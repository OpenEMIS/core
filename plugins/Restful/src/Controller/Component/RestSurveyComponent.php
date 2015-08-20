<?php
namespace Restful\Controller\Component;

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
	}

	public function schools() {
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
			// $fileName = $format . '_' . date('Ymdhis') . '.xml';
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
				'section' => $this->FormField->aliasField('section'),
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

		return $xml;
	}
}
