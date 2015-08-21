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

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];

		$models = $this->config('models');
		foreach ($models as $key => $model) {
			if (!is_null($model)) {
				$this->{$key} = TableRegistry::get($model);
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
		$formKey = Inflector::underscore(Inflector::singularize($this->Form->alias())) . '_id';

		$query->innerJoin(
				[$SurveyStatuses->alias() => $SurveyStatuses->table()],
				[
					$SurveyStatuses->aliasField($formKey . ' = ') . $this->Form->aliasField('id'),
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

			$moduleKey = Inflector::underscore(Inflector::singularize($this->Module->alias())) . '_id';
			$query->where([
				$this->Form->aliasField($moduleKey) => $selectedModule
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
			foreach ($institutions as $institutionId => $institution) {
				$list[] = array(
					'id' => $institutionId,
					'name' => $institution,
					// 'forms' => $forms
				);
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
	}

	public function getXForms($instanceId, $id) {
		$title = $this->Form->get($id)->name;
		$title = htmlspecialchars($title, ENT_QUOTES);

		$fieldContains = [];
		$fieldContains = isset($this->FieldOption) ? array_merge($fieldContains, [$this->FieldOption->alias()]) : $fieldContains;
		$fieldContains = isset($this->TableColumn) ? array_merge($fieldContains, [$this->TableColumn->alias()]) : $fieldContains;
		$fieldContains = isset($this->TableRow) ? array_merge($fieldContains, [$this->TableRow->alias()]) : $fieldContains;

		$fieldKey = Inflector::underscore(Inflector::singularize($this->Field->alias())) . '_id';
		$formKey = Inflector::underscore(Inflector::singularize($this->Form->alias())) . '_id';
		$fields = $this->FormField
			->find()
			->find('order')
			->select([
				'form_id' => $this->FormField->aliasField($formKey),
				'field_id' => $this->FormField->aliasField($fieldKey),
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
				[$this->Field->aliasField('id =') . $this->FormField->aliasField($fieldKey)]
			)
			->where([
				$this->FormField->aliasField($formKey) => $id
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

						$formNode = $instanceNode->addChild($this->Field->alias(), null, NS_OE);
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

							// Table: separate xform group
							if ($field->field_type == 'TABLE') {
								$sectionBreakNode = $bodyNode->addChild("group", null, NS_XF);
								$sectionBreakNode->addAttribute("ref", $field->field_id);
								$sectionBreakNode->addAttribute("oe-type", "table");
								$sectionBreakNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
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
													$this->FieldOption->aliasField($fieldKey) => $field->field_id
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
													$this->FieldOption->aliasField($fieldKey) => $field->field_id
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
										/* Nested xform group
										$tableBreakNode = $sectionBreakNode->addChild("group", null, NS_XF);
										$tableBreakNode->addAttribute("ref", $field->field_id);
										$tableBreakNode->addAttribute("oe-type", "table");
										$tableBreakNode->addChild("label", htmlspecialchars($fieldName, ENT_QUOTES), NS_XF);
										$tableNode = $tableBreakNode->addChild("table", null, NS_XHTML);
										*/

										$tableNode = $sectionBreakNode->addChild("table", null, NS_XHTML);
										$tableNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]");
											$tableHeader = $tableNode->addChild("tr", null, NS_XHTML);
											$tableHeader->addChild("th", null, NS_XHTML);
											$tableBody = $tableNode->addChild("tbody", null, NS_XHTML);
												$xformRepeat = $tableBody->addChild("repeat", null, NS_XF);
												$xformRepeat->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableRow->alias());
													$tbodyRow = $xformRepeat->addChild("tr", null, NS_XHTML);
														$tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
															$tbodyCell = $tbodyColumn->addChild("output", null, NS_XF);
																$tbodyCell->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias()."0");

										$tableColumnResults = $this->TableColumn
											->find()
											->find('order')
											->where([
												$this->TableColumn->aliasField($fieldKey) => $field->field_id
											])
											->all();

										$tableRowResults = $this->TableRow
											->find()
											->find('order')
											->where([
												$this->TableRow->aliasField($fieldKey) => $field->field_id
											])
											->all();

										if (!$tableColumnResults->isEmpty() && !$tableRowResults->isEmpty()) {
											$tableColumns = $tableColumnResults->toArray();
											$tableRows = $tableRowResults->toArray();

											foreach ($tableRows as $row => $tableRow) {
												$rowNode = $fieldNode->addChild($this->TableRow->alias(), null, NS_OE);
												$rowNode->addAttribute("id", $tableRow->id);
													$colIndex = 0;

													$columnNode = $rowNode->addChild($this->TableColumn->alias() . $colIndex, htmlspecialchars($tableRow->name, ENT_QUOTES), NS_OE);
													$columnNode->addAttribute("id", $colIndex);

													foreach ($tableColumns as $col => $tableColumn) {
														$colIndex++;
														$columnNode = $rowNode->addChild($this->TableColumn->alias() . $colIndex, null, NS_OE);
														$columnNode->addAttribute("id", $tableColumn->id);
														if ($row == 0) {
															$tableHeader->addChild("th", htmlspecialchars($tableColumn->name, ENT_QUOTES), NS_XHTML);
															$tbodyColumn = $tbodyRow->addChild("td", null, NS_XHTML);
																$tbodyCell = $tbodyColumn->addChild("input", null, NS_XF);
																	$tbodyCell->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias().$colIndex);

															$bindNode = $modelNode->addChild("bind", null, NS_XF);
															$bindNode->addAttribute("ref", "instance('" . $instanceId . "')/".$this->Form->alias()."/".$this->Field->alias()."[".$index."]"."/".$this->TableColumn->alias().$colIndex);
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
