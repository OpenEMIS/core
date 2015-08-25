<?php
namespace Restful\Controller\Component;

use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Utility\Xml;

define("NS_XHTML", "http://www.w3.org/1999/xhtml");
define("NS_XF", "http://www.w3.org/2002/xforms");
define("NS_EV", "http://www.w3.org/2001/xml-events");
define("NS_XSD", "http://www.w3.org/2001/XMLSchema");
define("NS_OE", "https://www.openemis.org");

class RestSurveyComponent extends Component {
	public $controller;
	public $action;

	public $components = ['Paginator'];

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
				$result['limit'] = $requestPaging['perPage'];	// limit
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
			$periods = $AcademicPeriods->getList();
			// End

			$list = [];
			$SurveyRecords = TableRegistry::get('Institution.InstitutionSurveys');
			foreach ($institutions as $institutionId => $institution) {
				$SurveyRecords->buildSurveyRecords($institutionId);

				$forms = [];
				$surveyResults = $SurveyRecords
					->find()
					->where([
						$SurveyRecords->aliasField('institution_site_id') => $institutionId,
						$SurveyRecords->aliasField($this->formKey . ' IN') => $formIds,
						$SurveyRecords->aliasField('status') => 0	// New
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
			return $result->asXML();
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
				$recordKey = 'institution_site_survey_id';

	    		$xmlResponse = $data['response'];
	    		// line below is for testing
	    		// $xmlResponse = "<xf:instance id='xform'><oe:SurveyForms id='1'><oe:InstitutionSite>1</oe:InstitutionSite><oe:AcademicPeriod>10</oe:AcademicPeriod><oe:SurveyQuestions id='2'>some text</oe:SurveyQuestions><oe:SurveyQuestions id='3'>0</oe:SurveyQuestions><oe:SurveyQuestions id='4'>some long long text</oe:SurveyQuestions><oe:SurveyQuestions id='6'>3</oe:SurveyQuestions><oe:SurveyQuestions id='7'>5 6 7</oe:SurveyQuestions><oe:SurveyQuestions id='25'><oe:SurveyTableRows id='20'><oe:SurveyTableColumns0 id='0'>Male</oe:SurveyTableColumns0><oe:SurveyTableColumns1 id='37'>10</oe:SurveyTableColumns1><oe:SurveyTableColumns2 id='38'>20</oe:SurveyTableColumns2><oe:SurveyTableColumns3 id='39'>30</oe:SurveyTableColumns3></oe:SurveyTableRows><oe:SurveyTableRows id='21'><oe:SurveyTableColumns0 id='0'>Female</oe:SurveyTableColumns0><oe:SurveyTableColumns1 id='37'>15</oe:SurveyTableColumns1><oe:SurveyTableColumns2 id='38'>25</oe:SurveyTableColumns2><oe:SurveyTableColumns3 id='39'>35</oe:SurveyTableColumns3></oe:SurveyTableRows></oe:SurveyQuestions></oe:SurveyForms></xf:instance>";
				$this->log('XML Response', 'debug');
				$this->log($xmlResponse, 'debug');
				$xmlResponse = str_replace("xf:", "", $xmlResponse);
				$xmlResponse = str_replace("oe:", "", $xmlResponse);

				$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlResponse;
	            $this->log('XML String:', 'debug');
	            $this->log($xmlstr, 'debug');
	    		$xml = Xml::build($xmlstr);

				$formId = $xml->$formAlias->attributes()->id->__toString();
	    		$institutionId = $xml->$formAlias->InstitutionSite->__toString();
	    		$periodId = $xml->$formAlias->AcademicPeriod->__toString();
	    		$status = 2; // completed
	    		$createdUserId = 1; // System Administrator

	    		$formData = [];
	    		$formData = [
	    			$this->formKey => $formId,
	    			'institution_site_id' => $institutionId,
	    			'academic_period_id' => $periodId,
	    			'status' => $status,
	    			'created_user_id' => $createdUserId
	    		];

	    		// Find existing record
	    		$recordId = null;
	    		$recordResults = $CustomRecords
					->find()
					->where([
						$CustomRecords->aliasField($this->formKey) => $formId,
						$CustomRecords->aliasField('institution_site_id') => $institutionId,
						$CustomRecords->aliasField('academic_period_id') => $periodId
					])
					->all();

				if (!$recordResults->isEmpty()) {
					$formData['id'] = $recordResults->first()->id;
				}
				// End

				// Update record table
				$entity = $CustomRecords->newEntity($formData);
				if ($CustomRecords->save($entity)) {
					if($entity->status == 2) {
						$message = 'Survey record has been submitted successfully.';
					} else {
						$message = 'Survey record has been saved to draft successfully.';
					}
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
								$answerValue = urldecode($field->__toString());
								if (strlen($answerValue) != 0) {
									$answerData = [
										$recordKey => $recordId,
					    				$this->fieldKey => $fieldId,
					    				$fieldColumnName => $answerValue,
					    				'institution_site_id' => $institutionId,
					    				'created_user_id' => $createdUserId
					    			];

					    			// Save answer
					    			$answerEntity = $this->FieldValue->newEntity($answerData);
									if ($this->FieldValue->save($answerEntity)) {
									} else {
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
					    					'institution_site_id' => $institutionId,
						    				'created_user_id' => $createdUserId
						    			];

						    			// Save answer
						    			$answerEntity = $this->FieldValue->newEntity($answerData);
										if ($this->FieldValue->save($answerEntity)) {
										} else {
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
								    				'institution_site_id' => $institutionId,
								    				'created_user_id' => $createdUserId
								    			);

								    			// Save cell by cell
								    			$cellEntity = $this->TableCell->newEntity($cellData);
												if ($this->TableCell->save($cellEntity)) {
												} else {
													$this->log($cellEntity->errors(), 'debug');
												}
												// End
											}
										}
									}
								}
								break;
		    			}
					}
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
				'default_is_unique' => $this->Field->aliasField('is_unique')
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
						$index = 1;
						$sectionBreakNode = $bodyNode;

						$formNode = $instanceNode->addChild($this->Form->alias(), null, NS_OE);
							$formNode->addAttribute("id", $id);
						$formNode->addChild('InstitutionSite', null, NS_OE);
						$formNode->addChild('AcademicPeriod', null, NS_OE);

						$bindNode = $modelNode->addChild("bind", null, NS_XF);
						$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/InstitutionSite");
						$bindNode->addAttribute("type", 'string');
						$bindNode->addAttribute("required", 'true()');
						$bindNode = $modelNode->addChild("bind", null, NS_XF);
						$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/AcademicPeriod");
						$bindNode->addAttribute("type", 'string');
						$bindNode->addAttribute("required", 'true()');

						$textNode = $sectionBreakNode->addChild("input", null, NS_XF);
						$textNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/InstitutionSite");
						$textNode->addAttribute("oe-type", "select");
							$textNode->addChild("label", "Institution Site", NS_XF);
						$textNode = $sectionBreakNode->addChild("input", null, NS_XF);
						$textNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/AcademicPeriod");
						$textNode->addAttribute("oe-type", "select");
						$textNode->addAttribute("oe-dependency", "instance('" . $instanceId . "')/".$this->Form->alias()."/InstitutionSite");
							$textNode->addChild("label", "Academic Period", NS_XF);

						$sectionName = null;
						foreach ($fields as $key => $field) {
							// fieldName, fieldIsMandatory and fieldIsUnique is get from survey_questions for now. Will need to get from survey_forms_questions in future.
							$fieldName = $field->default_name;
							$fieldIsMandatory = $field->default_is_mandatory;
							$fieldIsUnique = $field->default_is_unique;

							// Section
							if ($field->section_name != $sectionName) {
								$sectionName = $field->section_name;

								$sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
								$sectionBreakNode->addAttribute("ref", $field->form_id . '_' . $field->field_id);
								$sectionBreakNode->addChild("label", htmlspecialchars($sectionName, ENT_QUOTES), NS_XF);
							}
							// End

							$fieldNode = $formNode->addChild($this->Field->alias(), null, NS_OE);
								$fieldNode->addAttribute("id", $field->field_id);

								switch($field->field_type) {
									case 'TEXT':
										$fieldType = 'string';
										$textNode = $sectionBreakNode->addChild("input", null, NS_XF);
										$textNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$textNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
										break;
									case 'NUMBER':
										$fieldType = 'integer';
										$numberNode = $sectionBreakNode->addChild("input", null, NS_XF);
										$numberNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$numberNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
										break;
									case 'TEXTAREA':
										$fieldType = 'string';
										$textareaNode = $sectionBreakNode->addChild("textarea", null, NS_XF);
										$textareaNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$textareaNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
										break;
									case 'DROPDOWN':
										$fieldType = 'integer';
										$dropdownNode = $sectionBreakNode->addChild("select1", null, NS_XF);
										$dropdownNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$dropdownNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);

											$fieldOptionResults = $this->FieldOption
												->find()
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
										break;
									case 'CHECKBOX':
										$fieldType = 'integer';
										$checkboxNode = $sectionBreakNode->addChild("select", null, NS_XF);
										$checkboxNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$checkboxNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);

											$fieldOptionResults = $this->FieldOption
												->find()
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
										break;
									case 'TABLE':
										$fieldType = false;
										// To nested table inside xform group
										$tableBreakNode = $sectionBreakNode->addChild("group", null, NS_XF);
										$tableBreakNode->addAttribute("ref", $field->field_id);
										$tableBreakNode->addAttribute("oe-type", "table");
										$tableBreakNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
										// End

										$tableNode = $tableBreakNode->addChild("table", null, NS_XHTML);
										$tableNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
											$tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
												$xformRepeat = $tableBody->addChild("repeat", null, NS_XF);
												$xformRepeat->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableRow->alias());
													$tbodyRow = $xformRepeat->addChild("tr", null, NS_XHTML);

										$tableColumnResults = $this->TableColumn
											->find()
											->find('order')
											->where([
												$this->TableColumn->aliasField($this->fieldKey) => $field->field_id
											])
											->all();

										$tableRowResults = $this->TableRow
											->find()
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
													} else {
														$columnNode = $rowNode->addChild($this->TableColumn->alias() . $col, null, NS_OE);
														$columnNode->addAttribute("id", $tableColumn->id);
													}

													if ($row == 0) {
														$tableHeader->addChild("th", htmlspecialchars($tableColumn->name, ENT_QUOTES), NS_XHTML);
														$tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
															$tbodyCell = $tbodyColumn->addChild("input", null, NS_XF);
																$tbodyCell->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias().$col);

														$bindNode = $modelNode->addChild("bind", null, NS_XF);
														$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias().$col);
														$bindNode->addAttribute("type", 'string');
													}
												}
											}
										}
										break;
								}

							if ($fieldType) {
								$bindNode = $modelNode->addChild("bind", null, NS_XF);
								$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
								$bindNode->addAttribute("type", $fieldType);
								if($fieldIsMandatory) {
									$bindNode->addAttribute("required", 'true()');
								} else {
									$bindNode->addAttribute("required", 'false()');
								}
							}

							$index++;
						}
		return $xml;
	}
}
