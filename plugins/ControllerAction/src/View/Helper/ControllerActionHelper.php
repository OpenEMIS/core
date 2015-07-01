<?php
namespace ControllerAction\View\Helper;

use Cake\Event\Event;
use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ControllerActionHelper extends Helper {
	public $helpers = ['Html', 'ControllerAction.HtmlField', 'Form', 'Paginator', 'Label'];

	public function getColumnLetter($columnNumber) {
        if ($columnNumber > 26) {
            $columnLetter = Chr(intval(($columnNumber - 1) / 26) + 64) . Chr((($columnNumber - 1) % 26) + 65);
        } else {
            $columnLetter = Chr($columnNumber + 64);
        }
        return $columnLetter;
    }

	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	public function dispatchEvent($subject, $eventKey, $method=null, $params=[]) {
		$eventMap = $subject->implementedEvents();
		$event = new Event($eventKey, $this, $params);

		if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
			if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
				$subject->eventManager()->on($eventKey, [], [$subject, $method]);
			}
		}
		return $subject->eventManager()->dispatch($event);
	}

	public function getFormTemplate() {
		return [
			'select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>'
		];
	}

	public function getFormDefaults() {
		$defaults = array(
			'div' => 'form-group',
			'label' => array('class' => 'col-md-3 form-label'),
			'between' => '<div class="col-md-4">',
			'after' => '</div>',
			'class' => 'form-control'
		);
		return $defaults;
	}

	public function getFormOptions() {
		$options = [
			'class' => 'form-horizontal',
			'novalidate' => true
		];

		$fields = $this->_View->get('_fields');
		if (!empty($fields)) {
			$types = ['binary','image'];
			foreach ($fields as $key => $attr) {
				if (in_array($attr['type'], $types)) {
					$options['type'] = 'file';
					break;
				}
			}
		}
		
		return $options;
	}

	public function getFormButtons() {
		$buttons = $this->_View->get('_buttons');
	
		echo '<div class="form-buttons"><div class="button-label"></div>';
		echo $this->Form->button(__('Save'), array('class' => 'btn btn-default btn-save', 'div' => false, 'name' => 'submit', 'value' => 'save'));
		echo $this->Html->link(__('Cancel'), $buttons['back']['url'], array('class' => 'btn btn-outline btn-cancel'));
		echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
		echo '</div>';
	}

	public function highlight($needle, $haystack){
		// to cater for photos returning resource
		if (is_resource($haystack)) { return $haystack; }
		
		$ind = stripos($haystack, $needle);
		$len = strlen($needle);
		$value = $haystack;
		if ($ind !== false) {
			$value = substr($haystack, 0, $ind) . "<span class=\"highlight\">" . substr($haystack, $ind, $len) . "</span>" .
				$this->highlight($needle, substr($haystack, $ind + $len));
		}
		return $value;
	}

	public function isFieldVisible($attr, $type) {
		$visible = false;

		if (array_key_exists('visible', $attr)) {
			$visibleField = $attr['visible'];

			if (is_bool($visibleField)) {
				$visible = $visibleField;
			} else if (is_array($visibleField)) {
				if (array_key_exists($type, $visibleField)) {
					$visible = isset($visibleField[$type]) ? $visibleField[$type] : true;
				}
			}
		}
		return $visible;
	}

	public function getTableHeaders($fields, $model, &$dataKeys) {
		$excludedTypes = array('hidden', 'file', 'file_upload');
		$attrDefaults = array(
			'type' => 'string',
			'model' => $model,
			'sort' => false
		);

		$tableHeaders = array();
		$table = null;
		$session = $this->request->session();
		$language = $session->read('System.language');

		foreach ($fields as $field => $attr) {
			$attr = array_merge($attrDefaults, $attr);
			$type = $attr['type'];
			$visible = $this->isFieldVisible($attr, 'index');
			$label = '';

			if ($visible && $type != 'hidden') {
				$fieldModel = $attr['model'];
				
				if (!in_array($type, $excludedTypes)) {
					if (is_null($table)) {
						$table = TableRegistry::get($attr['className']);
					}

					// attach event to get labels for fields
					$event = new Event('ControllerAction.Model.onGetFieldLabel', $this, ['module' => $fieldModel, 'field' => $field, 'language' => $language]);
					$event = $table->eventManager()->dispatch($event);
					// end attach event

					if ($event->result) {
						$label = $event->result;
					}

					if ($attr['sort']) {
						$title = ($label!='') ? $label : $field;
						$label = $this->Paginator->sort($field, $title);
					}
					
					$method = 'onGet' . Inflector::camelize($field);
					$eventKey = 'ControllerAction.Model.' . $method;

					$event = new Event($eventKey, $this);
					if (method_exists($table, $method) || $table->behaviors()->hasMethod($method)) {
						$table->eventManager()->on($eventKey, [], [$table, $method]);
		            }

					if (isset($attr['tableHeaderClass'])) {
						$tableHeaders[] = array($label => array('class' => $attr['tableHeaderClass']));
					} else {
						$tableHeaders[] = $label;
					}
					$dataKeys[$field] = $attr;
				}
			}
		}
		return $tableHeaders;
	}

	public function getTableRow($obj, $fields) {
		$row = array();

		$search = '';
		if (isset($this->request->data['Search']) && array_key_exists('searchField', $this->request->data['Search'])) {
			$search = $this->request->data['Search']['searchField'];
		}

		$table = null;

		foreach ($fields as $field => $attr) {
			$model = $attr['model'];
			$value = $obj->$field;
			$type = $attr['type'];

			if (!empty($search)) {
				$value = $this->highlight($search, $value);
			}

			if (is_null($table)) {
				$table = TableRegistry::get($attr['className']);
			}

			// attach event for index columns
			$method = 'onGet' . Inflector::camelize($field);
			$eventKey = 'ControllerAction.Model.' . $method;

			$event = new Event($eventKey, $this, ['entity' => $obj]);
			$event = $table->eventManager()->dispatch($event);
			// end attach event

			$associatedFound = false;
			if (strlen($event->result) > 0) {
				$value = $event->result;
				$obj->$field = $value;
			} else if ($this->endsWith($field, '_id')) {
				$associatedObject = $table->ControllerAction->getAssociatedEntityArrayKey($field);
				if ($obj->has($associatedObject) && $obj->$associatedObject->has('name')) {
					$value = $obj->$associatedObject->name;
					$associatedFound = true;
				}
			}

			if (!$associatedFound) {
				$value = $this->HtmlField->render($type, 'index', $obj, $attr);
			}

			if (isset($attr['tableRowClass'])) {
				$row[] = array($value, array('class' => $attr['tableRowClass']));
			} else {
				$row[] = $value;
			}
		}
		return $row;
	}

	public function getLabel($model, $field, $attr=array()) {
		return $this->Label->getLabel($model, $field, $attr);
	}

	public function getPaginatorButtons($type='prev') {
		$icon = array('prev' => '&laquo', 'next' => '&raquo');
		$html = $this->Paginator->{$type}(
			$icon[$type],
			array('tag' => 'li', 'escape' => false),
			null,
			array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a', 'escape' => false)
		);
		return $html;
	}

	public function getPaginatorNumbers() {
		$html = $this->Paginator->numbers(array(
			'tag' => 'li', 
			'currentTag' => 'a', 
			'currentClass' => 'active', 
			'separator' => '', 
			'modulus' => 4, 
			'first' => 2,
			'last' => 2,
			'ellipsis' => '<li><a>...</a></li>'
		));
		return $html;
	}

	public function getPageOptions() {
		$html = '';
		if (!is_null($this->_View->get('pageOptions'))) {
			$pageOptions = $this->_View->get('pageOptions');
			
			if (!empty($pageOptions)) {
				$html = '<span>' . __('Display') . '</span>';
				$html .= $this->Form->create(NULL, ['type' => 'post', 'style' => 'display: inline-block']);
				$html .= $this->Form->input('Search.limit', [
					'label' => false,
					'options' => $pageOptions,
					'onchange' => "$(this).closest('form').submit()",
					'templates' => $this->getFormTemplate()
				]);
				$html .= '<p>' . __('records') . '</p>';
				$html .= $this->Form->end();
			}
		}
		return $html;
	}

	public function getEditElements(Entity $data, $fields = [], $exclude = []) {
		$_fields = $this->_View->get('_fields');

		$html = '';
		$model = $this->_View->get('model');
		$displayFields = $_fields;

		if (!empty($fields)) { // if we only want specific fields to be displayed
			foreach ($displayFields as $_field => $attr) {
				if (!in_array($displayFields, $fields)) {
					unset($displayFields[$_field]);
				}
			}
		}

		if (!empty($exclude)) {
			foreach ($exclude as $f) {
				if (array_key_exists($f, $displayFields)) {
					unset($displayFields[$f]);
				}
			}
		}

		$_attrDefaults = [
			'type' => 'string',
			'model' => $model,
			'label' => true
		];

		$table = null;
		$session = $this->request->session();
		$language = $session->read('System.language');

		foreach ($displayFields as $_field => $attr) {
			$_fieldAttr = array_merge($_attrDefaults, $attr);
			$visible = $this->isFieldVisible($_fieldAttr, 'edit');
			$label = false;

			if ($visible) {
				$_type = $_fieldAttr['type'];
				$_fieldModel = $_fieldAttr['model'];
				$fieldName = $_fieldModel . '.' . $_field;
				$options = isset($_fieldAttr['attr']) ? $_fieldAttr['attr'] : array();
				
				if (is_null($table)) {
					$table = TableRegistry::get($attr['className']);
				}

				// attach event to get labels for fields
				$event = new Event('ControllerAction.Model.onGetFieldLabel', $this, ['module' => $_fieldModel, 'field' => $_field, 'language' => $language, 'autoHumanize' => true]);
				$event = $table->eventManager()->dispatch($event);
				// end attach event

				if ($event->result) {
					$label = $event->result;
				}
				if ($label !== false) {
					$_fieldAttr['label'] = $label;
					$options['label'] = $label;
				}

				$html .= $this->HtmlField->render($_type, 'edit', $data, $_fieldAttr, $options);
			}
		}
		$this->HtmlField->includes();
		return $html;
	}

	public function getViewElements(Entity $data, $fields = [], $exclude = []) {
		//  1. implemented override param for nav_tabs to omit label
		//  2. for case 'element', implemented $elementData for $this->_View->element($element, $elementData)
		$_fields = $this->_View->get('_fields');

		$html = '';
		$row = $_labelCol = $_valueCol = '<div class="%s">%s</div>';
		$_rowClass = array('row');
		$_labelClass = array('col-xs-6 col-md-3 form-label'); // default bootstrap class for labels
		$_valueClass = array('form-input'); // default bootstrap class for values

		$allowTypes = array('element', 'disabled', 'chosenSelect');

		$displayFields = $_fields;

		if (!empty($fields)) { // if we only want specific fields to be displayed
			foreach ($displayFields as $_field => $attr) {
				if (!in_array($displayFields, $fields)) {
					unset($displayFields[$_field]);
				}
			}
		}

		if (!($exclude)) {
			foreach ($exclude as $f) {
				if (array_key_exists($f, $displayFields)) {
					unset($displayFields[$f]);
				}
			}
		}

		$_attrDefaults = array(
			'type' => 'string',
			'label' => true,
			'rowClass' => '',
			'labelClass' => '',
			'valueClass' => ''
		);

		$table = null;
		$session = $this->request->session();
		$language = $session->read('System.language');

		foreach ($displayFields as $_field => $attr) {
			$_fieldAttr = array_merge($_attrDefaults, $attr);
			$_type = $_fieldAttr['type'];
			$visible = $this->isFieldVisible($_fieldAttr, 'view');
			$value = $data->$_field;
			$label = '';

			if ($visible && $_type != 'hidden') {
				$_fieldModel = $_fieldAttr['model'];
				$options = isset($_fieldAttr['attr']) ? $_fieldAttr['attr'] : array();
				
				if (is_null($table)) {
					$table = TableRegistry::get($attr['className']);
				}

				// attach event to get labels for fields
				$event = new Event('ControllerAction.Model.onGetFieldLabel', $this, ['module' => $_fieldModel, 'field' => $_field, 'language' => $language]);
				$event = $table->eventManager()->dispatch($event);
				// end attach event

				if ($event->result) {
					$label = $event->result;
				}
				if (isset($options['label'])) {
					$label = $options['label'];
				}

				// attach event for index columns
				$method = 'onGet' . Inflector::camelize($_field);
				$eventKey = 'ControllerAction.Model.' . $method;
				$event = $this->dispatchEvent($table, $eventKey, $method, ['entity' => $data]);
				// end attach event

				$associatedFound = false;
				if ($event->result) {
					$value = $event->result;
					$data->$_field = $event->result;
				} else if ($this->endsWith($_field, '_id')) {
					$table = TableRegistry::get($attr['className']);
					$associatedObject = $table->ControllerAction->getAssociatedEntityArrayKey($_field);
					
					if ($data->has($associatedObject)) {
						$value = $data->$associatedObject->name;
						$associatedFound = true;
					}
				}

				if (!$associatedFound) {
					$value = $this->HtmlField->render($_type, 'view', $data, $_fieldAttr, $options);
				}

				if (is_string($value) && strlen(trim($value)) == 0) {
					$value = '&nbsp;';
				}

				if (!empty($_fieldAttr['rowClass'])) {
					$_rowClass[] = $_fieldAttr['rowClass'];
				}
				if (!empty($_fieldAttr['labelClass'])) {
					$_labelClass[] = $_fieldAttr['labelClass'];
				}
				if (!empty($_fieldAttr['valueClass'])) {
					$_valueClass[] = $_fieldAttr['valueClass'];
				}

				$valueClass = implode(' ', $_valueClass);
				$rowClass = implode(' ', $_rowClass);

				if ($_fieldAttr['label']) {
					$labelClass = implode(' ', $_labelClass);
					$rowContent = sprintf($_labelCol.$_valueCol, $labelClass, $label, $valueClass, $value);
				} else { // no label
					$rowContent = sprintf($_valueCol, $valueClass, $value);
				}
				if (!array_key_exists('override', $_fieldAttr)) {
					$html .= sprintf($row, $rowClass, $rowContent);
				} else {
					$html .= '<div class="row">' . $value . '</div>';
				}
			}
		}
		return $html;
	}
}
