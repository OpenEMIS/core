<?php
namespace Rest\Controller\Component;

use ArrayObject;
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

class RestSurveyComponent extends Component
{
    use LogTrait;
    
    public $controller;
    public $action;

    public $components = ['Paginator', 'Workflow'];

    public $allowedActions = array('listing', 'schools', 'download');

    public function initialize(array $config)
    {
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

    public function listing()
    {
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

    public function schools()
    {
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

    public function download($format="xform", $id=0, $output=true)
    {
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

    public function upload()
    {
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
                $this->deleteExpiredResponse();
                $this->addResponse($xmlResponse);
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

    public function getXForms($instanceId, $id)
    {
        $title = $this->Form->get($id)->name;
        $title = htmlspecialchars($title, ENT_QUOTES);

        $fields = $this->getFields($id);

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

        // need further testing if is commented out
        // $sectionBreakNode = $bodyNode;

        // set fixed Institutions Field
        $references = [$this->Form->alias(), 'Institutions'];

        $formNode->addChild('Institutions', null, NS_OE);
        $fieldNode = $bodyNode->addChild("input", null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $fieldNode->addAttribute("oe-type", "select");
        $fieldNode->addChild("label", "Institution", NS_XF);

        $this->setBindNode($modelNode, $instanceId, $references, ['type' => 'string', 'required' => true]);
        // End

        // set fixed Academic Periods Field
        $references = [$this->Form->alias(), 'AcademicPeriods'];

        $formNode->addChild('AcademicPeriods', null, NS_OE);
        $fieldNode = $bodyNode->addChild("input", null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $fieldNode->addAttribute("oe-type", "select");
        $fieldNode->addAttribute("oe-dependency", $this->getRef($instanceId, [$this->Form->alias(), 'Institutions']));
        $fieldNode->addChild("label", "Academic Period", NS_XF);

        $this->setBindNode($modelNode, $instanceId, $references, ['type' => 'string', 'required' => true]);
        // End

        // used to build validation rules
        // $schemaNode = $modelNode->addChild("schema", null, NS_XSD);

        $sectionName = null;
        foreach ($fields as $key => $field) {
            $extra = new ArrayObject([]);
            $extra['index'] = $key + 1;
            $extra['head'] = $headNode;
            $extra['body'] = $bodyNode;
            $extra['model'] = $modelNode;
            $extra['instance'] = $instanceNode;
            $extra['form'] = $formNode;

            $extra['references'] = [$this->Form->alias(), $this->Field->alias()."[".$extra['index']."]"];
            $extra['default_value'] = null;

            if (is_null($sectionName)) { $parentNode = $bodyNode; }

            // Section
            if ($field->section_name != $sectionName) {
                $sectionName = $field->section_name;
                $sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
                $sectionBreakNode->addAttribute("ref", $field->form_id . '_' . $field->field_id);
                $sectionBreakNode->addChild("label", htmlspecialchars($sectionName, ENT_QUOTES), NS_XF);

                $parentNode = $sectionBreakNode;
            }
            // End

            $fieldTypeFunction = strtolower($field->field_type);
            if (method_exists($this, $fieldTypeFunction)) {
                $this->$fieldTypeFunction($field, $parentNode, $instanceId, $extra);

                if (!is_null($extra['form'])) {
                    $this->setModelNode($field, $extra['form'], $instanceId, $extra);
                }
            }
        }

        return $xml;
    }

    private function getFields($id)
    {
        return $this->FormField
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
    }

    private function text($field, $parentNode, $instanceId, $extra)
    {
        $this->setBodyNode($field, $parentNode, $instanceId, 'input', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'string', 'required' => $field->default_is_mandatory]);
    }

    private function number($field, $parentNode, $instanceId, $extra)
    {
        $this->setBodyNode($field, $parentNode, $instanceId, 'input', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'integer', 'required' => $field->default_is_mandatory]);
    }

    private function textarea($field, $parentNode, $instanceId, $extra)
    {
        $extra['references'] = [$this->Form->alias(), $this->Field->alias()."[".$extra['index']."]"];

        $this->setBodyNode($field, $parentNode, $instanceId, 'textarea', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'string', 'required' => $field->default_is_mandatory]);
    }

    private function dropdown($field, $parentNode, $instanceId, $extra)
    {
        $fieldOptionResults = $this->FieldOption
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->FieldOption->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        $dropdownNode = $this->setBodyNode($field, $parentNode, $instanceId, 'select1', $extra);
        if (!$fieldOptionResults->isEmpty()) {
            $fieldOptions = $fieldOptionResults->toArray();
            foreach ($fieldOptions as $fieldOption) {
                if ($fieldOption->is_default) {
                    // to set default value in Head > Model > instance e.g. <oe:SurveyQuestions id='5'>default value here</oe:SurveyQuestions>
                    $extra['default_value'] = $fieldOption->id;
                }

                $itemNode = $dropdownNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($fieldOption->name, ENT_QUOTES), NS_XF);
                    $itemNode->addChild("value", $fieldOption->id, NS_XF);
            }
        }

        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'integer', 'required' => $field->default_is_mandatory]);
    }

    private function checkbox($field, $parentNode, $instanceId, $extra)
    {
        $fieldOptionResults = $this->FieldOption
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->FieldOption->aliasField($this->fieldKey) => $field->field_id
            ])
            ->all();

