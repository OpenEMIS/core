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

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $surveys = InstitutionSurveys::with('surveyForms','surveyForms.customModule');

            if(isset($params['institution_id'])){
                $surveys = $surveys->where('institution_id', $params['institution_id']);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $surveys = $surveys->orderBy($col, $orderBy);
            }

            $list = $surveys->paginate($limit)->toArray();
            
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
        $extra['required'] = $field->default_is_mandatory;

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
                $bindType = "decimal".Inflector::camelize($validationType).$extra['index'];

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
}

