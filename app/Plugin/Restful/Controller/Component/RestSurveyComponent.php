<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

define("NS_XHTML", "http://www.w3.org/1999/xhtml");
define("NS_XF", "http://www.w3.org/2002/xforms");
define("NS_EV", "http://www.w3.org/2001/xml-events");
define("NS_XSD", "http://www.w3.org/2001/XMLSchema");
define("NS_OE", "https://www.openemis.org");
App::uses('Xml', 'Utility');

class RestSurveyComponent extends Component {
	private $controller;
    
    public $allowedActions = array('listing', 'schools', 'download');

	public $components = array(
		'Session', 'Message', 'Auth',
		'CustomField2'
	);

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;

		$models = $this->settings['models'];
		foreach ($models as $key => $model) {
			if (!is_null($model)) {
				$this->{$key} = ClassRegistry::init($model);
			} else {
				$this->{$key} = null;
			}

			$modelInfo = explode('.', $model);
			$base = count($modelInfo) == 1 ? $modelInfo[0] : $modelInfo[1];
			$this->controller->set('Custom_' . $key, $base);
		}
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {}

	public function listing() {
    	$params = $this->controller->params->named;
    	$selectedModule = $this->CustomField2->checkModule();
		
		if(!is_null($selectedModule)) {
			$groupsConditions = array(
				$this->Group->alias.'.'.Inflector::underscore($this->Module->alias).'_id' => $selectedModule
			);
		} else {
			$groupsConditions = array();
		}

		//Start of joining SurveyStatus table
		$todayDate = date('Y-m-d');
		$todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));
		$this->controller->paginate['joins'] = array(
			array(
				'table' => 'survey_statuses',
				'alias' => 'SurveyStatus',
				'type' => 'INNER',
				'conditions' => array(
					'SurveyStatus.'.Inflector::underscore($this->Group->alias).'_id = ' .$this->Group->alias.'.id',
					'SurveyStatus.date_disabled >=' => $todayTimestamp
				)
		));
		$this->controller->paginate['group'] = array(
			$this->Group->alias.'.id'
		);
		//End of joining SurveyStatus table

		$this->controller->paginate['conditions'] = $groupsConditions;
    	$this->controller->paginate['order'] = array(
    		$this->Group->alias.'.name' => 'asc'
    	);
    	$this->controller->paginate['limit'] = isset($params['limit']) ? $params['limit'] : 20;
    	$this->controller->paginate['page'] = isset($params['page']) ? $params['page'] : 1;
    	$this->controller->paginate['findType'] = 'list';

		$this->controller->Paginator->settings = $this->controller->paginate;

		try {
			$templates = $this->controller->Paginator->paginate($this->Group->alias);pr($templates);

			$result = array();
			if(!empty($templates)) {
				$url = '/' . $this->controller->params->controller . '/survey/download/xform/';
				//$media_url = '/' . $this->controller->params->controller . '/survey/downloadImage/';

				$list = array();
				foreach ($templates as $key => $template) {
					$list[] = array(
						'id' => $key,
						'name' => $template
					);
				}

				$requestPaging = $this->controller->request['paging'][$this->Group->alias];
				$result['total'] = $requestPaging['count'];
				$result['page'] = $requestPaging['page'];
				$result['limit'] = $requestPaging['limit'];
				$result['list'] = $list;
				$result['url'] = $url;
				//$result['media_url'] = $media_url;
			}
		} catch (NotFoundException $e) {
			$this->controller->log($e->getMessage(), 'debug');
			$result['list'] = array();
		}

    	return json_encode($result);
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
			return $result->asXML();
		} else { // download as file
			$fileName = $format . '_' . date('Ymdhis') . '.xml';
		    
		    header('Expires: 0');
		    header('Content-Encoding: UTF-8');
		    // force download  
		    header("Content-Type: application/force-download; charset=UTF-8'");
		    header("Content-Type: application/octet-stream; charset=UTF-8'");
		    header("Content-Type: application/download; charset=UTF-8'");
		    // disposition / encoding on response body
		    header("Content-Disposition: attachment;filename={$fileName}");
		    header("Content-Transfer-Encoding: binary");

		   	if (ob_get_contents()){
			    ob_end_clean();
			}
			ob_start();
			$df = fopen("php://output", 'w');
			fputs($df, $result->asXML());
			fclose($df);
			return ob_get_clean();
		}
    }

    public function upload() {
		if ($this->controller->request->is(array('post', 'put'))) {
    		$data = $this->controller->request->data;
            $this->log('Data:', 'debug');
    		$this->log($data, 'debug');
    		$xmlResponse = $data['response'];
			
            $this->log('XML Response', 'debug');
            $this->log($xmlResponse, 'debug');
	    	$xmlResponse = str_replace("xf:", "", $xmlResponse);
	    	$xmlResponse = str_replace("oe:", "", $xmlResponse);

	    	$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlResponse;
            $this->log('XML String:', 'debug');
            $this->log($xmlstr, 'debug');
    		$xml = new SimpleXMLElement($xmlstr);

			$surveyTemplateId = $xml->SurveyTemplate->attributes()->id->__toString();
    		$institutionSiteId = $xml->SurveyTemplate->InstitutionSite->__toString();
    		$academicPeriodId = $xml->SurveyTemplate->AcademicPeriod->__toString();
    		$surveyStatus = 2; //completed

			$surveyData = array();
    		$surveyData['InstitutionSiteSurveyNew'] = array(
    			'survey_template_id' => $surveyTemplateId,
    			'institution_site_id' => $institutionSiteId,
    			'academic_period_id' => $academicPeriodId,
    			'status' => $surveyStatus,
    			'created_user_id' => 1
    		);

    		$questions = $xml->SurveyTemplate->SurveyQuestion;

			$arrFieldName = array(
				2 => 'text_value',
				3 => 'int_value',
				4 => 'int_value',
				5 => 'textarea_value',
				6 => 'int_value',
				7 => 'value'
			);
    		$SurveyQuestion = ClassRegistry::init('SurveyQuestion');

    		foreach ($questions as $question) {
    			$questionId = $question->attributes()->id->__toString();
				$fieldType = $SurveyQuestion->field('type', array('SurveyQuestion.id' => $questionId));
				$fieldName = $arrFieldName[$fieldType];

				$fieldTypeArr = array(2, 3, 4, 5, 6, 7); //Only support 2 -> Text, 3 -> Dropdown, 4 -> Checkbox, 5 -> Textarea, 6 -> Number, 7 -> Table
				if(in_array($fieldType, $fieldTypeArr)) {
					switch($fieldType) {
						case 2:	//Text
						case 3:	//Dropdown
						case 5:	//Textarea
						case 6:	//Number
							$answerValue = $question->__toString();
							if (!empty($answerValue)) {
								$answer = array(
				    				'institution_site_id' => $institutionSiteId,
				    				'survey_status' => $surveyStatus,
				    				'survey_question_id' => $questionId,
				    				'type' => $fieldType,
				    				$fieldName => $answerValue,
				    				'created_user_id' => 1
				    			);
				    			$surveyData['InstitutionSiteSurveyAnswer'][] = $answer;
				    		}
							break;
						case 4:	//Checkbox
							$answerValue = $question->__toString();
							if (!empty($answerValue)) {
								$checkboxValues = explode(" ", $answerValue);
								foreach ($checkboxValues as $key => $checkboxValue) {
									$answer = array(
					    				'institution_site_id' => $institutionSiteId,
					    				'survey_status' => $surveyStatus,
					    				'survey_question_id' => $questionId,
					    				'answer_number' => ++$key,
					    				'type' => $fieldType,
					    				$fieldName => $checkboxValue,
					    				'created_user_id' => 1
					    			);
					    			$surveyData['InstitutionSiteSurveyAnswer'][] = $answer;
								}
							}
							break;
						case 7:	//Table
							foreach ($question->children() as $row => $rowObj) {
								$rowId = $rowObj->attributes()->id->__toString();
								foreach ($rowObj->children() as $col => $colObj) {
									$colId = $colObj->attributes()->id->__toString();
									if ($colId != 0) {
										$cellValue = $colObj->__toString();
										if (!empty($cellValue)) {
											$cell = array(
							    				'institution_site_id' => $institutionSiteId,
							    				'survey_status' => $surveyStatus,
							    				'survey_question_id' => $questionId,
							    				'survey_table_column_id' => $colId,
							    				'survey_table_row_id' => $rowId,
							    				'type' => $fieldType,
							    				$fieldName => $cellValue,
							    				'created_user_id' => 1
							    			);
											$surveyData['InstitutionSiteSurveyTableCell'][] = $cell;
										}
									}
								}
							}
							break;
					}
				}
    		}

			$InstitutionSiteSurvey = ClassRegistry::init('InstitutionSiteSurveyNew');
			$InstitutionSiteSurveyAnswer = ClassRegistry::init('InstitutionSiteSurveyAnswer');
			$InstitutionSiteSurveyTableCell = ClassRegistry::init('InstitutionSiteSurveyTableCell');
			$surveyId = $InstitutionSiteSurvey->field('id', array(
				'InstitutionSiteSurveyNew.survey_template_id' => $surveyTemplateId,
    			'InstitutionSiteSurveyNew.institution_site_id' => $institutionSiteId,
    			'InstitutionSiteSurveyNew.academic_period_id' => $academicPeriodId
			));
			if ($surveyId) {
				$InstitutionSiteSurvey->deleteAll(array('InstitutionSiteSurveyNew.id' => $surveyId));
				$InstitutionSiteSurveyAnswer->deleteAll(array(
					'InstitutionSiteSurveyAnswer.institution_site_survey_id' => $surveyId
				), false);
				$InstitutionSiteSurveyTableCell->deleteAll(array(
					'InstitutionSiteSurveyTableCell.institution_site_survey_id' => $surveyId
				), false);
			}

			if ($InstitutionSiteSurvey->saveAll($surveyData)) {
				if($surveyStatus == 2) {
					$message = 'Survey record has been submitted successfully.';
				} else {
					$message = 'Survey record has been saved to draft successfully.';
				}
				$this->log('Message:', 'debug');
    			$this->log($message, 'debug');
			} else {
				$this->log($InstitutionSiteSurvey->validationErrors, 'debug');
			}
    	}
    }

    public function schools() {
    	$query = $this->controller->request->query;
    	$token = $query['token'];
    	$templateIds = explode(",", $query['ids']);
    	$limit = isset($query['limit']) ? $query['limit'] : 10;

		$result = array();
    	$institutionSites = ClassRegistry::init('InstitutionSite')->find('list', array(
    		'limit' => $limit
    	));
    	$academicPeriods = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodList();

    	$InstitutionSiteSurveyNew = ClassRegistry::init('InstitutionSiteSurveyNew');

    	$list = array();
    	$institutionSiteId = $this->Session->read('InstitutionSite.id');
		foreach ($institutionSites as $key => $institutionSite) {
			$this->Session->write('InstitutionSite.id', $key);
			$data = $InstitutionSiteSurveyNew->getSurveyTemplatesByModule();
			
			$templates = array();
			foreach ($data as $obj) {
				if(in_array($obj['SurveyTemplate']['id'], $templateIds)) {
					$periods = array();
					foreach ($obj['AcademicPeriod'] as $academicPeriod) {
						$periods[] = $academicPeriod['AcademicPeriod']['id'];
					}
					$templates[] = array(
						'id' => $obj['SurveyTemplate']['id'],
						'periods' => $periods
					);
				}
			}

			if (!empty($templates)) {
				$list[] = array(
					'id' => $key,
					'name' => $institutionSite,
					'templates' => $templates
				);
			}
		}

		$this->Session->write('InstitutionSite.id', $institutionSiteId);
		$result['list'] = $list;
		$result['periods'] = $academicPeriods;

    	return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function getXForms($instanceId, $id) {
		$title = $this->Group->field('name', array($this->Group->alias.'.id' => $id));
		$title = htmlspecialchars($title, ENT_QUOTES);

    	$fieldContains = array();
		$fieldContains = isset($this->FieldOption) ? array_merge(array($this->FieldOption->alias), $fieldContains) : $fieldContains;
		$fieldContains = isset($this->TableColumn) ? array_merge(array($this->TableColumn->alias), $fieldContains) : $fieldContains;
		$fieldContains = isset($this->TableRow) ? array_merge(array($this->TableRow->alias), $fieldContains) : $fieldContains;
		$this->Field->contain($fieldContains);
		$fields = $this->Field->find('all', array(
			'conditions' => array(
				$this->Field->alias.'.'.Inflector::underscore($this->Group->alias).'_id' => $id,
				$this->Field->alias.'.visible' => 1
			),
			'order' => array(
				$this->Field->alias.'.order', 
				$this->Field->alias.'.name'
			)
		));

		$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
    				<html
    					xmlns="' . NS_XHTML . '"
    					xmlns:xf="' . NS_XF . '"
    					xmlns:ev="' . NS_EV . '"
    					xmlns:xsd="' . NS_XSD . '"
	    				xmlns:oe="' . NS_OE . '">
					</html>';

    	$xml = new SimpleXMLElement($xmlstr);

		$headNode = $xml->addChild("head", null, NS_XHTML);
		$bodyNode = $xml->addChild("body", null, NS_XHTML);
			$headNode->addChild("title", $title, NS_XHTML);
				$modelNode = $headNode->addChild("model", null, NS_XF);
					$instanceNode = $modelNode->addChild("instance", null, NS_XF);
					$instanceNode->addAttribute("id", $instanceId);
						$index = 1;
						$sectionBreakNode = $bodyNode;

						$groupNode = $instanceNode->addChild($this->Group->alias, null, NS_OE);
							$groupNode->addAttribute("id", $id);
						$groupNode->addChild('InstitutionSite', null, NS_OE);
						$groupNode->addChild('AcademicPeriod', null, NS_OE);

						$bindNode = $modelNode->addChild("bind", null, NS_XF);
						$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/InstitutionSite");
						$bindNode->addAttribute("type", 'string');
						$bindNode->addAttribute("required", 'true()');
						$bindNode = $modelNode->addChild("bind", null, NS_XF);
						$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/AcademicPeriod");
						$bindNode->addAttribute("type", 'string');
						$bindNode->addAttribute("required", 'true()');

						$textNode = $sectionBreakNode->addChild("input", null, NS_XF);
						$textNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/InstitutionSite");
						$textNode->addAttribute("oe-type", "select");
							$textNode->addChild("label", "Institution Site", NS_XF);
						$textNode = $sectionBreakNode->addChild("input", null, NS_XF);
						$textNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/AcademicPeriod");
						$textNode->addAttribute("oe-type", "select");
						$textNode->addAttribute("oe-dependency", "instance('" . $instanceId . "')/".$this->Group->alias."/InstitutionSite");
							$textNode->addChild("label", "Academic Period", NS_XF);

						foreach ($fields as $key => $field) {
							if ($field[$this->Field->alias]['type'] == 1) {
								$sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
								$sectionBreakNode->addAttribute("ref", $field[$this->Field->alias]['id']);
								$sectionBreakNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
							} else if ($field[$this->Field->alias]['type'] == 7) {
								$sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
								$sectionBreakNode->addAttribute("ref", $field[$this->Field->alias]['id']);
								$sectionBreakNode->addAttribute("oe-type", "table");
								$sectionBreakNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
							}

							$fieldTypeArr = array(2, 3, 4, 5, 6, 7); //Only support 2 -> Text, 3 -> Dropdown, 4 -> Checkbox, 5 -> Textarea, 6 -> Number, 7 -> Table
							if(in_array($field[$this->Field->alias]['type'], $fieldTypeArr)) {
								$fieldNode = $groupNode->addChild($this->Field->alias, null, NS_OE);
									$fieldNode->addAttribute("id", $field[$this->Field->alias]['id']);

								switch($field[$this->Field->alias]['type']) {
									case 2:	//Text
										$fieldType = 'string';
										$textNode = $sectionBreakNode->addChild("input", null, NS_XF);
										$textNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
											$textNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
										break;
									case 3:	//Dropdown
										$fieldType = 'integer';
										$dropdownNode = $sectionBreakNode->addChild("select1", null, NS_XF);
										$dropdownNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
											$dropdownNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
											foreach ($field[$this->FieldOption->alias] as $fieldOption) {
												$itemNode = $dropdownNode->addChild("item", null, NS_XF);
													$itemNode->addChild("label", htmlspecialchars($fieldOption['value'], ENT_QUOTES), NS_XF);
													$itemNode->addChild("value", $fieldOption['id'], NS_XF);
											}
										break;
									case 4:	//Checkbox
										$fieldType = 'integer';
										$checkboxNode = $sectionBreakNode->addChild("select", null, NS_XF);
										$checkboxNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
											$checkboxNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
											foreach ($field[$this->FieldOption->alias] as $fieldOption) {
												$itemNode = $checkboxNode->addChild("item", null, NS_XF);
													$itemNode->addChild("label", htmlspecialchars($fieldOption['value'], ENT_QUOTES), NS_XF);
													$itemNode->addChild("value", $fieldOption['id'], NS_XF);
											}
										break;
									case 5:	//Textarea
										$fieldType = 'string';
										$textareaNode = $sectionBreakNode->addChild("textarea", null, NS_XF);
										$textareaNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
											$textareaNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
										break;
									case 6:	//Number
										$fieldType = 'integer';
										$numberNode = $sectionBreakNode->addChild("input", null, NS_XF);
										$numberNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
											$numberNode->addChild("label", htmlspecialchars($field[$this->Field->alias]['name'], ENT_QUOTES), NS_XF);
										break;
									case 7:	//Table
										$fieldType = false;

										$tableNode = $sectionBreakNode->addChild("table", null, NS_XHTML);
										$tableNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
											$tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
											$tableHeader->addChild("th", null, NS_XHTML);
											$tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
												$xformRepeat = $tableBody->addChild("repeat", null, NS_XF);
												$xformRepeat->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]"."/".$this->TableRow->alias);
													$tbodyRow = $xformRepeat->addChild("tr", null, NS_XHTML);
														$tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
															$tbodyCell = $tbodyColumn->addChild("output", null, NS_XF);
																$tbodyCell->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]"."/".$this->TableColumn->alias."0");

										foreach ($field[$this->TableRow->alias] as $row => $tableRow) {
											$rowNode = $fieldNode->addChild($this->TableRow->alias, null, NS_OE);
											$rowNode->addAttribute("id", $tableRow['id']);
												$colIndex = 0;

												$columnNode = $rowNode->addChild($this->TableColumn->alias . $colIndex, htmlspecialchars($tableRow['name'], ENT_QUOTES), NS_OE);
												$columnNode->addAttribute("id", $colIndex);
												foreach ($field[$this->TableColumn->alias] as $col => $tableColumn) {
													$colIndex++;
													$columnNode = $rowNode->addChild($this->TableColumn->alias . $colIndex, null, NS_OE);
													$columnNode->addAttribute("id", $tableColumn['id']);
													if ($row == 0) {
														$tableHeader->addChild("th", htmlspecialchars($tableColumn['name'], ENT_QUOTES), NS_XHTML);
														$tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
															$tbodyCell = $tbodyColumn->addChild("input", null, NS_XF);
																$tbodyCell->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]"."/".$this->TableColumn->alias.$colIndex);

														$bindNode = $modelNode->addChild("bind", null, NS_XF);
														$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]"."/".$this->TableColumn->alias.$colIndex);
														$bindNode->addAttribute("type", 'integer');
														$bindNode->addAttribute("required", 'true()');
													}
												}
										}
										break;
								}

								if ($fieldType) {
									$bindNode = $modelNode->addChild("bind", null, NS_XF);
									$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Group->alias."/".$this->Field->alias."[".$index."]");
									$bindNode->addAttribute("type", $fieldType);
									if($field[$this->Field->alias]['is_mandatory']) {
										$bindNode->addAttribute("required", 'true()');
									} else {
										$bindNode->addAttribute("required", 'false()');
									}
								}

								$index++;
							}
						}

    	return $xml;
    }
}
