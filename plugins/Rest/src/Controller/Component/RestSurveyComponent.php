<?php
namespace Rest\Controller\Component;

use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Utility\Xml;
use Cake\Utility\Text;
use Cake\Log\LogTrait;
use Cake\I18n\Time;

define("NS_XHTML", "http://www.w3.org/1999/xhtml");
define("NS_XF", "http://www.w3.org/2002/xforms");
define("NS_EV", "http://www.w3.org/2001/xml-events");
define("NS_XSD", "http://www.w3.org/2001/XMLSchema");
define("NS_OE", "https://www.openemis.org");

class RestSurveyComponent extends Component {
    use LogTrait;
    
    public $controller;
    public $action;

    public $components = ['Paginator', 'Workflow'];

    public $allowedActions = array('listing', 'schools', 'download');

    public function initialize(array $config) {
        $this->controller = $this->_registry->getController();
        $this->action = $this->request->params['action'];

        $models = $this->config('models');
        foreach ($models as $key => $model) {
            if (!is_null($model)) {
                $this->{$key} = TableRegistry::get($model);
                $this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
            } else {
                $this->{$key} = null;
            }

            $modelInfo = explode('.', $model);
            $base = count($modelInfo) == 1 ? $modelInfo[0] : $modelInfo[1];
            $this->controller->set('Custom_' . $key, $base);
        }
    }