        $checkboxNode = $this->setBodyNode($field, $parentNode, $instanceId, 'select', $extra);
        if (!$fieldOptionResults->isEmpty()) {
            $fieldOptions = $fieldOptionResults->toArray();
            foreach ($fieldOptions as $fieldOption) {
                $itemNode = $checkboxNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($fieldOption->name, ENT_QUOTES), NS_XF);
                    $itemNode->addChild("value", $fieldOption->id, NS_XF);
            }
        }

        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'integer', 'required' => $field->default_is_mandatory]);
    }

    private function table($field, $parentNode, $instanceId, $extra)
    {
        // To nested table inside xform group
        $tableBreakNode = $parentNode->addChild('group', null, NS_XF);
        $tableBreakNode->addAttribute("ref", $field->field_id);
        $tableBreakNode->addChild("label", htmlspecialchars($field->default_name, ENT_QUOTES), NS_XF);
        $tableBreakNode->addAttribute("oe-type", "table");
        // End

        $tableNode = $tableBreakNode->addChild("table", null, NS_XHTML);
        $tableNode->addAttribute("ref", $this->getRef($instanceId, $extra['references']));
            $tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
            $tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
                $repeatNode = $tableBody->addChild("repeat", null, NS_XF);
                $repeatNode->addAttribute("ref", $this->getRef($instanceId, array_merge($extra['references'], [$this->TableRow->alias()])));
                $tbodyRow = $repeatNode->addChild("tr", null, NS_XHTML);

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

            $fieldNode = $this->setModelNode($field, $extra['form'], $instanceId, $extra);
            $extra['form'] = null;  // set to null to skip adding into Head > Model > Instance

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
                            $tbodyCell->addAttribute("ref", $this->getRef($instanceId, array_merge($extra['references'], [$this->TableColumn->alias().$col])));

                        $this->setBindNode($extra['model'], $instanceId, array_merge($extra['references'], [$this->TableColumn->alias().$col]), ['type' => 'string']);
                    }
                }
            }
        }
    }


    private function date($field, $parentNode, $instanceId, $extra)
    {
        $this->setBodyNode($field, $parentNode, $instanceId, 'input', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'date', 'required' => $field->default_is_mandatory]);
    }

    private function time($field, $parentNode, $instanceId, $extra)
    {
        $this->setBodyNode($field, $parentNode, $instanceId, 'input', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'time', 'required' => $field->default_is_mandatory]);
    }

    private function coordinates($field, $parentNode, $instanceId, $extra)
    {
        $this->setBodyNode($field, $parentNode, $instanceId, 'input', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'geopoint', 'required' => $field->default_is_mandatory]);
    }

    private function file($field, $parentNode, $instanceId, $extra)
    {
        $this->setBodyNode($field, $parentNode, $instanceId, 'upload', $extra);
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], ['type' => 'file', 'required' => $field->default_is_mandatory]);
    }

    private function repeater($field, $parentNode, $instanceId, $extra)
    {
        $repeaterNode = $this->setBodyNode($field, $parentNode, $instanceId, 'repeater', $extra);

        $fieldNode = $this->setModelNode($field, $extra['form'], $instanceId, $extra);
        $repeatNode = $fieldNode->addChild('RepeatBlock', null, NS_OE);
        $extra['form'] = null;  // set to null to skip adding into Head > Model > Instance

        $formId = null;
        // Get Survey Form ID
        if ($field->has('params') && !empty($field->params)) {
            $params = json_decode($field->params, true);
            if (array_key_exists($this->formKey, $params)) {
                $formId = $params[$this->formKey];
            }
        }

        if (!is_null($formId)) {
            $fields = $this->getFields($formId);

            if (!empty($fields)) {
                foreach ($fields as $key => $field) {
                    $index = $key + 1;
                    // must reset to null
                    $extra['default_value'] = null;
                    $extra['references'] = [$this->Form->alias(), $this->Field->alias()."[".$extra['index']."]", 'RepeatBlock', $this->Field->alias().$index];

                    $fieldTypeFunction = strtolower($field->field_type);
                    if (method_exists($this, $fieldTypeFunction)) {
                        $this->$fieldTypeFunction($field, $repeaterNode, $instanceId, $extra);

                        // add to Head > Model > Instance > RepeatBlock here
                        $itemNode = $repeatNode->addChild($this->Field->alias().$index, null, NS_OE);
                        $itemNode->addAttribute("id", $field->field_id);
                    }
                }
            }
        } else {
            // Survey Form ID not found
            $this->log('Repeater Survey Form ID is not configured.', 'debug');
        }
        // End
    }

    private function setBodyNode($field, $parentNode, $instanceId, $fieldType, $extra)
    {
        $fieldNode = $parentNode->addChild($fieldType, null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $extra['references']));
        $fieldNode->addChild("label", htmlspecialchars($field->default_name, ENT_QUOTES), NS_XF);

        return $fieldNode;
    }

    private function setBindNode($modelNode, $instanceId, $references=[], $attr=[])
    {
        $bindType = array_key_exists('type', $attr) ? $attr['type'] : 'string';
        $required = array_key_exists('required', $attr) ? $attr['required'] : false;

        $bindNode = $modelNode->addChild("bind", null, NS_XF);
        $bindNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $bindNode->addAttribute("type", $bindType);

        if ($required) {
            $bindNode->addAttribute("required", 'true()');
        } else {
            $bindNode->addAttribute("required", 'false()');
        }

        return $bindNode;
    }

    private function setModelNode($field, $formNode, $instanceId, $extra)
    {
        $fieldNode = $formNode->addChild($this->Field->alias(), $extra['default_value'], NS_OE);
        $fieldNode->addAttribute("id", $field->field_id);

        return $fieldNode;
    }

    private function getRef($instanceId, $references=[]) {
        $ref = "instance('" . $instanceId . "')";
        if (!empty($references)) {
            foreach ($references as $reference) {
                $ref .= "/$reference";
            }
        }

        return $ref;
    }

    private function twentyFourHourFormat($value)
    {
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

    private function deleteExpiredResponse()
    {
        $SurveyResponses = TableRegistry::get('Survey.SurveyResponses');
        $expiryDate = new Time();
        $expiryDate->subDays(3);
        $SurveyResponses->deleteAll([
            $SurveyResponses->aliasField('created <') => $expiryDate
        ]);
    }

    private function addResponse($xmlResponse)
    {
        $SurveyResponses = TableRegistry::get('Survey.SurveyResponses');
            $responseData = [
            'id' => Text::uuid(),
            'response' => $xmlResponse
        ];

        $responseEntity = $SurveyResponses->newEntity($responseData);
        if (!$SurveyResponses->save($responseEntity)) {
            $this->log($responseEntity->errors(), 'debug');
        }
    }
}
