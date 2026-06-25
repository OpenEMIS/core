<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyForms;
use App\Models\InstitutionSurveys;
use App\Models\SurveyRules;
use App\Models\SurveyFormQuestions;
use App\Models\SurveyTableColumns;
use App\Models\SurveyTableRows;
use App\Models\SurveyResponse;
use App\Models\Institutions;
use App\Models\SecurityUsers;
use App\Models\SurveyFormFilter;
use App\Models\WorkflowModel;
use App\Models\Workflows;
use App\Models\WorkflowFilters;
use App\Models\SurveyStatusPeriods;
use App\Models\InstitutionSurveyAnswers;
use App\Models\InstitutionSurveyTableCells;
use App\Models\SurveyQuestionChoices;
use App\Models\CustomModules;
use App\Models\InstitutionStudentSurvey;
use App\Models\SurveyQuestion;
use App\Models\AcademicPeriod;


define("NS_XHTML", "http://www.w3.org/1999/xhtml");
define("NS_XF", "http://www.w3.org/2002/xforms");
define("NS_EV", "http://www.w3.org/2001/xml-events");
define("NS_XSD", "http://www.w3.org/2001/XMLSchema");
define("NS_OE", "https://www.openemis.org");

class SurveyRepository extends Controller
{
    public function getSurveys($request)
    {
        try {
            $params = $request->all();

            $todayDate = date('Y-m-d');
            $todayTimestamp = date('Y-m-d', strtotime($todayDate));

            $surveys = SurveyForms::select('survey_forms.*')
                        ->with('customModule')
                        ->join('survey_statuses', 'survey_statuses.survey_form_id', '=', 'survey_forms.id')
                        ->where('date_disabled', '>=', $todayTimestamp)
                        ->where('date_enabled', '<=', $todayTimestamp);


            $moduleOptions = CustomModules::where('visible', 1)->where('parent_id', 0)->pluck('model', 'id')->toArray();

            $selectedModule = isset($options['module']) ? $options['module'] : key($moduleOptions);
            
            $surveys = $surveys->where('custom_module_id', $selectedModule);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $surveys = $surveys->orderBy($col, $orderBy);
            }
            
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $surveys->paginate($limit)->toArray();
            } else {
                $list['data'] = $surveys->get()->toArray();
            }
            
            
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }


    public function downloadXform($request, $surveyFormId)
    {
        try {
            $param = $request->all();
            $surveyForm = SurveyForms::where('id', $surveyFormId)->first();
            $instanceId = 'xform';
            $fields = $this->getFields($surveyFormId);
            

            $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
                <html
                    xmlns="' . NS_XHTML . '"
                    xmlns:xf="' . NS_XF . '"
                    xmlns:ev="' . NS_EV . '"
                    xmlns:xsd="' . NS_XSD . '"
                    xmlns:oe="' . NS_OE . '">
                </html>';


            //Creating SimpleXML Object
            $xml = new \SimpleXMLElement($xmlstr);

            //Setting the newsPagePrefix attribute and its value to the news node
            //$newsXML->addAttribute('newsPagePrefix', 'Times of India');

            $headNode = $xml->addChild("head", null, NS_XHTML);

            $bodyNode = $xml->addChild("body", null, NS_XHTML);
            $headNode->addChild("title", $surveyForm->name, NS_XHTML);
            $metaNode = $headNode->addChild("meta", null, NS_XHTML);
            $metaNode->addAttribute("name", "description");
            $metaNode->addAttribute("content", $surveyForm->description);
            $modelNode = $headNode->addChild("model", null, NS_XF);

            $instanceNode = $modelNode->addChild("instance", null, NS_XF);
            $instanceNode->addAttribute("id", $instanceId);

            $formNode = $instanceNode->addChild('SurveyForms', null, NS_OE);
            $formNode->addAttribute("id", $surveyFormId);


            // set fixed Institutions Field
            $references = ['SurveyForms', 'Institutions'];

            $formNode->addChild('Institutions', null, NS_OE);
            $fieldNode = $bodyNode->addChild("input", null, NS_XF);
            $fieldNode->addAttribute("ref", $this->getRef($instanceId, $references));
            $fieldNode->addAttribute("oe-type", "string");
            $fieldNode->addChild("label", "Institution Code", NS_XF);


            $this->setBindNode($modelNode, $instanceId, $references, ['type' => 'string', 'required' => true]);

            // set fixed Academic Periods Field
            $references = ['SurveyForms', 'AcademicPeriods'];

            $formNode->addChild('AcademicPeriods', null, NS_OE);
            $fieldNode = $bodyNode->addChild("select1", null, NS_XF);
            $fieldNode->addAttribute("ref", $this->getRef($instanceId, $references));
            $fieldNode->addAttribute("oe-type", "integer");
            $fieldNode->addAttribute("oe-dependency", $this->getRef($instanceId, ['SurveyForms', 'Institutions']));
            $fieldNode->addChild("label", "Academic Period", NS_XF);

            $todayDate = date("Y-m-d");
            
            $periodListResults = SurveyForms::join('survey_statuses', 'survey_statuses.survey_form_id', '=', 'survey_forms.id')
                    ->join('survey_status_periods', 'survey_status_periods.survey_status_id', '=', 'survey_statuses.id')
                    ->join('academic_periods', 'academic_periods.id', '=', 'survey_status_periods.academic_period_id')
                    ->where('survey_forms.id', $surveyFormId)
                    ->where('survey_statuses.date_disabled', '>=', $todayDate)
                    ->select('academic_periods.id as academic_period_id', 'academic_periods.name as academic_period_name')
                    ->groupBy('academic_periods.id')
                    ->get();
            
            if (!empty($periodListResults)) {
                $periodOptions = $periodListResults->toArray();
                
                foreach ($periodOptions as $key => $period) {
                    $itemNode = $fieldNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($period['academic_period_name']), NS_XF);
                    $itemNode->addChild("value", htmlspecialchars($period['academic_period_id']), NS_XF);
                }
            }
            //dd($modelNode, $instanceId, $references);
            $this->setBindNode($modelNode, $instanceId, $references, ['type' => 'integer', 'required' => true]);

            // used to build validation rules
            $schemaNode = $modelNode->addChild("schema", null, NS_XSD);


            $rules = SurveyRules::where('survey_form_id', $surveyFormId)->get()->toArray();
            
            //$rules = new ArrayObject($rules);
            
            $sectionName = null;
            foreach ($fields as $key => $field) {

                $extra = [];
                $extra['index'] = $key + 1;
                $extra['subIndex'] = 0;
                $extra['head'] = $headNode;
                $extra['body'] = $bodyNode;
                $extra['model'] = $modelNode;
                $extra['instance'] = $instanceNode;
                $extra['schema'] = $schemaNode;
                $extra['form'] = $formNode;
                $extra['hint'] = null;
                $extra['constraint'] = null;

                $extra['references'] = ['SurveyForms', "SurveyQuestions[".$extra['index']."]"];
                $extra['default_value'] = null; // to handle default value for dropdown

                // For relevancy
                $extra['field_id'] = $field['field_id'];
                $extra['rules'] = $rules;
                
                if (is_null($sectionName)) {
                    $parentNode = $bodyNode;
                }

                // Section
                if ($field['section_name'] != $sectionName) {
                    $sectionName = $field['section_name'];
                    $sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
                    $sectionBreakNode->addAttribute("ref", $field['form_id'] . '_' . $field['field_id']);
                    $sectionBreakNode->addChild("label", htmlspecialchars($sectionName, ENT_QUOTES), NS_XF);

                    $parentNode = $sectionBreakNode;

                }
                // End
                $fieldTypeFunction = strtolower($field['field_type']);
                
                if (method_exists($this, $fieldTypeFunction)) {
                    // here to add logic of xform
                    $this->$fieldTypeFunction($field, $parentNode, $instanceId, $extra);

                    // set to null to skip adding into Head > Model > Instance (e.g. for table and repeater)
                    if (!is_null($extra['form'])) {
                        $this->setModelNode($field, $extra['form'], $instanceId, $extra);
                    }
                }
                
            }

            //return $xml;
            return $xml->asXml();
        } catch (\Exception $e) {
            Log::error(
                'Failed to download survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );  
            return $this->sendErrorResponse('Failed to download survey xform.');
        }
    }


    private function staff_list($field, $parentNode, $instanceId, $extra)
    {   
        $extra['tagName'] = 'staff_list';
        $extra['is_staff_list_field'] = 'yesss';
        $extra['bindType'] = 'string';
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function student_list($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'student_list';
        $extra['is_student_list_field'] = 'yesss';
        $extra['bindType'] = 'string';
        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }

    private function note($field, $parentNode, $instanceId, $extra)
    {
        $noteBreakNode = $parentNode->addChild('group', null, NS_XF);
        $noteBreakNode->addAttribute("ref", $field['field_id']);
        $noteBreakNode->addChild("label", htmlspecialchars($field['default_name'], ENT_QUOTES), NS_XF);
        $noteBreakNode->addAttribute("oe-type", "note");
        $noteBreakNode->addChild("p", htmlspecialchars($field['default_description'], ENT_QUOTES), NS_XHTML);
    }


    private function getRef($instanceId, $references=[])
    {
        $ref = "instance('" . $instanceId . "')";
        if (!empty($references)) {
            foreach ($references as $reference) {
                $ref .= "/$reference";
            }
        }

        return $ref;
    }


    private function setBindNode($modelNode, $instanceId, $references=[], $attr=[])
    {
        $bindType = array_key_exists('type', $attr) ? $attr['type'] : 'string';
        $required = array_key_exists('required', $attr) ? $attr['required'] : false;
        $constraint = array_key_exists('constraint', $attr) ? $attr['constraint'] : null;

        //dd("setBindNode: ", $bindType, $required, $constraint);
        $bindNode = $modelNode->addChild("bind", null, NS_XF);
        $bindNode->addAttribute("ref", $this->getRef($instanceId, $references));
        $bindNode->addAttribute("type", $bindType);

        if ($required) {
            $bindNode->addAttribute("required", 'true()');
        } else {
            $bindNode->addAttribute("required", 'false()');
        }

        if (!is_null($constraint)) {
            // <xf:bind constraint=". &gt;= 5 &amp;&amp; . &lt;= 15" ref="instance('xform')/SurveyForms/SurveyQuestions[1]" required="false()" type="integer"/>
            $bindNode->addAttribute("constraint", $constraint);
        }

        if (isset($attr['rules'])) {
            $questionId = $attr['field_id'];
            $attr['rules']['dependent_question_mapping'][$questionId] = $attr['references'][1];
            if (isset($attr['rules'][$questionId])) {
                $rules = $attr['rules'][$questionId];
                $relevancy = '';
                $tmp = [];
                foreach ($rules as $key => $options) {
                    $dependentQuestion = $attr['rules']['dependent_question_mapping'][$key];
                    $options = json_decode($options);
                    foreach ($options as $option) {
                        $tmp[] = '../'.$dependentQuestion.' eq '. $option;
                    }
                }
                $relevancy = implode(' &#38;&#38; ', $tmp);
                $bindNode->addAttribute("relevant", $relevancy);
            }
        }

        return $bindNode;
    }



    private function getFields($id)
    {
        $list = SurveyFormQuestions::join('survey_questions', 'survey_questions.id', '=', 'survey_forms_questions.survey_question_id')
                ->select('survey_forms_questions.survey_form_id as form_id',
                    'survey_forms_questions.survey_question_id as field_id',
                    'survey_forms_questions.section as section_name',
                    'survey_forms_questions.name',
                    'survey_forms_questions.is_mandatory',
                    'survey_forms_questions.is_unique',
                    'survey_questions.field_type',
                    'survey_questions.name as default_name',
                    'survey_questions.description as default_description',
                    'survey_questions.is_mandatory as default_is_mandatory',
                    'survey_questions.is_unique as default_is_unique',
                    'survey_questions.params'
                )
                ->orderBy('survey_forms_questions.order', 'ASC')
                ->where('survey_forms_questions.survey_form_id', $id)
                ->get()
                ->toArray();

        return $list;
    }


    private function setModelNode($field, $formNode, $instanceId, $extra)
    {
        $fieldNode = $formNode->addChild('SurveyQuestions', $extra['default_value'], NS_OE);
        $fieldNode->addAttribute("id", $field['field_id']);

        return $fieldNode;
    }


    private function setCommonNode($field, $parentNode, $instanceId, $extra)
    {   
        $tagName = array_key_exists('tagName', $extra) ? $extra['tagName'] : 'input';
        $bindType = array_key_exists('bindType', $extra) ? $extra['bindType'] : 'string';

        $this->setBodyNode($field, $parentNode, $instanceId, $tagName, $extra);
        $extra['type'] = $bindType;
        $extra['required'] = $field['default_is_mandatory'];
        
        if (isset($extra['constraint']) && empty($extra['constraint'])) {
            unset($extra['constraint']);
        }
        $this->setBindNode($extra['model'], $instanceId, $extra['references'], $extra);
    }

    private function table($field, $parentNode, $instanceId, $extra)
    {
        try {
            // To nested table inside xform group
            $tableBreakNode = $parentNode->addChild('group', null, NS_XF);
            $tableBreakNode->addAttribute("ref", $field['field_id']);
            $tableBreakNode->addChild("label", htmlspecialchars($field['default_name'], ENT_QUOTES), NS_XF);
            $tableBreakNode->addAttribute("oe-type", "table");
            // End

            $tableNode = $tableBreakNode->addChild("table", null, NS_XHTML);
            $tableNode->addAttribute("ref", $this->getRef($instanceId, $extra['references']));
            $tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
            $tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
            $repeatNode = $tableBody->addChild("repeat", null, NS_XF);
            $repeatNode->addAttribute("ref", $this->getRef($instanceId, array_merge($extra['references'], ['SurveyTableRows'])));
            $tbodyRow = $repeatNode->addChild("tr", null, NS_XHTML);
            
            $tableColumnResults = SurveyTableColumns::select('id',
                    'name',
                    'order',
                    'visible',
                    'survey_question_id',
                    'modified_user_id',
                    'modified',
                    'created_user_id',
                    'created'
                )
                ->where('visible', 1)
                ->where('survey_question_id', $field['field_id'])
                ->get();


            $tableRowResults = SurveyTableRows::select('id',
                    'name',
                    'order',
                    'visible',
                    'survey_question_id',
                    'modified_user_id',
                    'modified',
                    'created_user_id',
                    'created'
                )
                ->where('visible', 1)
                ->where('survey_question_id', $field['field_id'])
                ->get();
            

            if (!empty($tableColumnResults) && !empty($tableRowResults)) {

                $tableColumns = $tableColumnResults->toArray();
                $tableRows = $tableRowResults->toArray();

                $fieldNode = $this->setModelNode($field, $extra['form'], $instanceId, $extra);

                $extra['form'] = null;  // set to null to skip adding into Head > Model > Instance

                // start validation constraint
                $inputType = 'string';
                $constraint = null;
                $validationType = null;
                $validations = [];
                $validationHint = '';

                if (isset($field['params']) && !empty($field['params'])) {

                    $params = json_decode($field['params'], true);

                    if (array_key_exists('number', $params)) {
                        $inputType = 'integer';

                        $validationRules = $params['number'];
                        if (is_array($validationRules)) {
                            foreach ($validationRules as $key => $value) {
                                switch ($key) {
                                    case 'min_value':
                                        $validationType = $key;
                                        $validations['min_inclusive'] = $value;
                                        /*$validationHint = $this->Field->getMessage('CustomField.number.minValue', ['sprintf' => $value]);*/
                                        $validationHint = '';
                                        break;
                                    case 'max_value':
                                        $validationType = $key;
                                        $validations['max_inclusive'] = $value;
                                        /*$validationHint = $this->Field->getMessage('CustomField.number.maxValue', ['sprintf' => $value]);*/
                                        $validationHint = '';
                                        break;
                                    case 'range':
                                        $validationType = $key;
                                        $validations['min_inclusive'] = $value['lower'];
                                        $validations['max_inclusive'] = $value['upper'];
                                        /*$validationHint = $this->Field->getMessage('CustomField.number.range', ['sprintf' => [$value['lower'], $value['upper']]]);*/
                                        $validationHint = '';
                                        break;
                                }
                            }
                        }
                    } elseif (array_key_exists('decimal', $params)) {
                        $inputType = 'decimal';

                        $generateRangeValues = function($length, $precision = 0) {
                            $range = str_repeat('9', $length);
                            if ($precision > 0) {
                                $range .= '.' . str_repeat('9', $precision);
                            }
                            return $range;
                        };

                        $validationRules = $params['decimal'];
                        $length = $validationRules['length'];
                        $precision = $validationRules['precision'];

                        // for positive values
                        $validations['min_inclusive'] = 0;
                        $validations['max_inclusive'] = $generateRangeValues($length, $precision);

                        if ($precision == 0) {
                            $validationType = 'total_digits';
                            /*$validationHint = $this->Field->getMessage('CustomField.decimal.length', ['sprintf' => [$length]]);*/
                            $validationHint = '';
                        } else {
                            $validationType = 'fraction_digits';
                            $validations['fraction_digits'] = $precision;
                            /*$validationHint = $this->Field->getMessage('CustomField.decimal.precision', ['sprintf' => [$length, $precision]]);*/
                            $validationHint = '';
                        }
                    }
                }

                if (!is_null($validationType)) {

                    $bindType = $inputType.Inflector::camelize($validationType).$extra['index'];

                    // introduce subIndex to handle question inside repeater has validation
                    $subIndex = $extra['subIndex'];
                    if (!empty($subIndex)) {
                        $bindType .= "_$subIndex";
                    }
                    // End

                    $schemaNode = $extra['schema'];
                    $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
                    $simpleType->addAttribute("name", $bindType);

                    $restriction = $simpleType->addChild('restriction', null, NS_XSD);
                    $restriction->addAttribute("base", "xf:".$inputType);

                    foreach ($validations as $key => $value) {
                        $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                        $condition->addAttribute("value", $value);
                    }
                } else {
                    $bindType = $inputType;
                }

                $extra['type'] = $bindType;
                $extra['hint'] = !empty($validationHint) ? $validationHint : null;
                // end validation constraint
                
                foreach ($tableRows as $row => $tableRow) {
                    $rowNode = $fieldNode->addChild('SurveyTableRows', null, NS_OE);
                    $rowNode->addAttribute("id", $tableRow['id']);
                    
                    foreach ($tableColumns as $col => $tableColumn) {
                        if ($col == 0) {
                            $columnNode = $rowNode->addChild("SurveyTableColumns" . $col, htmlspecialchars($tableRow['name'], ENT_QUOTES), NS_OE);
                            $columnNode->addAttribute("id", $col);
                            $cellType = 'output';
                            $cellLabel = $tableRow['name'];
                            $cellHint = null;
                        } else {
                            $columnNode = $rowNode->addChild("SurveyTableColumns" . $col, null, NS_OE);
                            $columnNode->addAttribute("id", $tableColumn['id']);
                            $cellType = 'input';
                            $cellLabel = $tableRow['name'];
                            $cellHint = !is_null($extra['hint']) ? $extra['hint'] : null;
                        }

                        if ($row == 0) {
                            $tableHeader->addChild("th", htmlspecialchars($tableColumn['name'], ENT_QUOTES), NS_XHTML);
                            $tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
                            $tbodyCell = $tbodyColumn->addChild($cellType, null, NS_XF);
                            $tbodyCell->addAttribute("ref", $this->getRef($instanceId, array_merge($extra['references'], ["SurveyTableColumns".$col])));

                            $tbodyCell->addChild("label", htmlspecialchars($cellLabel, ENT_QUOTES), NS_XF);
                            if (!empty($cellHint)) {
                                $tbodyCell->addChild("hint", htmlspecialchars($cellHint, ENT_QUOTES), NS_XF);
                            }

                            $this->setBindNode($extra['model'], $instanceId, array_merge($extra['references'], ["SurveyTableColumns".$col]), $extra);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }

        
    }



    private function text($field, $parentNode, $instanceId, $extra)
    {
        try {
            $bindType = 'string';

            $validationType = null;
            $validations = [];
            $validationHint = '';
            if (isset($field['params']) && !empty($field['params'])) {
                $params = json_decode($field['params'], true);

                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'min_length':
                            $validationType = $key;
                            $validations[$validationType] = $value;
                            /*$validationHint = $this->Field->getMessage('CustomField.text.minLength', ['sprintf' => $value]);*/
                            $validationHint = '';
                            break;
                        case 'max_length':
                            $validationType = $key;
                            $validations[$validationType] = $value;
                            /*$validationHint = $this->Field->getMessage('CustomField.text.maxLength', ['sprintf' => $value]);*/

                            $validationHint = '';
                            break;
                        case 'range':
                            $validationType = $key;
                            if (array_key_exists('lower', $value) && array_key_exists('upper', $value)) {
                                $validations['min_length'] = $value['lower'];
                                $validations['max_length'] = $value['upper'];
                                /*$validationHint = $this->Field->getMessage('CustomField.text.range', ['sprintf' => [$value['lower'], $value['upper']]]);*/

                                $validationHint = '';
                            }
                    }
                }
            }

            if (!is_null($validationType)) {
                $bindType = "string".Inflector::camelize($validationType).$extra['index'];

                // introduce subIndex to handle question inside repeater has validation
                $subIndex = $extra['subIndex'];
                if (!empty($subIndex)) {
                    $bindType .= "_$subIndex";
                }
                // End

                $schemaNode = $extra['schema'];
                $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
                $simpleType->addAttribute("name", $bindType);

                $restriction = $simpleType->addChild('restriction', null, NS_XSD);
                $restriction->addAttribute("base", "xf:string");

                foreach ($validations as $key => $value) {
                    $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                    $condition->addAttribute("value", $value);
                }
            }

            $extra['tagName'] = 'input';
            $extra['bindType'] = $bindType;
            $extra['hint'] = !empty($validationHint) ? $validationHint : null;
            $this->setCommonNode($field, $parentNode, $instanceId, $extra);
        } catch(\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
        
    }



    private function number($field, $parentNode, $instanceId, $extra)
    {
        try {
            $bindType = 'integer';
            $constraint = null;
            $validationType = null;
            $validations = [];
            $validationHint = '';

            if (isset($field['params']) && !empty($field['params'])) {
                $params = json_decode($field['params'], true);

                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'min_value':
                            $validationType = $key;
                            $validations['min_inclusive'] = $value;
                            /*$validationHint = $this->Field->getMessage('CustomField.number.minValue', ['sprintf' => $value]);*/
                            $validationHint = '';
                            break;
                        case 'max_value':
                            $validationType = $key;
                            $validations['max_inclusive'] = $value;
                            /*$validationHint = $this->Field->getMessage('CustomField.number.maxValue', ['sprintf' => $value]);*/
                            $validationHint = '';
                            break;
                        case 'range':
                            $validationType = $key;
                            $validations['min_inclusive'] = $value['lower'];
                            $validations['max_inclusive'] = $value['upper'];
                            /*$validationHint = $this->Field->getMessage('CustomField.number.range', ['sprintf' => [$value['lower'], $value['upper']]]);*/
                            $validationHint = '';
                            break;
                    }
                }
            }

            if (!is_null($validationType)) {
                $bindType = "integer".Inflector::camelize($validationType).$extra['index'];

                // introduce subIndex to handle question inside repeater has validation
                $subIndex = $extra['subIndex'];
                if (!empty($subIndex)) {
                    $bindType .= "_$subIndex";
                }
                // End

                $schemaNode = $extra['schema'];
                $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
                $simpleType->addAttribute("name", $bindType);

                $restriction = $simpleType->addChild('restriction', null, NS_XSD);
                $restriction->addAttribute("base", "xf:integer");

                foreach ($validations as $key => $value) {
                    $condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                    $condition->addAttribute("value", $value);
                }
            }

            $extra['tagName'] = 'input';
            $extra['bindType'] = $bindType;
            $extra['hint'] = !empty($validationHint) ? $validationHint : null;
            $this->setCommonNode($field, $parentNode, $instanceId, $extra);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }


    private function decimal($field, $parentNode, $instanceId, $extra)
    {
        try {
            $bindType = 'decimal';
            $constraint = null;
            $validationType = null;
            $validations = [];
            $validationHint = '';

            $generateRangeValues = function($length, $precision = 0) {
                $range = str_repeat('9', $length);
                if ($precision > 0) {
                    $range .= '.' . str_repeat('9', $precision);
                }
                return $range;
            };

            if (isset($field['params']) && !empty($field['params'])) {
                $params = json_decode($field['params'], true);

                $length = $params['length'];
                $precision = $params['precision'];

                // for positive values
                $validations['min_inclusive'] = 0;
                $validations['max_inclusive'] = $generateRangeValues($length, $precision);

                if ($precision == 0) {
                    $validationType = 'total_digits';
                    /*$validationHint = $this->Field->getMessage('CustomField.decimal.length', ['sprintf' => [$length]]);*/
                    $validationHint = '';
                } else {
                    $validationType = 'fraction_digits';
                    $validations['fraction_digits'] = $precision;
                    /*$validationHint = $this->Field->getMessage('CustomField.decimal.precision', ['sprintf' => [$length, $precision]]);*/
                    $validationHint = '';
                }
            }

            if (!is_null($validationType)) {
                //$bindType = "decimal".Inflector::camelize($validationType).$extra['index'];
                $bindType = "decimal".Str::camel($validationType).$extra['index'];
                
                // introduce subIndex to handle question inside repeater has validation
                $subIndex = $extra['subIndex'];
                if (!empty($subIndex)) {
                    $bindType .= "_$subIndex";
                }
                // End

                $schemaNode = $extra['schema'];
                $simpleType = $schemaNode->addChild('simpleType', null, NS_XSD);
                $simpleType->addAttribute("name", $bindType);

                $restriction = $simpleType->addChild('restriction', null, NS_XSD);
                $restriction->addAttribute("base", "xf:decimal");

                foreach ($validations as $key => $value) {
                    //$condition = $restriction->addChild(Inflector::variable($key), null, NS_XSD);
                    $condition = $restriction->addChild(Str::camel($key), null, NS_XSD);
                    $condition->addAttribute("value", $value);
                }
            }

            $extra['tagName'] = 'input';
            $extra['bindType'] = $bindType;
            $extra['hint'] = !empty($validationHint) ? $validationHint : null;
            $this->setCommonNode($field, $parentNode, $instanceId, $extra);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
        
    }


    private function textarea($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'textarea';
        $extra['bindType'] = 'string';

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }


    private function dropdown($field, $parentNode, $instanceId, $extra)
    {
        try {
            $fieldOptionResults = SurveyQuestionChoices::select(
                'id', 
                'name', 
                'is_default', 
                'visible', 
                'order', 
                'survey_question_id', 
                'modified_user_id', 
                'modified', 
                'created_user_id', 
                'created'
            )
            ->where('visible', 1)
            ->where('survey_question_id', $field['field_id'])
            ->orderBy('order', 'ASC')
            ->get();



            $dropdownNode = $this->setBodyNode($field, $parentNode, $instanceId, 'select1', $extra);
            if (!empty($fieldOptionResults)) {
                $fieldOptions = $fieldOptionResults->toArray();
                foreach ($fieldOptions as $fieldOption) {
                    if ($fieldOption['is_default']) {
                        // to set default value in Head > Model > instance e.g. <oe:SurveyQuestions id='5'>default value here</oe:SurveyQuestions>
                        $extra['default_value'] = $fieldOption['id'];
                    }

                    $itemNode = $dropdownNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($fieldOption['name'], ENT_QUOTES), NS_XF);
                    $itemNode->addChild("value", $fieldOption['id'], NS_XF);
                }
            }

            $extra['type'] = 'integer';
            $extra['required'] = $field['default_is_mandatory'];

            $this->setBindNode($extra['model'], $instanceId, $extra['references'], $extra);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
        
    }


    private function checkbox($field, $parentNode, $instanceId, $extra)
    {
        try {
            $fieldOptionResults = SurveyQuestionChoices::select(
                'id', 
                'name', 
                'is_default', 
                'visible', 
                'order', 
                'survey_question_id', 
                'modified_user_id', 
                'modified', 
                'created_user_id', 
                'created'
            )
            ->where('visible', 1)
            ->where('survey_question_id', $field['field_id'])
            ->orderBy('order', 'ASC')
            ->get();

            $checkboxNode = $this->setBodyNode($field, $parentNode, $instanceId, 'select', $extra);
            if (!empty($fieldOptionResults)) {
                $fieldOptions = $fieldOptionResults->toArray();
                foreach ($fieldOptions as $fieldOption) {
                    $itemNode = $checkboxNode->addChild("item", null, NS_XF);
                    $itemNode->addChild("label", htmlspecialchars($fieldOption['name'], ENT_QUOTES), NS_XF);
                    $itemNode->addChild("value", $fieldOption['id'], NS_XF);
                }
            }

            $extra['type'] = 'integer';
            $extra['required'] = $field['default_is_mandatory'];

            $this->setBindNode($extra['model'], $instanceId, $extra['references'], $extra);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }

        
    }


    private function date($field, $parentNode, $instanceId, $extra)
    {
        try {
            $constraint = null;
            $validationHint = '';
            if (isset($field['params']) && !empty($field['params'])) {
                $params = json_decode($field['params'], true);

                $startDate = array_key_exists('start_date', $params) ? $params['start_date'] : null;
                $endDate = array_key_exists('end_date', $params) ? $params['end_date'] : null;

                if (!is_null($startDate) && !is_null($endDate)) {
                    $constraint = ". >= '".$startDate."'' && ".". <= '".$endDate."'";
                    /*$validationHint = $this->Field->getMessage('CustomField.date.between', ['sprintf' => [$startDate, $endDate]]);*/
                    $validationHint = '';
                } elseif (!is_null($startDate)) {
                    $constraint = ". >= '$startDate'";
                    /*$validationHint = $this->Field->getMessage('CustomField.date.earlier', ['sprintf' => $startDate]);*/
                    $validationHint = '';
                } elseif (!is_null($endDate)) {
                    $constraint = ". <= '$endDate'";
                    /*$validationHint = $this->Field->getMessage('CustomField.date.later', ['sprintf' => $endDate]);*/
                    $validationHint = '';
                }
            }

            $extra['tagName'] = 'input';
            $extra['bindType'] = 'date';
            $extra['hint'] = !empty($validationHint) ? $validationHint : null;
            $extra['constraint'] = !empty($constraint) ? $constraint : null;

            $this->setCommonNode($field, $parentNode, $instanceId, $extra);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
        
    }


    private function time($field, $parentNode, $instanceId, $extra)
    {
        try {
            $constraint = null;
            $validationHint = '';
            if (isset($field['params']) && !empty($field['params'])) {
                $params = json_decode($field['params'], true);

                $startTime = array_key_exists('start_time', $params) ? $params['start_time'] : null;
                $endTime = array_key_exists('end_time', $params) ? $params['end_time'] : null;

                if (!is_null($startTime) && !is_null($endTime)) {
                    $constraint = ". >= '".$this->twentyFourHourFormat($startTime)."'' && ".". <= '".$this->twentyFourHourFormat($endTime)."'";
                    /*$validationHint = $this->Field->getMessage('CustomField.time.between', ['sprintf' => [$startTime, $endTime]]);*/
                    $validationHint = '';
                } elseif (!is_null($startTime)) {
                    $constraint = ". >= '".$this->twentyFourHourFormat($startTime)."'";
                    /*$validationHint = $this->Field->getMessage('CustomField.time.earlier', ['sprintf' => $startTime]);*/
                    $validationHint = '';
                } elseif (!is_null($endTime)) {
                    $constraint = ". <= '".$this->twentyFourHourFormat($endTime)."'";
                    /*$validationHint = $this->Field->getMessage('CustomField.time.later', ['sprintf' => $endTime]);*/
                    $validationHint = '';
                }
            }

            $extra['tagName'] = 'input';
            $extra['bindType'] = 'time';
            $extra['hint'] = !empty($validationHint) ? $validationHint : null;
            $extra['constraint'] = !empty($constraint) ? $constraint : null;

            $this->setCommonNode($field, $parentNode, $instanceId, $extra);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
        
    }

    private function coordinates($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'input';
        $extra['bindType'] = 'geopoint';

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }


    private function file($field, $parentNode, $instanceId, $extra)
    {
        $extra['tagName'] = 'upload';
        $extra['bindType'] = 'file';

        $this->setCommonNode($field, $parentNode, $instanceId, $extra);
    }


    private function repeater($field, $parentNode, $instanceId, $extra)
    {
        try {
            $repeaterNode = $this->setBodyNode($field, $parentNode, $instanceId, 'repeat', $extra);

            $fieldNode = $this->setModelNode($field, $extra['form'], $instanceId, $extra);
            $repeatNode = $fieldNode->addChild('RepeatBlock', null, NS_OE);
            $extra['form'] = null;  // set to null to skip adding into Head > Model > Instance

            $formId = null;
            // Get Survey Form ID
            if (isset($field['params']) && !empty($field['params'])) {
                $params = json_decode($field['params'], true);
                if (array_key_exists('survey_form_id', $params)) {
                    $formId = $params['survey_form_id'];
                }
            }

            if (!is_null($formId)) {
                $fields = $this->getFields($formId);

                if (!empty($fields)) {
                    foreach ($fields as $key => $field) {
                        $index = $key + 1;
                        $extra['subIndex'] = $index;
                        // must reset to null
                        $extra['default_value'] = null;
                        $extra['references'] = ['SurveyForms', "SurveyQuestions[".$extra['index']."]", 'RepeatBlock', "SurveyQuestions".$index];
                        $extra['hint'] = null; // reset hint

                        $fieldTypeFunction = strtolower($field['field_type']);
                        if (method_exists($this, $fieldTypeFunction)) {
                            $this->$fieldTypeFunction($field, $repeaterNode, $instanceId, $extra);

                            // add to Head > Model > Instance > RepeatBlock here
                            $repeatBlockNode = $repeatNode->addChild("SurveyQuestions".$index, $extra['default_value'], NS_OE);
                            $repeatBlockNode->addAttribute("id", $field['field_id']);
                        }
                    }
                }
            } else {
                // Survey Form ID not found
                Log::write('debug', 'Repeater Survey Form ID is not configured.');
            }
            // End
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
        
    }


    private function setBodyNode($field, $parentNode, $instanceId, $fieldType, $extra)
    {
        $fieldNode = $parentNode->addChild($fieldType, null, NS_XF);
        $fieldNode->addAttribute("ref", $this->getRef($instanceId, $extra['references']));
        $fieldNode->addChild("label", htmlspecialchars($field['default_name'], ENT_QUOTES), NS_XF);

        if (!empty($extra['hint'])) {
            // <xf:hint>Text should be at least 10 characters</xf:hint>
            $fieldNode->addChild("hint", htmlspecialchars($extra['hint'], ENT_QUOTES), NS_XF);
        }

        return $fieldNode;
    }



    public function uploadXform(Request $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $xml = file_get_contents('php://input');

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $formAlias = 'SurveyForms';
                $fieldAlias = 'SurveyQuestions';
                $xmlResponse = $xml;
                //dd("xmlResponse", $xmlResponse);

                $this->deleteExpiredResponse();
                $this->addResponse($xmlResponse);

                $xmlResponse = str_replace("xf:", "", $xmlResponse);
                $xmlResponse = str_replace("oe:", "", $xmlResponse);

                $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlResponse;

                $xml = new \SimpleXMLElement($xmlstr);
                
                $periodId = $xml->{$formAlias}->AcademicPeriods->__toString();
                $formId = $xml->{$formAlias}->attributes()->id->__toString();
                $institutionCode = $xml->{$formAlias}->Institutions->__toString();
                
                // checking institutionId
                $institutionResult = Institutions::where(DB::raw('lower(code)'), strtolower($institutionCode))->first();
                
                if (empty($institutionResult)) {
                    return 6; //Invalid institution code
                }

                $institutionId = $institutionResult->id;
                // end of check for institutionId

                //$userId = 2;
                $user = JWTAuth::user();
                $userId = $user->id;
                
                //$userEntity = SecurityUsers::where('id', $userId)->first();
                $userEntity = $user;
                $permissions = checkAccess();
                
                // checking of access only if the user is not super admin    
                if ($userEntity->super_admin == 0) {
                    if(!in_array($institutionId, $permissions['institutionIds'])){
                        return 5; //not allowed.
                    }
                } 

                // build survey records than check if the record don't exist, it is a invalid combination
                $buildSurveyRecords = $this->buildSurveyRecords($institutionId, $formId, $periodId);
                

                $institutionSurveyResults = InstitutionSurveys::with('status')->where('survey_form_id', $formId)->where('institution_id', $institutionId)->where('academic_period_id', $periodId)->first();

                if(empty($institutionSurveyResults)){
                    return 2; //'No record found for institution for the form for the period'
                }
                $institutionSurveyEntity = $institutionSurveyResults;
                $institutionSurveyId = $institutionSurveyEntity->id;
                $institutionSurveyStatusId = $institutionSurveyEntity->status_id;

                // if the survey is expired
                if ($institutionSurveyStatusId == '-1') {
                    return 3; //'Survey is already expired'
                }

                // if the survey is done
                if (!is_null($institutionSurveyEntity->status) && $institutionSurveyEntity->status->category == 3) {
                    return 4; //'Survey is already completed'
                }
                
                $update = InstitutionSurveys::where('survey_form_id', $formId)->where('institution_id', $institutionId)->where('academic_period_id', $periodId)->update(['modified_user_id' => $userId, 'modified' => Carbon::now()->toDateTimeString()]);


                // Delete relevance questions
                $this->deleteQuestionWithRules($formId, $institutionSurveyId);

                $rules = [];
                $rulesData = SurveyRules::where('survey_form_id', $formId)->get();
                foreach($rulesData as $r){
                    
                    $rules[$r->survey_question_id][$r->dependent_question_id] = $r->show_options;

                }
                
                $answers = [];
                $fields = $xml->{$formAlias}->{$fieldAlias};
                
                foreach ($fields as $field) {
                    $fieldId = $field->attributes()->id->__toString();
                    //dd($fieldId);
                    $fieldEntity = DB::table('survey_questions')->where('id', $fieldId)->first();
                    $fieldType = $fieldEntity->field_type??"";
                    $responseValue = urldecode($field->__toString());

                    $fieldTypeFunction = "upload" . ucfirst(strtolower($fieldType));
                    

                    if (method_exists($this, $fieldTypeFunction)) {
                        $responseData = [
                            "institution_survey_id" => $institutionSurveyEntity->id,
                            'survey_question_id' => $fieldId,
                            'created_user_id' => $userId,
                            'created' => Carbon::now()->toDateTimeString()
                        ];

                        $extra = [];

                        //$extra['model'] = "InstitutionSurveyAnswers";
                        $extra['model'] = "institution_survey_answers";
                        //$extra['cellModel'] = "InstitutionSurveyTableCells";
                        $extra['cellModel'] = "institution_survey_table_cells";
                        $extra['data'] = $responseData;
                        $extra['value'] = trim($responseValue);
                        $extra['recordKey'] = "institution_survey_id";
                        $extra['formKey'] = "survey_form_id";
                        $extra['fieldKey'] = "survey_question_id";
                        $extra['fieldEntity'] = $fieldEntity;


                        $questionId = $extra['data']['survey_question_id'];
                        $show = $this->isRelevantQuestion($rules, $questionId, $answers, $responseValue);
                        
                        if ($show) {
                            $this->$fieldTypeFunction($field, $institutionSurveyEntity, $extra);
                        }
                    }
                }

            }

            DB::commit();
            return 1;
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error(
                'Failed to upload survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to upload survey xform.');
        }
    }


    private function isRelevantQuestion($rules, $questionId, $answers, $responseValue)
    {
        $show = true;
        if (isset($rules[$questionId])) {
            $show = false;
            $dependentQuestions = $rules[$questionId];
            //$ans = $answers->getArrayCopy();
            $ans = $answers;
            $intersectKey = array_intersect_key($ans, $dependentQuestions);
            foreach ($intersectKey as $key => $value) {
                $ruleOptions = json_decode($dependentQuestions[$key]);
                if (in_array($value, $ruleOptions)) {
                    $show = true;
                }
            }
        }
        if ($show) {
            $answers[$questionId] = $responseValue;
        }
        return $show;
    }


    private function deleteTableCell($data, $extra)
    {
        $cellModel = $extra['cellModel'];
        $recordKey = $extra['recordKey'];
        $fieldKey = $extra['fieldKey'];
        
        /*$cellModel->deleteAll([
            $cellModel->aliasField($recordKey) => $data[$recordKey],
            $cellModel->aliasField($fieldKey) => $data[$fieldKey]
        ]);*/

        $delete = DB::table($cellModel)->where($recordKey, $data[$recordKey])->where($fieldKey, $data[$fieldKey])->delete();
        
    }



    private function deleteFieldValue($data, $extra)
    {
        $model = $extra['model'];
        $recordKey = $extra['recordKey'];
        $fieldKey = $extra['fieldKey'];
        
        /*$model->deleteAll([
            $model->aliasField($recordKey) => $data[$recordKey],
            $model->aliasField($fieldKey) => $data[$fieldKey]
        ]);*/

        $delete = DB::table($model)->where($recordKey, $data[$recordKey])->where($fieldKey, $data[$fieldKey])->delete();
    }

    private function processUpload($key, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        
        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            $data[$key] = $value;
            
            $this->saveFieldValue($data, $extra);
        }
    }

    private function saveFieldValue($answerData, $extra)
    {
        $model = $extra['model'];

        $answerData['id'] = Str::uuid();
        
        /*$answerEntity = $model->newEntity($answerData);
        if (!$model->save($answerEntity)) {
            Log::write('debug', $answerEntity->errors());
        }*/

        $store = DB::table($model)->insert($answerData);
    }

    private function uploadText($field, $entity, $extra)
    {
        $this->processUpload('text_value', $extra);
    }

    private function uploadTable($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        $fieldEntity = $extra['fieldEntity'];
        
        $cellValueColumn = 'text_value';
        if (isset($fieldEntity->params) && !empty($fieldEntity->params)) {
            $params = json_decode($fieldEntity->params, true);

            if (array_key_exists('number', $params)) {
                $cellValueColumn = 'number_value';
            } elseif (array_key_exists('decimal', $params)) {
                $cellValueColumn = 'decimal_value';
            }
        }
        ;
        $this->deleteTableCell($data, $extra);
        foreach ($field->children() as $row => $rowObj) {

            $rowId = $rowObj->attributes()->id->__toString();
            foreach ($rowObj->children() as $col => $colObj) {

                $colId = $colObj->attributes()->id->__toString();
                if ($colId != 0) {
                    $cellValue = urldecode($colObj->__toString());
                    if (strlen($cellValue) != 0) {
                        $cellData = array_merge($data, [
                            "survey_table_column_id" => $colId,
                            "survey_table_row_id" => $rowId,
                            'text_value' => '',
                            'number_value' => '',
                            'decimal_value' => ''
                        ]);
                        $cellData[$cellValueColumn] = $cellValue;

                        $this->saveTableCell($cellData, $extra);
                    }
                }
            }
        }
    }


    private function uploadNumber($field, $entity, $extra)
    {
        $this->processUpload('number_value', $extra);
    }

    private function uploadDecimal($field, $entity, $extra)
    {
        $this->processUpload('decimal_value', $extra);
    }

    private function uploadTextarea($field, $entity, $extra)
    {
        $this->processUpload('textarea_value', $extra);
    }

    private function uploadDropdown($field, $entity, $extra)
    {
        $this->processUpload('number_value', $extra);
    }


    private function uploadCheckbox($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];

        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            $checkboxValues = explode(" ", $value);
            foreach ($checkboxValues as $checkboxKey => $checkboxValue) {
                $data['number_value'] = $checkboxValue;
                $this->saveFieldValue($data, $extra);
            }
        }
    }


    private function uploadStudentList($field, $entity, $extra)
    {
        $thresholdDataaa = json_decode($extra['value'], true);
        $students = $thresholdDataaa;

        foreach ($students as $w => $stu) {
            $alreadyExistData = InstitutionStudentSurvey::where(
                    [
                        'status_id' => 1,
                        'institution_id' => $stu['institution_id'],
                        'student_id' => $stu['student_id'],
                        'academic_period_id' => $stu['academic_period_id'],
                        'survey_form_id' => $stu['student_list_form_id'],
                        'parent_form_id' => $stu['parent_form_id'],
                    ]
                )->first();

            if(empty($alreadyExistData)){
                $insertArr['status_id'] = 1;
                $insertArr['institution_id'] = $stu['institution_id'];
                $insertArr['student_id'] = $stu['student_id'];
                $insertArr['academic_period_id'] = $stu['academic_period_id'];
                $insertArr['survey_form_id'] = $stu['student_list_form_id'];
                $insertArr['parent_form_id'] = $stu['institution_form_id'];
                $insertArr['created_user_id'] = JWTAuth::user()->id;
                $insertArr['created'] = date('Y-m-d H:i:s');

                $newRecordId = InstitutionStudentSurvey::insertGetId($insertArr);

                $successData = InstitutionStudentSurvey::where('id', $newRecordId)->first();
            } else {
                $successData = $alreadyExistData;
            }

            if ($successData) {
                $questions = $stu['questions'];
                foreach ($questions as $t => $ques) {
                    $duplicateData11 = InstitutionStudentSurveyAnswer::where([
                            'survey_question_id' => $ques['student_list_survey_question_id'],
                            'parent_survey_question_id' => $ques['parent_survey_question_id'],
                            'institution_student_survey_id' => $successData['id'],
                        ])
                        ->delete();

                    if (!empty($ques['survey_answer'])) {
                        if (($ques['student_list_survey_question_type'] == "DROPDOWN") || ($ques['student_list_survey_question_type'] == "NUMBER")) {
                            $AnsEntity = InstitutionStudentSurveyAnswer::insert([
                                'id' => Str::uuid(),
                                'number_value' => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $ques['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => date('Y-m-d H:i:s'),
                            ]);
                        } else if($ques['student_list_survey_question_type'] == "TEXT") {
                            $AnsEntity = InstitutionStudentSurveyAnswer::insert([
                                'id' => Str::uuid(),
                                'text_value' => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $ques['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => date('Y-m-d H:i:s'),
                            ]);
                        } else if($ques['student_list_survey_question_type'] == "DECIMAL") {
                            $AnsEntity = InstitutionStudentSurveyAnswer::insert([
                                'id' => Str::uuid(),
                                'decimal_value' => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $ques['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => date('Y-m-d H:i:s'),
                            ]);
                        } else if ($ques['student_list_survey_question_type'] == "TEXTAREA") {
                            $AnsEntity = InstitutionStudentSurveyAnswer::insert([
                                'id' => Str::uuid(),
                                'textarea_value' => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $ques['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => date('Y-m-d H:i:s'),
                            ]);
                        } else if($ques['student_list_survey_question_type'] == "DATE") {
                            $AnsEntity = InstitutionStudentSurveyAnswer::insert([
                                'id' => Str::uuid(),
                                'date_value' => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $ques['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => date('Y-m-d H:i:s'),
                            ]);
                        } else if($ques['student_list_survey_question_type'] == "TIME") {
                            $AnsEntity = InstitutionStudentSurveyAnswer::insert([
                                'id' => Str::uuid(),
                                'time_value' => $ques['survey_answer'],
                                'survey_question_id' => $ques['student_list_survey_question_id'],
                                'parent_survey_question_id' => $ques['parent_survey_question_id'],
                                'institution_student_survey_id' => $successData['id'],
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }
            }
        }

        $this->processUpload('student_list', ['sada']);
    }


    private function uploadDate($field, $entity, $extra)
    {
        $this->processUpload('date_value', $extra);
    }

    private function uploadTime($field, $entity, $extra)
    {
        $this->processUpload('time_value', $extra);
    }


    private function uploadCoordinates($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            if (count(explode(" ", $value)) == 2) {
                list($latitudeValue, $longitudeValue) = explode(" ", $value, 2);
                $json = json_encode([
                    'latitude' => $latitudeValue,
                    'longitude' => $longitudeValue
                ]);
                $data['text_value'] = $json;
                $this->saveFieldValue($data, $extra);
            }
        }
    }


    private function uploadFile($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];

        $this->deleteFieldValue($data, $extra);
        if (strlen($value) != 0) {
            // expected format received from mobile
            // filename.jpg|data:image/jpg;base64,urlencode( base64_encode( file_get_contents( $filepath) ) )
            list($fileName, $fileData) = explode("|", $value, 2);
            list($fileTypeStr, $encodedStr) = explode(";", $fileData, 2);
            list($encodeType, $encoded) = explode(",", $encodedStr, 2);
            $decoded = base64_decode($encoded);

            $answerData = array_merge($data, [
                'text_value' => $fileName,  // File Name
                'file' => $decoded  // File Content
            ]);
            $this->saveFieldValue($answerData, $extra);
        }
    }


    private function uploadRepeater($field, $entity, $extra)
    {
        $data = $extra['data'];
        $value = $extra['value'];
        $recordKey = $extra['recordKey'];
        $formKey = $extra['formKey'];
        $fieldKey = $extra['fieldKey'];

        $formId = null;
        $fieldId = $data[$fieldKey];


        // Get Survey Form ID
        $fieldEntity = SurveyQuestion::where('id', $fieldId)->first();
        if (isset($fieldEntity->params) && !empty($fieldEntity->params)) {
            $params = json_decode($fieldEntity->params, true);
            if (array_key_exists($formKey, $params)) {
                $formId = $params[$formKey];
            }
        }
        // End


        if (!is_null($formId)) {
            //
        }
    }


    private function saveTableCell($cellData, $extra)
    {
        $cellModel = $extra['cellModel'];

        /*$cellEntity = $cellModel->newEntity($cellData);
        if (!$cellModel->save($cellEntity)) {
            Log::write('debug', $cellEntity->errors());
        }*/

        $store = DB::table($cellModel)->insert($cellData);
    }


    private function deleteExpiredResponse()
    {
        //$SurveyResponses = TableRegistry::getTableLocator()->get('Survey.SurveyResponses');

        $expiryDate = Date('Y-m-d h:i:s', strtotime('-3 days'));

        $delete = SurveyResponse::where('created', '<', $expiryDate)->delete();
    }


    private function addResponse($xmlResponse)
    {
        $responseData = [
            'id' => Str::Uuid(),
            'response' => $xmlResponse,
            'created' => date('Y-m-d h:i:s')
        ];
        $store = SurveyResponse::insert($responseData);
    }


    public function buildSurveyRecords($institutionId = null, $surveyFormId = null, $academicPeriodId = null)
    {
        $surveyForms = new SurveyForms();
        if(!is_null($surveyFormId)){
            $surveyForms = $surveyForms->where('id', $surveyFormId);
        }

        $surveyForms = $surveyForms->pluck('id')->toArray();
        $todayDate = date("Y-m-d");
        $institution = Institutions::where('id', $institutionId)->first();
        $institutionTypeId = $institution->institution_type_id??0;

        foreach ($surveyForms as $key => $surveyFormId) {
            $filterTypeQuery = SurveyFormFilter::where(['survey_form_id' => $surveyFormId])->get();


            $registryAlias = 'Institution.InstitutionSurveys';
            $openStatusId = null;
            $workflow = $this->getWorkflow($registryAlias, null, $surveyFormId);
            
            if(count($filterTypeQuery) > 0){
                if (!empty($workflow)) {
                    foreach ($workflow->WorkflowSteps as $workflowStep) {
                        
                        if ($workflowStep->category == 1) {
                            $openStatusId = $workflowStep->id;
                            break;
                        }
                    }

                    // Update all New Survey to Expired by Institution Id
                    /*$this->updateAll(
                        ['status_id' => self::EXPIRED],
                        [
                            'institution_id' => $institutionId,
                            'survey_form_id' => $surveyFormId,
                            'status_id' => $openStatusId
                        ]
                    );*/

                    $update = InstitutionSurveys::where('institution_id', $institutionId)->where('survey_form_id', $surveyFormId)->where('status_id', $openStatusId)->update(['status_id' => '-1']);
                    

                    $periodResults = SurveyStatusPeriods::select('survey_status_periods.id', 'survey_status_periods.academic_period_id', 'survey_status_id')->join('survey_statuses', 'survey_statuses.id', '=', 'survey_status_periods.survey_status_id')->join('survey_forms', 'survey_forms.id', '=', 'survey_statuses.survey_form_id')
                        ->join('academic_periods', 'academic_periods.id', '=', 'survey_status_periods.academic_period_id');
                    if(!is_null($academicPeriodId)){
                        $periodResults = $periodResults->where('academic_periods.id', $academicPeriodId);
                    }

                    if(isset($surveyFormId) && isset($todayDate)){
                        $periodResults = $periodResults->where('survey_statuses.survey_form_id', $surveyFormId)->where('survey_statuses.date_disabled', '>', $todayDate);
                    }

                    $periodResults = $periodResults->get();
                    //dd($academicPeriodId, $surveyFormId, $todayDate, $periodResults);

                    foreach ($periodResults as $obj) {
                        if (!is_null($institutionId)) {
                            $periodId = $obj->academic_period_id;
                            $instutionSurvey = InstitutionSurveys::where('academic_period_id', $periodId)->where('survey_form_id', $surveyFormId)->where('institution_id', $institutionId)->first();
                            
                            if(empty($instutionSurvey)){
                                // Insert New Survey if not found
                                $surveyData = [
                                    'status_id' => $openStatusId,
                                    'academic_period_id' => $periodId,
                                    'survey_form_id' => $surveyFormId,
                                    'institution_id' => $institutionId,
                                    'created_user_id' => 1,
                                    'created' => Carbon::now()->toDateTimeString()
                                ];

                                $insert = InstitutionSurveys::insert($surveyData);
                            } else {
                                $update = InstitutionSurveys::where('institution_id', $institutionId)->where('survey_form_id', $surveyFormId)->where('status_id', '-1')->where('academic_period_id', $periodId)->update(['status_id' => $openStatusId]);
                            }
                        }
                    }
                }
            }
        }
    }


    public function getWorkflow($registryAlias, $entity = null, $filterId = null)
    {
        $workflowModel = $this->getWorkflowSetup($registryAlias);
        //dd($workflowModel);
        if (!empty($workflowModel)) {
            // Find all Workflow setup for the model
            $workflowIdsQuery = Workflows::where('workflow_model_id', $workflowModel->id)->pluck('id');
            
            //$excludedModels = $this->Workflows->getExcludedModels();
            $excludedModels = ['Cases.InstitutionCases'];

            /*if (in_array($workflowModel->model, $excludedModels) && !is_null($entity) && $entity->has('workflow_rule_id') && !empty($entity->workflow_rule_id)) {
                
                $workflowRuleId = $entity->workflow_rule_id;
                $workflowIdsQuery->matching('WorkflowRules', function ($q) use ($workflowRuleId) {
                    return $q->where([
                        'WorkflowRules.id' => $workflowRuleId
                    ]);
                });
            }*/

            $workflowIds = $workflowIdsQuery->toArray();
            
            $workflowQuery = Workflows::with('WorkflowSteps', 'WorkflowSteps.WorkflowActions');

            if (empty($workflowModel->filter)) {
                //dd("if");
                $workflowQuery = $workflowQuery->whereIn('id', $workflowIds);
            } else {
                //dd("else");
                
                $workflowId = 0;
                if (!is_null($filterId)) {
                    //$conditions = [$this->WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds];

                    $filterQuery = WorkflowFilters::whereIn('workflow_id', $workflowIds)->where('filter_id', $filterId);
                        

                    $workflowFilterResults = $filterQuery->get()->toArray();
                    
                    // Use Workflow with filter if found otherwise use Workflow that Apply To All
                    if (empty($workflowFilterResults)) {
                        $filterQuery->whereIn('workflow_id', $workflowIds)->where('filter_id', $filterId);

                        $workflowResults = $filterQuery->get()->toArray();
                    } else {
                        $workflowResults = $workflowFilterResults;
                    }
                    
                    if (!empty($workflowResults)) {
                        //$workflowId = $workflowResults->first()->workflow_id;
                        $workflowId = $workflowResults[0]['workflow_id'];
                    }
                }
                //dd($workflowId);
                $workflowQuery = $workflowQuery->where('id', $workflowId);
            }
            $workflowQuery = $workflowQuery->first();
            return $workflowQuery;
        } else {
            return null;
        }
    }


    public function getWorkflowSetup($registryAlias)
    {
        $workflowModel = WorkflowModel::where('model', 'Institution.InstitutionSurveys')->first();
        
        return $workflowModel;
    }


    private function deleteQuestionWithRules($surveyFormId, $recordId)
    {
        $questions = SurveyRules::where('survey_form_id', $surveyFormId)->where('enabled', 1)->pluck('survey_question_id');


        $deleteInstitutionSurveyAnswers = InstitutionSurveyAnswers::whereIn('survey_question_id', $questions)->where('institution_survey_id', $recordId)->delete();

        $delInstitutionSurveyTableCells = InstitutionSurveyTableCells::whereIn('survey_question_id', $questions)->where('institution_survey_id', $recordId)->delete();
    }



    public function checkInsXform($params, $surveyFormId, $insCode, $academicPeriod)
    {
        try {
            $resp = [];
            $institution = Institutions::where('code', $insCode)->first();          
            $academicPeriod = AcademicPeriod::where('name', $academicPeriod)->first();

            $checkSurvey = InstitutionSurveys::where('institution_id', $institution->id??0)
                            ->where('academic_period_id', $academicPeriod->id??0)
                            ->where('survey_form_id', $surveyFormId)
                            ->first();
            

            if(!empty($checkSurvey)){
                $resp['code'] = 200;
                $resp['survey_exist_for_ins'] = "Yes";
            }
            
            return $resp;                             
        } catch (\Exception $e) {
            Log::error(
                'Failed to check survey form.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to check survey form.');
        }
    }



    public function getStudentListForSurvey($params, $surveyFormId, $insCode, $academicPeriod)
    {
        try {
            ini_set('max_execution_time', 3000);


            $surveyForm = SurveyForms::where('id', $surveyFormId)->first();
            if(empty($surveyForm)){
                return 1; //Survey form don't exists.
            }

            $insData = Institutions::where('code', $insCode)->first();
            if(empty($insData)){
                return 2; //Institution don't exist.
            }

            $apData = AcademicPeriod::where('name', $academicPeriod)->first();
            if(empty($apData)){
                return 3; //Academic period don't exist.
            }

            $insId = $insData->id;
            $apId = $apData->id;
            $title = $surveyForm->name??"";


            $main_query = "(SELECT institution_surveys.academic_period_id
                    ,institution_surveys.institution_id
                    ,institution_surveys.survey_form_id institution_survey_form_id
                    ,institution_forms.name institution_survey_form_name
                    ,survey_questions.id institution_survey_question_id
                    ,survey_forms_questions.section
                    ,student_list_survey_forms_questions.order
                    ,survey_questions.name institution_survey_question_name
                    ,student_list_survey_forms_questions.survey_form_id student_list_survey_form_id
                    ,survey_list_forms.name student_list_survey_form_name
                    ,student_list_survey_questions.id student_list_survey_question_id
                    ,student_list_survey_questions.name student_list_survey_question_name
                    ,student_list_survey_questions.field_type student_list_survey_question_type
                FROM institution_surveys
                INNER JOIN survey_forms institution_forms
                ON institution_forms.id = institution_surveys.survey_form_id
                INNER JOIN survey_forms_questions
                ON survey_forms_questions.survey_form_id = institution_surveys.survey_form_id
                INNER JOIN survey_questions
                ON survey_questions.id = survey_forms_questions.survey_question_id
                LEFT JOIN survey_forms_questions student_list_survey_forms_questions
                ON student_list_survey_forms_questions.survey_form_id = JSON_EXTRACT(survey_questions.params, '$.survey_form_id')
                LEFT JOIN survey_forms survey_list_forms
                ON survey_list_forms.id = student_list_survey_forms_questions.survey_form_id
                LEFT JOIN survey_questions student_list_survey_questions
                ON student_list_survey_questions.id = student_list_survey_forms_questions.survey_question_id
                WHERE institution_surveys.academic_period_id = ".$apId."
                AND institution_surveys.institution_id = ".$insId."
                AND institution_surveys.survey_form_id = ".$surveyFormId."
                AND institution_surveys.status_id = 1
                AND LENGTH(survey_questions.params) > 0
                AND survey_questions.field_type = 'STUDENT_LIST') main_query";


            $left_join1 = "(SELECT institution_classes.academic_period_id
                    ,institution_classes.institution_id
                    ,institution_classes.id institution_class_id
                    ,institution_classes.name institution_class_name
                    ,classes_student_info.student_id
                    ,classes_student_info.openemis_no
                    ,classes_student_info.student_name
                FROM institution_classes
                LEFT JOIN
                    (
                        SELECT institution_class_students.institution_class_id
                            ,security_users.id student_id
                            ,security_users.openemis_no
                            ,REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' ') student_name
                        FROM institution_class_students
                        INNER JOIN
                        (
                            SELECT institution_class_students.student_id
                                ,institution_class_students.education_grade_id
                                ,institution_class_students.academic_period_id
                                ,institution_class_students.institution_id
                                ,MAX(institution_class_students.created) max_created
                            FROM institution_class_students
                            INNER JOIN academic_periods
                            ON academic_periods.id = institution_class_students.academic_period_id
                            WHERE institution_class_students.academic_period_id = ".$apId."
                            AND institution_class_students.institution_id = ".$insId."
                            AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
                            GROUP BY institution_class_students.student_id
                                ,institution_class_students.education_grade_id
                                ,institution_class_students.academic_period_id
                                ,institution_class_students.institution_id
                        ) latest_class
                        ON latest_class.student_id = institution_class_students.student_id
                        AND latest_class.education_grade_id = institution_class_students.education_grade_id
                        AND latest_class.academic_period_id = institution_class_students.academic_period_id
                        AND latest_class.institution_id = institution_class_students.institution_id
                        AND latest_class.max_created = institution_class_students.created
                        INNER JOIN security_users
                        ON security_users.id = institution_class_students.student_id
                        INNER JOIN academic_periods
                        ON academic_periods.id = institution_class_students.academic_period_id
                        WHERE institution_class_students.academic_period_id = ".$apId."
                        AND institution_class_students.institution_id = ".$insId."
                        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
                    ) classes_student_info
                    ON classes_student_info.institution_class_id = institution_classes.id
                    WHERE institution_classes.academic_period_id = ".$apId."
                    AND institution_classes.institution_id = ".$insId.") class_students_info ON class_students_info.academic_period_id = main_query.academic_period_id AND class_students_info.institution_id = main_query.institution_id";

            $left_join2 = "(SELECT institution_student_surveys.academic_period_id
                    ,institution_student_surveys.institution_id
                    ,institution_student_surveys.student_id
                    ,institution_student_surveys.survey_form_id
                    ,institution_student_surveys.parent_form_id
                    ,institution_student_survey_answers.survey_question_id
                    ,institution_student_survey_answers.parent_survey_question_id
                    ,survey_question_choices.id answer_choice_id_for_dropdown
                    ,IF(institution_student_survey_answers.id IS NULL, '',
                        IF(institution_student_survey_answers.text_value IS NOT NULL, institution_student_survey_answers.text_value,
                            IF(institution_student_survey_answers.decimal_value IS NOT NULL, institution_student_survey_answers.decimal_value,
                                IF(institution_student_survey_answers.textarea_value IS NOT NULL, institution_student_survey_answers.textarea_value,
                                    IF(institution_student_survey_answers.date_value IS NOT NULL, institution_student_survey_answers.date_value,
                                        IF(institution_student_survey_answers.time_value IS NOT NULL, institution_student_survey_answers.time_value,
                                                IF(survey_question_choices.id IS NOT NULL, survey_question_choices.name, institution_student_survey_answers.number_value))))))) survey_answer_values
                FROM institution_student_survey_answers
                INNER JOIN institution_student_surveys
                ON institution_student_surveys.id = institution_student_survey_answers.institution_student_survey_id
                LEFT JOIN survey_question_choices
                ON survey_question_choices.id = institution_student_survey_answers.number_value
                WHERE institution_student_surveys.status_id = 1
                AND institution_student_surveys.academic_period_id = ".$apId."
                AND institution_student_surveys.institution_id = ".$insId."
                AND institution_student_surveys.parent_form_id = ".$surveyFormId.") student_survey_answers_info ON student_survey_answers_info.academic_period_id = class_students_info.academic_period_id AND student_survey_answers_info.institution_id = class_students_info.institution_id AND student_survey_answers_info.student_id = class_students_info.student_id AND student_survey_answers_info.survey_form_id = main_query.student_list_survey_form_id AND student_survey_answers_info.parent_form_id = main_query.institution_survey_form_id AND student_survey_answers_info.survey_question_id = main_query.student_list_survey_question_id AND student_survey_answers_info.parent_survey_question_id = main_query.institution_survey_question_id";


            $sql1 = "SELECT
                main_query.academic_period_id as academic_period_id,
                main_query.institution_id as institution_id,
                main_query.institution_survey_form_id as institution_form_id,
                main_query.institution_survey_form_name as institution_form_name,
                main_query.institution_survey_question_id as institutiton_survey_question_id,
                main_query.section as section,
                main_query.institution_survey_question_name as name,
                main_query.student_list_survey_form_id as student_list_form_id,
                main_query.student_list_survey_form_name as student_list_form_name,
                main_query.student_list_survey_question_id as student_list_survey_question_id,
                main_query.student_list_survey_question_name as student_list_survey_question_name,
                main_query.student_list_survey_question_type as student_list_survey_question_type,
                class_students_info.institution_class_id as institution_class_id,
                class_students_info.institution_class_name as class_name,
                class_students_info.student_id as student_id,
                class_students_info.openemis_no as openemis_no,
                class_students_info.student_name as student_name,
                student_survey_answers_info.answer_choice_id_for_dropdown,
                student_survey_answers_info.survey_answer_values as survey_answer FROM " .$main_query. " LEFT JOIN ".$left_join1." LEFT JOIN ".$left_join2." GROUP BY main_query.section ORDER BY main_query.section DESC, main_query.institution_survey_question_id ASC";


            $sql2 = "SELECT 
                class_students_info.institution_class_id as institution_class_id,
                class_students_info.institution_class_name as class_name FROM " .$main_query. " LEFT JOIN ".$left_join1." LEFT JOIN ".$left_join2."  GROUP BY institution_class_id ORDER BY main_query.section DESC";

            $sql3 = "SELECT
                main_query.institution_survey_form_id as institution_form_id,
                main_query.institution_survey_form_name as institution_form_name,
                main_query.student_list_survey_form_id as student_list_form_id,
                main_query.student_list_survey_form_name as student_list_form_name,
                main_query.institution_survey_question_name as name,
                main_query.student_list_survey_question_id as student_list_survey_question_id,
                main_query.student_list_survey_question_name as student_list_survey_question_name,
                main_query.student_list_survey_question_type as student_list_survey_question_type,
                main_query.institution_id as institution_id,
                main_query.academic_period_id as academic_period_id,
                class_students_info.institution_class_id as institution_class_id,
                class_students_info.institution_class_name as class_name,
                class_students_info.student_id as student_id,
                class_students_info.openemis_no as openemis_no,
                class_students_info.student_name as student_name FROM " .$main_query. " LEFT JOIN ".$left_join1." LEFT JOIN ".$left_join2."  GROUP BY student_id ORDER BY main_query.section DESC";



            $sql4 = "SELECT
                main_query.order as question_order,
                main_query.student_list_survey_question_id as student_list_survey_question_id,
                main_query.student_list_survey_question_name as student_list_survey_question_name,
                main_query.student_list_survey_question_type as student_list_survey_question_type,
                main_query.institution_id as institution_id FROM " .$main_query. " LEFT JOIN ".$left_join1." LEFT JOIN ".$left_join2." GROUP BY student_list_survey_question_id ORDER BY main_query.section DESC, question_order ASC";


            $tabData = DB::select(DB::raw($sql1));
            //Converting collection into array...
            $tabData = array_map(function($item) {
                return (array) $item;
            }, $tabData);
            

            $class_list = DB::select(DB::raw($sql2));
            //Converting collection into array...
            $class_list = array_map(function($item) {
                return (array) $item;
            }, $class_list);

            $students = DB::select(DB::raw($sql3));
            //Converting collection into array...
            $students = array_map(function($item) {
                return (array) $item;
            }, $students);
            

            $questions = DB::select(DB::raw($sql4));
            //Converting collection into array...
            $questions = array_map(function($item) {
                return (array) $item;
            }, $questions);
            


            $finalData = [];
            $AnswerKeyArr = [];
            $selectVAlue = Null;
            foreach ($tabData as $p => $tbDta) {
                $finalData[$tbDta['section']]['parent_question_tab_id'] = $tbDta['institutiton_survey_question_id'];
                $finalData[$tbDta['section']]['class_list'] = $class_list;
                $finalData[$tbDta['section']]['students'] = $students;

                foreach ($finalData[$tbDta['section']]['students'] as $ke => $student) {
                    $finalData[$tbDta['section']]['students'][$ke]['questions'] = $questions;
                    $ins_stu_survey = InstitutionStudentSurvey::where([
                            'status_id' => 1,
                            'institution_id' => $student['institution_id'],
                            'student_id' => $student['student_id'],
                            'academic_period_id' => $student['academic_period_id'],
                            'survey_form_id' => $student['student_list_form_id'],
                            'parent_form_id' => $student['institution_form_id'],
                        ])
                        ->first();

                    $finalData[$tbDta['section']]['students'][$ke]['institution_student_survey_id'] = $ins_stu_survey->id??null;

                    foreach ($finalData[$tbDta['section']]['students'][$ke]['questions'] as $jk => $ques) {
                        $options = SurveyQuestionChoices::where('survey_question_id', $ques['student_list_survey_question_id'])->get()->toArray();

                        $finalData[$tbDta['section']]['students'][$ke]['questions'][$jk]['options'] = $options;

                        if (!empty($ins_stu_survey)) {
                            $dataExistAns = InstitutionStudentSurveyAnswer::where([
                                    'survey_question_id' => $ques['student_list_survey_question_id'],
                                    'parent_survey_question_id' => $tbDta['institutiton_survey_question_id'],
                                    'institution_student_survey_id' => $student['institution_student_survey_id']
                                ])
                                ->first();

                            if (!empty($dataExistAns)) {
                                if (!empty($dataExistAns->number_value)) {
                                    $selectVAlue = $dataExistAns->number_value;
                                } elseif (!empty($dataExistAns->text_value)) {
                                    $selectVAlue = $dataExistAns->text_value;
                                } elseif (!empty($dataExistAns->decimal_value)) {
                                    $selectVAlue = $dataExistAns->decimal_value;
                                } elseif (!empty($dataExistAns->textarea_value)) {
                                    $selectVAlue = $dataExistAns->textarea_value;
                                } elseif (!empty($dataExistAns->date_value)) {
                                    $selectVAlue = date('Y-m-d', strtotime($dataExistAns->date_value));
                                } elseif (!empty($dataExistAns->time_value)) {
                                    $selectVAlue = date('h:i:s', strtotime($dataExistAns->date_value));
                                }
                            } else {
                                $selectVAlue = $options[0]['id'];
                            }
                        }

                        $AnswerKeyArr['server_key'][$tbDta['section']][$ke][$jk]['answer'][] = $selectVAlue;

                        $finalData[$tbDta['section']]['students'][$ke]['questions'][$jk]['survey_answer'] = $selectVAlue;

                    }
                }
            }

            $final = [];
            $final['data'] = $finalData;
            $final['survey_answer_arr'] = $AnswerKeyArr;

            return $final;

        } catch (\Exception $e) {
            Log::error(
                'Failed to find student list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to find student list.');
        }
    }
}