    public function listing() {
        $query = $this->Form->find('list');
        $options = [];
        $options['limit'] = !is_null($this->request->query('limit')) ? $this->request->query('limit') : 20;
        $options['page'] = !is_null($this->request->query('page')) ? $this->request->query('page') : 1;

        // Start of joining SurveyStatus table
        $todayDate = date('Y-m-d');
        $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));
        $SurveyStatuses = TableRegistry::get('Survey.SurveyStatuses');

        $query->innerJoin(
                [$SurveyStatuses->alias() => $SurveyStatuses->table()],
                [
                    $SurveyStatuses->aliasField($this->formKey . ' = ') . $this->Form->aliasField('id'),
                    $SurveyStatuses->aliasField('date_disabled >=') => $todayTimestamp
                ]
            )
            ->group($this->Form->aliasField('id'));
        //End

        $models = $this->config('models');
        if (!is_null($models['Module'])) {
            $moduleOptions = $this->Module
                ->find('list', ['keyField' => 'id', 'valueField' => 'code'])
                ->find('visible')
                ->where([
                    $this->Module->aliasField('parent_id') => 0
                ])
                ->toArray();
            $selectedModule = !is_null($this->request->query('module')) ? $this->request->query('module') : key($moduleOptions);

            $query->where([
                $this->Form->aliasField($this->moduleKey) => $selectedModule
            ]);
        }

        $query->order([$this->Form->aliasField('name ASC')]);

        try {
            $data = $this->Paginator->paginate($query, $options);
            $result = [];
            if (!$data->isEmpty()) {
                $forms = $data->toArray();

                $url = '/' . $this->controller->name . '/survey/download/xform/';
                //$media_url = '/' . $this->controller->params->controller . '/survey/downloadImage/';

                $list = [];
                foreach ($forms as $key => $form) {
                    $list[] = [
                        'id' => $key,
                        'name' => htmlspecialchars($form, ENT_QUOTES)
                    ];
                }

                $requestPaging = $this->controller->request['paging'][$this->Form->alias()];
                $result['total'] = $requestPaging['count'];
                $result['page'] = $requestPaging['page'];
                $result['limit'] = $requestPaging['perPage'];   // limit
                $result['list'] = $list;
                $result['url'] = $url;
                //$result['media_url'] = $media_url;
            }
        } catch (NotFoundException $e) {
            $this->log($e->getMessage(), 'debug');
            $result['list'] = [];
        }

        $this->response->body(json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }

    public function schools() {
        $result = [];

        $ids = !is_null($this->request->query('ids')) ? $this->request->query('ids') : 0;
        $limit = !is_null($this->request->query('limit')) ? $this->request->query('limit') : 10;
        $page = !is_null($this->request->query('page')) ? $this->request->query('page') : 1;

        if ($ids != 0 && $page > 0) {
            $formIds = explode(",", $ids);
            // Institutions
            $Institutions = TableRegistry::get('Institution.Institutions');
            $institutions = $Institutions
                ->find('list')
                ->limit($limit)
                ->page($page)
                ->toArray();
            // End

            // Academic Periods
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periods = $AcademicPeriods->getList(['withLevels' => false]);
            // End

            $list = [];
            $SurveyRecords = TableRegistry::get('Institution.InstitutionSurveys');
            $statusIds = $this->Workflow->getStepsByModelCode($SurveyRecords->registryAlias(), 'NOT_COMPLETED');
            if (!empty($statusIds)) {
                foreach ($institutions as $institutionId => $institution) {
                    $SurveyRecords->buildSurveyRecords($institutionId);

                    $forms = [];
                    $surveyResults = $SurveyRecords
                        ->find()
                        ->where([
                            $SurveyRecords->aliasField('institution_id') => $institutionId,
                            $SurveyRecords->aliasField($this->formKey . ' IN') => $formIds,
                            $SurveyRecords->aliasField('status_id IN') => $statusIds    // Not Completed
                        ])
                        ->all();

                    if (!$surveyResults->isEmpty()) {
                        $records = $surveyResults->toArray();
                        foreach ($records as $recordKey => $recordObj) {
                            $formId = $recordObj->{$this->formKey};
                            $forms[$formId]['id'] = $formId;
                            $forms[$formId]['periods'][] = $recordObj->academic_period_id;
                        }
                    }

                    if (!empty($forms)) {
                        $list[] = array(
                            'id' => $institutionId,
                            'name' => $institution,
                            'forms' => $forms
                        );
                    }
                }
            }

            $result['list'] = $list;
            $result['periods'] = $periods;
        }

        $this->response->body(json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }

    public function download($format="xform", $id=0, $output=true) {
        switch ($format) {
            case 'xform':
                $result = $this->getXForms($format, $id);
                break;
            default:
                break;
        }

        if ($output) { // true = output to screen
            if (is_object($result)) {
                $this->response->body($result->asXML());
            } else {
                $this->response->body($result);
            }
            $this->response->type('xml');

            return $this->response;
        } else { // download as file
            $fileName = $format . '_' . date('Ymdhis');

            $this->response->body($result->asXML());
            $this->response->type('xml');

            // Optionally force file download
            $this->response->download($fileName . '.xml');

            // Return response object to prevent controller from trying to render a view.
            return $this->response;
        }
    }

    public function upload() {
        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->data;
            $this->log('Data:', 'debug');
            $this->log($data, 'debug');

            if (array_key_exists('response', $data)) {
                $CustomRecords = TableRegistry::get('Institution.InstitutionSurveys');
                $formAlias = $this->Form->alias();
                $fieldAlias = $this->Field->alias();
                // To use $this->recordKey when record table is changed to institution_surveys and foreign key will become institution_survey_id
                $recordKey = 'institution_survey_id';

                $xmlResponse = $data['response'];
                // lines below is for testing
                // $xmlResponse = "<xf:instance id='xform'><oe:SurveyForms id='1'><oe:Institutions>1</oe:Institutions><oe:AcademicPeriods>10</oe:AcademicPeriods><oe:SurveyQuestions id='2'>some text</oe:SurveyQuestions><oe:SurveyQuestions id='3'>0</oe:SurveyQuestions><oe:SurveyQuestions id='4'>some long long text</oe:SurveyQuestions><oe:SurveyQuestions id='6'>3</oe:SurveyQuestions><oe:SurveyQuestions id='7'>5 6 7</oe:SurveyQuestions><oe:SurveyQuestions id='25'><oe:SurveyTableRows id='20'><oe:SurveyTableColumns0 id='0'>Male</oe:SurveyTableColumns0><oe:SurveyTableColumns1 id='37'>10</oe:SurveyTableColumns1><oe:SurveyTableColumns2 id='38'>20</oe:SurveyTableColumns2><oe:SurveyTableColumns3 id='39'>30</oe:SurveyTableColumns3></oe:SurveyTableRows><oe:SurveyTableRows id='21'><oe:SurveyTableColumns0 id='0'>Female</oe:SurveyTableColumns0><oe:SurveyTableColumns1 id='37'>15</oe:SurveyTableColumns1><oe:SurveyTableColumns2 id='38'>25</oe:SurveyTableColumns2><oe:SurveyTableColumns3 id='39'>35</oe:SurveyTableColumns3></oe:SurveyTableRows></oe:SurveyQuestions></oe:SurveyForms></xf:instance>";
                // $xmlResponse = '<xf:instance id="xform"><oe:SurveyForms id="16"><oe:Institutions>1059</oe:Institutions><oe:AcademicPeriods>10</oe:AcademicPeriods><oe:SurveyQuestions id="113" array-id="1">1.3641 123.9214</oe:SurveyQuestions><oe:SurveyQuestions id="114" array-id="2">1.74 100.243</oe:SurveyQuestions><oe:SurveyQuestions id="16" array-id="3">5</oe:SurveyQuestions></oe:SurveyForms></xf:instance>';
                // end testing data //

                // save response into database for debug purpose, always purge 3 days old response
                $SurveyResponses = TableRegistry::get('Survey.SurveyResponses');
                $expiryDate = new Time();
                $expiryDate->subDays(3);
                $SurveyResponses->deleteAll([
                    $SurveyResponses->aliasField('created <') => $expiryDate
                ]);

                $responseData = [
                    'id' => Text::uuid(),
                    'response' => $xmlResponse
                ];
                $responseEntity = $SurveyResponses->newEntity($responseData);
                if (!$SurveyResponses->save($responseEntity)) {
                    $this->log($responseEntity->errors(), 'debug');
                }
                // End
                
                $this->log('XML Response', 'debug');
                $this->log($xmlResponse, 'debug');
                $xmlResponse = str_replace("xf:", "", $xmlResponse);
                $xmlResponse = str_replace("oe:", "", $xmlResponse);

                $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlResponse;
                $this->log('XML String:', 'debug');
                $this->log($xmlstr, 'debug');
                $xml = Xml::build($xmlstr);

                $formId = $xml->$formAlias->attributes()->id->__toString();
                $institutionId = $xml->$formAlias->Institutions->__toString();
                $periodId = $xml->$formAlias->AcademicPeriods->__toString();
                $statusIds = $this->Workflow->getStepsByModelCode($CustomRecords->registryAlias(), 'COMPLETED');
                $createdUserId = 1; // System Administrator

                $formData = [];
                $formData = [
                    $this->formKey => $formId,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $periodId,
                    'created_user_id' => $createdUserId
                ];

                // Find existing record
                $recordId = null;
                $where = [
                    $CustomRecords->aliasField($this->formKey) => $formId,
                    $CustomRecords->aliasField('institution_id') => $institutionId,
                    $CustomRecords->aliasField('academic_period_id') => $periodId
                ];
                $recordResults = $CustomRecords
                    ->find()
                    ->where($where)
                    ->all();

                if (!$recordResults->isEmpty()) {
                    $formData['id'] = $recordResults->first()->id;
                }
                // End

                // Overwrite survey record only if is not completed
                $where[$CustomRecords->aliasField('status_id IN')] = $statusIds;
                $completedResults = $CustomRecords
                    ->find()
                    ->where($where)
                    ->all();

                if ($completedResults->isEmpty()) {
                    // Update record table
                    $entity = $CustomRecords->newEntity($formData, ['validate' => false]);
                    if ($CustomRecords->save($entity)) {
                        // if($entity->status == 2) {
                            $message = 'Survey record has been submitted successfully.';
                        // } else {
                            // $message = 'Survey record has been saved to draft successfully.';
                        // }
                        $this->log('Message:', 'debug');
                        $this->log($message, 'debug');
                    } else {
                        $this->log($entity->errors(), 'debug');
                    }
                    // End

                    $recordId = $entity->id;
                    if (!is_null($recordId)) {
                        $CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
                        $fieldTypes = $CustomFieldTypes
                            ->find('list', ['keyField' => 'code', 'valueField' => 'value'])
                            ->toArray();

                        $fields = $xml->$formAlias->$fieldAlias;
                        foreach ($fields as $field) {
                            $fieldId = $field->attributes()->id->__toString();
                            $fieldType = $this->Field->get($fieldId)->field_type;
                            $fieldColumnName = $fieldTypes[$fieldType];

                            // Always delete the answers before reinsert
                            if ($fieldType == 'TABLE') {
                                $this->TableCell->deleteAll([
                                    $this->TableCell->aliasField($recordKey) => $recordId,
                                    $this->TableCell->aliasField($this->fieldKey) => $fieldId
                                ]);
                            } else {
                                $this->FieldValue->deleteAll([
                                    $this->FieldValue->aliasField($recordKey) => $recordId,
                                    $this->FieldValue->aliasField($this->fieldKey) => $fieldId
                                ]);
                            }

                            switch($fieldType) {
                                case 'TEXT':
                                case 'NUMBER':
                                case 'TEXTAREA':
                                case 'DROPDOWN':
                                case 'DATE':
                                case 'TIME':
                                    $answerValue = urldecode($field->__toString());
                                    if (strlen($answerValue) != 0) {
                                        $answerData = [
                                            $recordKey => $recordId,
                                            $this->fieldKey => $fieldId,
                                            $fieldColumnName => $answerValue,
                                            'institution_id' => $institutionId,
                                            'created_user_id' => $createdUserId
                                        ];

                                        // Save answer
                                        $answerEntity = $this->FieldValue->newEntity($answerData);
                                        if (!$this->FieldValue->save($answerEntity)) {
                                            $this->log($answerEntity->errors(), 'debug');
                                        }
                                        // End
                                    }
                                    break;
                                case 'CHECKBOX':
                                    $answerValue = urldecode($field->__toString());
                                    if (strlen($answerValue) != 0) {
                                        $checkboxValues = explode(" ", $answerValue);
                                        foreach ($checkboxValues as $checkboxKey => $checkboxValue) {
                                            $answerData = [
                                                $recordKey => $recordId,
                                                $this->fieldKey => $fieldId,
                                                $fieldColumnName => $checkboxValue,
                                                'institution_id' => $institutionId,
                                                'created_user_id' => $createdUserId
                                            ];

                                            // Save answer
                                            $answerEntity = $this->FieldValue->newEntity($answerData);
                                            if (!$this->FieldValue->save($answerEntity)) {
                                                $this->log($answerEntity->errors(), 'debug');
                                            }
                                            // End
                                        }
                                    }
                                    break;
                                case 'TABLE':
                                    foreach ($field->children() as $row => $rowObj) {
                                        $rowId = $rowObj->attributes()->id->__toString();
                                        foreach ($rowObj->children() as $col => $colObj) {
                                            $colId = $colObj->attributes()->id->__toString();
                                            if ($colId != 0) {
                                                $cellValue = urldecode($colObj->__toString());
                                                if (strlen($cellValue) != 0) {
                                                    $cellData = array(
                                                        $recordKey => $recordId,
                                                        $this->fieldKey => $fieldId,
                                                        $this->tableColumnKey => $colId,
                                                        $this->tableRowKey => $rowId,
                                                        $fieldColumnName => $cellValue,
                                                        'institution_id' => $institutionId,
                                                        'created_user_id' => $createdUserId
                                                    );

                                                    // Save cell by cell
                                                    $cellEntity = $this->TableCell->newEntity($cellData);
                                                    if (!$this->TableCell->save($cellEntity)) {
                                                        $this->log($cellEntity->errors(), 'debug');
                                                    }
                                                    // End
                                                }
                                            }
                                        }
                                    }
                                    break;
                                case 'COORDINATES':
                                    $answerValue = urldecode($field->__toString());
                                    if (strlen($answerValue) != 0) {
                                        $answerValues = explode(' ', $answerValue);
                                        if (count($answerValues)==2) {
                                            $answerValue = json_encode([
                                                'latitude' => $answerValues[0],
                                                'longitude' => $answerValues[1]
                                            ]);
                                            $answerData = [
                                                $recordKey => $recordId,
                                                $this->fieldKey => $fieldId,
                                                $fieldColumnName => $answerValue,
                                                'institution_id' => $institutionId,
                                                'created_user_id' => $createdUserId
                                            ];

                                            // Save answer
                                            $answerEntity = $this->FieldValue->newEntity($answerData);
                                            if (!$this->FieldValue->save($answerEntity)) {
                                                $this->log($answerEntity->errors(), 'debug');
                                            }
                                            // End
                                        } else {
                                            $this->log('COORDINATES type answer is invalid', 'debug');
                                        }
                                    }
                                    break;
                                case 'FILE':
                                    $answerValue = urldecode($field->__toString());
                                    if (strlen($answerValue) != 0) {
                                        // expected format received from mobile
                                        // filename.jpg|data:image/jpg;base64,urlencode( base64_encode( file_get_contents( $filepath) ) )
                                        list($fileName, $fileData) = explode("|", $answerValue, 2);
                                        list($fileTypeStr, $encodedStr) = explode(";", $fileData, 2);
                                        list($encodeType, $encoded) = explode(",", $encodedStr, 2);
                                        $decoded = base64_decode($encoded);

                                        $answerData = [
                                            $recordKey => $recordId,
                                            $this->fieldKey => $fieldId,
                                            'text_value' => $fileName,
                                            $fieldColumnName => $decoded,   // fileContent
                                            'institution_id' => $institutionId,
                                            'created_user_id' => $createdUserId
                                        ];

                                        // Save answer
                                        $answerEntity = $this->FieldValue->newEntity($answerData);
                                        if (!$this->FieldValue->save($answerEntity)) {
                                            $this->log($answerEntity->errors(), 'debug');
                                        }
                                        // End
                                    }
                                    break;
                            }
                        }
                    }
                } else {
                    $message = 'Survey record is not saved.';
                    $this->log('Message:', 'debug');
                    $this->log($message, 'debug');
                }
            }
        }
    }

    public function getXForms($instanceId, $id) {
        $title = $this->Form->get($id)->name;
        $title = htmlspecialchars($title, ENT_QUOTES);

        $fields = $this->FormField
            ->find()
            ->find('order')
            ->select([
                'form_id' => $this->FormField->aliasField($this->formKey),
                'field_id' => $this->FormField->aliasField($this->fieldKey),
                'section_name' => $this->FormField->aliasField('section'),
                'name' => $this->FormField->aliasField('name'),
                'is_mandatory' => $this->FormField->aliasField('is_mandatory'),
                'is_unique' => $this->FormField->aliasField('is_unique'),
                'field_type' => $this->Field->aliasField('field_type'),
                'default_name' => $this->Field->aliasField('name'),
                'default_is_mandatory' => $this->Field->aliasField('is_mandatory'),
                'default_is_unique' => $this->Field->aliasField('is_unique'),
                'params' => $this->Field->aliasField('params')
            ])
            ->innerJoin(
                [$this->Field->alias() => $this->Field->table()],
                [$this->Field->aliasField('id =') . $this->FormField->aliasField($this->fieldKey)]
            )
            ->where([
                $this->FormField->aliasField($this->formKey) => $id
            ])
            ->toArray();

        $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
                <html
                    xmlns="' . NS_XHTML . '"
                    xmlns:xf="' . NS_XF . '"
                    xmlns:ev="' . NS_EV . '"
                    xmlns:xsd="' . NS_XSD . '"
                    xmlns:oe="' . NS_OE . '">
                </html>';

        $xml = Xml::build($xmlstr);

        $headNode = $xml->addChild("head", null, NS_XHTML);
        $bodyNode = $xml->addChild("body", null, NS_XHTML);
        $headNode->addChild("title", $title, NS_XHTML);
        $modelNode = $headNode->addChild("model", null, NS_XF);

        $instanceNode = $modelNode->addChild("instance", null, NS_XF);
        $instanceNode->addAttribute("id", $instanceId);
        $formNode = $instanceNode->addChild($this->Form->alias(), null, NS_OE);
        $formNode->addAttribute("id", $id);
        $formNode->addChild('Institutions', null, NS_OE);
        $formNode->addChild('AcademicPeriods', null, NS_OE);
    
        $sectionBreakNode = $bodyNode;
        // set fixed fields
        $fieldNode = $sectionBreakNode->addChild("input", null, NS_XF);
        $fieldNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/Institutions");
        $fieldNode->addAttribute("oe-type", "select");
        $fieldNode->addChild("label", "Institution", NS_XF);
        $ref = "instance('" . $instanceId . "')/".$this->Form->alias()."/Institutions";
        $this->_setFieldBindNode($modelNode, $instanceId, 'string', true, $ref);
        
        $fieldNode = $sectionBreakNode->addChild("input", null, NS_XF);
        $fieldNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/AcademicPeriods");
        $fieldNode->addAttribute("oe-type", "select");
        $fieldNode->addAttribute("oe-dependency", "instance('" . $instanceId . "')/".$this->Form->alias()."/Institutions");
        $fieldNode->addChild("label", "Academic Period", NS_XF);
        $ref = "instance('" . $instanceId . "')/".$this->Form->alias()."/AcademicPeriods";
        $this->_setFieldBindNode($modelNode, $instanceId, 'string', true, $ref);
        // end setting fixed fields

        $schemaNode = $modelNode->addChild("schema", null, NS_XSD);

        $sectionName = null;
        foreach ($fields as $key => $field) {
            $index = $key + 1;

            // Section
            if ($field->section_name != $sectionName) {
                $sectionName = $field->section_name;
                $sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
                $sectionBreakNode->addAttribute("ref", $field->form_id . '_' . $field->field_id);
                $sectionBreakNode->addChild("label", htmlspecialchars($sectionName, ENT_QUOTES), NS_XF);
            }
            // End

            $fieldTypeFunction = '_'.strtolower($field->field_type).'Type';
            if (method_exists($this, $fieldTypeFunction)) {
                // function for student list type does not exists and puting this set of statement outside of method_exists check scope
                // will actually creates a malformed xform on the mobile side although viewing from browser is ok.
                    $fieldNode = $formNode->addChild($this->Field->alias(), null, NS_OE);
                    $fieldNode->addAttribute("id", $field->field_id);

                    // added array-id attribute to instance element child in the header to assist on troubleshooting the generated xml
                    // the sample output would be <oe:SurveyQuestions id="118" array-id="1"/>
                    // this element will actually map to <xf:bind ref="instance('xform')/SurveyForms/SurveyQuestions[1]" type="name" required="true()" />
                    // if instance element child exists but its related bind element does not exists, validation will not work.
                    $fieldNode->addAttribute("array-id", $index);
                //
                        
                $this->$fieldTypeFunction($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode);
            }
        }

        return $xml;
    }

    private function _setValidationSchema($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode) {

    }

    private function _textType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $validationHint = '';
        $bindType = 'string';
        if (!empty($field->params)) {
            $params = json_decode($field->params, true);
            $validationType = key($params);
            if (in_array($validationType, ['min_length', 'max_length', 'range'])) {
                $bindType = 'string' . Inflector::camelize($validationType) . $index;
                $this->_setFieldBindNode($modelNode, $instanceId, $bindType, $field->default_is_mandatory, '', $index);
                $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
                $simpleType->addAttribute("name", $bindType);
                $restriction = $simpleType->addChild('restriction', null, NS_XSD);
                $restriction->addAttribute("base", "xf:string");
                if ($validationType!='range') {
                    $condition = $restriction->addChild(Inflector::variable($validationType), null, NS_XSD);
                    $condition->addAttribute("value", $params[$validationType]);
                    if ($validationType=='min_length') {
                        $validationHint = __('Value should be at least '. $params[$validationType].' characters long.');
                    } else if ($validationType=='max_length') {
                        $validationHint = __('Value should not be more than '. $params[$validationType].' characters long.');
                    }
                } else {
                    $values = [];
                    foreach ($params[$validationType] as $key => $value) {
                        if ($key=='lower') {
                            $condition = $restriction->addChild('minLength', null, NS_XSD);
                        } else {
                            $condition = $restriction->addChild('maxLength', null, NS_XSD);
                        }
                        $condition->addAttribute("value", $value);
                        $values[] = $value;
                    }
                    $validationHint = __('Value should be between '. implode(' and ', $values).' characters long.');
                }
            }
        }
        $fieldNode = $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'input', $instanceId, $index);
        if (!empty($validationHint)) {
            // <xf:hint>Your name should be at least 3 characters long.</xf:hint>
            $fieldHint = $fieldNode->addChild("hint", htmlspecialchars($validationHint, ENT_QUOTES), NS_XF);
        } else {
            $this->_setFieldBindNode($modelNode, $instanceId, $bindType, $field->default_is_mandatory, '', $index);
        }
    }

    private function _numberType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $validationHint = '';
        if (!empty($field->params)) {
            $params = json_decode($field->params, true);
            $validationType = key($params);
            if (in_array($validationType, ['min_value', 'max_value', 'range'])) {
                if ($validationType!='range') {
                    if ($validationType=='min_value') {
                        $constraint = ". > ".$params[$validationType];
                        $validationHint = __('Value should be at least '. $params[$validationType]);
                    } else if ($validationType=='max_value') {
                        $constraint = ". < ".$params[$validationType];
                        $validationHint = __('Value should not be more than '. $params[$validationType]);
                    }
                } else {
                    $values = [];
                    $constraint = "";
                    foreach ($params[$validationType] as $key => $value) {
                        if ($key=='lower') {
                            $constraint .= empty($constraint) ? ". > ".$value : " && . > ".$value;
                        } else {
                            $constraint .= empty($constraint) ? ". < ".$value : " && . < ".$value;
                        }
                        $values[] = $value;
                    }
                    $validationHint = __('Value should be between '. implode(' and ', $values));
                }
            }
        }
        $fieldNode = $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'input', $instanceId, $index);
        $bindNode = $this->_setFieldBindNode($modelNode, $instanceId, 'integer', $field->default_is_mandatory, '', $index);
        if (!empty($validationHint)) {
            $fieldHint = $fieldNode->addChild("hint", htmlspecialchars($validationHint, ENT_QUOTES), NS_XF);
            $bindNode->addAttribute("constraint", $constraint);
        }
    }

    private function _textareaType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'textarea', $instanceId, $index);
        $this->_setFieldBindNode($modelNode, $instanceId, 'string', $field->default_is_mandatory, '', $index);
    }

    private function _dropdownType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $dropdownNode = $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'select1', $instanceId, $index);

        $fieldOptionResults = $this->FieldOption
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->FieldOption->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();
        if (!$fieldOptionResults->isEmpty()) {
            $fieldOptions = $fieldOptionResults->toArray();
            foreach ($fieldOptions as $fieldOption) {
                $itemNode = $dropdownNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($fieldOption->name, ENT_QUOTES), NS_XF);
                    $itemNode->addChild("value", $fieldOption->id, NS_XF);
            }
        }

        $this->_setFieldBindNode($modelNode, $instanceId, 'integer', $field->default_is_mandatory, '', $index);
    }

    private function _checkboxType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $checkboxNode = $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'select', $instanceId, $index);

        $fieldOptionResults = $this->FieldOption
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->FieldOption->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        if (!$fieldOptionResults->isEmpty()) {
            $fieldOptions = $fieldOptionResults->toArray();
            foreach ($fieldOptions as $fieldOption) {
                $itemNode = $checkboxNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($fieldOption->name, ENT_QUOTES), NS_XF);
                    $itemNode->addChild("value", $fieldOption->id, NS_XF);
            }
        }

        $this->_setFieldBindNode($modelNode, $instanceId, 'integer', $field->default_is_mandatory, '', $index);
    }

    private function _tableType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        // To nested table inside xform group
        $tableBreakNode = $sectionBreakNode->addChild("group", null, NS_XF);
        $tableBreakNode->addAttribute("ref", $field->field_id);
        $tableBreakNode->addAttribute("oe-type", "table");
        $tableBreakNode->addChild("label", htmlspecialchars($field->default_name, ENT_QUOTES), NS_XF);
        // End
        // 
        $tableNode = $tableBreakNode->addChild("table", null, NS_XHTML);
        $tableNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
            $tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
            $tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
                $xformRepeat = $tableBody->addChild("repeat", null, NS_XF);
                $xformRepeat->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableRow->alias());
                    $tbodyRow = $xformRepeat->addChild("tr", null, NS_XHTML);

        $tableColumnResults = $this->TableColumn
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->TableColumn->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        $tableRowResults = $this->TableRow
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->TableRow->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        if (!$tableColumnResults->isEmpty() && !$tableRowResults->isEmpty()) {
            $tableColumns = $tableColumnResults->toArray();
            $tableRows = $tableRowResults->toArray();
            foreach ($tableRows as $row => $tableRow) {
                $rowNode = $fieldNode->addChild($this->TableRow->alias(), null, NS_OE);
                $rowNode->addAttribute("id", $tableRow->id);

                foreach ($tableColumns as $col => $tableColumn) {
                    if ($col == 0) {
                        $columnNode = $rowNode->addChild($this->TableColumn->alias() . $col, htmlspecialchars($tableRow->name, ENT_QUOTES), NS_OE);
                        $columnNode->addAttribute("id", $col);
                        $cellType = 'output';
                    } else {
                        $columnNode = $rowNode->addChild($this->TableColumn->alias() . $col, null, NS_OE);
                        $columnNode->addAttribute("id", $tableColumn->id);
                        $cellType = 'input';
                    }

                    if ($row == 0) {
                        $tableHeader->addChild("th", htmlspecialchars($tableColumn->name, ENT_QUOTES), NS_XHTML);
                        $tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
                            $tbodyCell = $tbodyColumn->addChild($cellType, null, NS_XF);
                                $tbodyCell->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias().$col);

                        $bindNode = $modelNode->addChild("bind", null, NS_XF);
                        $bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias().$col);
                        $bindNode->addAttribute("type", 'string');
                    }
                }
            }
        }
    }

    private function _dateType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $validationHint = '';
        if (!empty($field->params)) {
            $params = json_decode($field->params, true);
            if (array_key_exists('start_date', $params) && array_key_exists('end_date', $params)) {
                $validationType = 'between';
            } else if (array_key_exists('start_date', $params)) {
                $validationType = 'earlier';
            } else if (array_key_exists('end_date', $params)) {
                $validationType = 'later';
            } else {
                $validationType = false;
            }
            if ($validationType) {
                if ($validationType!='between') {
                    if ($validationType=='earlier') {
                        $constraint = ". > '" . $params['start_date'] . "'";
                        $validationHint = __('Value should be at least '. $params['start_date']);
                    } else if ($validationType=='later') {
                        $constraint = ". < '" . $params['end_date'] . "'";
                        $validationHint = __('Value should not be more than '. $params['end_date']);
                    }
                } else {
                    $constraint = ". > '" . $params['start_date'] . "' && . < '" . $params['end_date'] . "'";
                    $validationHint = __('Value should be between '. implode(' and ', $params));
                }
            }
        }
        $fieldNode = $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'input', $instanceId, $index);
        $bindNode = $this->_setFieldBindNode($modelNode, $instanceId, 'date', $field->default_is_mandatory, '', $index);
        if (!empty($validationHint)) {
            $fieldHint = $fieldNode->addChild("hint", htmlspecialchars($validationHint, ENT_QUOTES), NS_XF);
            $bindNode->addAttribute("constraint", $constraint);
        }
    }

    private function _timeType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $validationHint = '';
        if (!empty($field->params)) {
            $params = json_decode($field->params, true);
            if (array_key_exists('start_time', $params) && array_key_exists('end_time', $params)) {
                $validationType = 'between';
            } else if (array_key_exists('start_time', $params)) {
                $validationType = 'earlier';
            } else if (array_key_exists('end_time', $params)) {
                $validationType = 'later';
            } else {
                $validationType = false;
            }
            if ($validationType) {
                if ($validationType!='between') {
                    if ($validationType=='earlier') {
                        $constraint = ". > '" . $this->_twentyFourHourFormat($params['start_time']) . "'";
                        $validationHint = __('Value should be at least '. $params['start_time']);
                    } else if ($validationType=='later') {
                        $constraint = ". < '" . $this->_twentyFourHourFormat($params['end_time']) . "'";
                        $validationHint = __('Value should not be more than '. $params['end_time']);
                    }
                } else {
                    $constraint = ". > '" . $this->_twentyFourHourFormat($params['start_time']) . "' && . < '" . $this->_twentyFourHourFormat($params['end_time']) . "'";
                    $validationHint = __('Value should be between '. implode(' and ', $params));
                }
            }
        }
        $fieldNode = $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'input', $instanceId, $index);
        $bindNode = $this->_setFieldBindNode($modelNode, $instanceId, 'time', $field->default_is_mandatory, '', $index);
        if (!empty($validationHint)) {
            $fieldHint = $fieldNode->addChild("hint", htmlspecialchars($validationHint, ENT_QUOTES), NS_XF);
            $bindNode->addAttribute("constraint", $constraint);
        }
    }

    private function _fileType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'upload', $instanceId, $index);
        $this->_setFieldBindNode($modelNode, $instanceId, 'file', $field->default_is_mandatory, '', $index);
    }

    private function _twentyFourHourFormat($value) {
        $values = explode(' ', $value);
        if (strtolower($values[1])=='am') {
            return $values[0] . ':00';
        } else {
            $time = explode(':', $values[0]);
            $time[] = '00';
            $time[0] = intval($time[0]) + 12;
            return implode(':', $time);
        }
    }

    private function _coordinatesType($field, $sectionBreakNode, $modelNode, $instanceId, $index, $fieldNode, $schemaNode) {
        $this->_setCommonAttribute($sectionBreakNode, $field->default_name, 'input', $instanceId, $index);
        $this->_setFieldBindNode($modelNode, $instanceId, 'geopoint', $field->default_is_mandatory, '', $index);
    }

    private function _setCommonAttribute($sectionBreakNode, $fieldName, $fieldType, $instanceId, $index) {
        $fieldNode = $sectionBreakNode->addChild($fieldType, null, NS_XF);
        $fieldNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
        $fieldNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
        return $fieldNode;
    }

    private function _setFieldBindNode($modelNode, $instanceId, $bindType, $fieldIsMandatory=false, $ref='', $index='0') {
        if (empty($ref)) {
            $ref = "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]";
        }
        $bindNode = $modelNode->addChild("bind", null, NS_XF);
        $bindNode->addAttribute("ref", $ref);
        $bindNode->addAttribute("type", $bindType);
        if($fieldIsMandatory) {
            $bindNode->addAttribute("required", 'true()');
        } else {
            $bindNode->addAttribute("required", 'false()');
        }
        return $bindNode;
    }
}
