<?php
namespace ControllerAction\View\Helper;

use Cake\Event\Event;
use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ControllerActionHelper extends Helper {
	public $helpers = ['Html', 'Form', 'Paginator', 'Label'];

	public $includes = [
		'datepicker' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min',
			'js' => 'ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min',
			'element' => 'ControllerAction.bootstrap-datepicker/datepicker'
		],
		'timepicker' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/timepicker/css/bootstrap-timepicker.min',
			'js' => 'ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min',
			'element' => 'ControllerAction.bootstrap-timepicker/timepicker'
		],
		'chosen' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/chosen/css/chosen.min',
			'js' => 'ControllerAction.../plugins/chosen/js/chosen.jquery.min'
		],
		'jasny' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/jasny/css/jasny-bootstrap.min',
			'js' => 'ControllerAction.../plugins/jasny/js/jasny-bootstrap.min'
		]
	];

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

					if ($attr['sort']) {
						$label = $this->Paginator->sort($field);
					} else {
						// attach event to get labels for fields
						$event = new Event('ControllerAction.Model.onGetLabel', $this, ['module' => $fieldModel, 'field' => $field, 'language' => $language]);
						$event = $table->eventManager()->dispatch($event);
						// end attach event

						if ($event->result) {
							$label = $event->result;
						}
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

			if ($event->result) {
				$value = $event->result;
				$obj->$field = $value;
			} else if ($this->endsWith($field, '_id')) {
				$associatedObject = $table->ControllerAction->getAssociatedEntityArrayKey($field);
				if ($obj->has($associatedObject) && $obj->$associatedObject->has('name')) {
					$value = $obj->$associatedObject->name;
					$obj->$field = $value;
				}
			}

			// trigger event for custom field types
			$method = 'onGet' . Inflector::camelize($type) . 'Element';
			$eventKey = 'ControllerAction.Model.' . $method;
			$event = $this->dispatchEvent($table, $eventKey, $method, ['action' => 'index', 'entity' => $obj, 'attr' => $attr, 'form' => $this->Form]);
			
			if (!empty($event->result)) {
				$value = $event->result;
			} else {
				// mapped to a current function in this class
				$function = 'get' . Inflector::camelize($type) . 'Element';
				if (method_exists($this, $function)) {
					$value = $this->$function('index', $obj, $attr);
				}
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

	// public function getExecuteButton($options) {
	// 	return $this->Html->link($options['buttonName'],array($options['actionURL'], $options['param']));
	// }

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

	// public function getIndexElement($value, $_fieldAttr) {
	// 	$_type = $_fieldAttr['type'];

	// 	// $function = 'get' . Inflector::camelize($_type) . 'Element';
	// 	// if (method_exists($this, $function)) {
	// 	// 	$value = $this->$function('view', $data, $_fieldAttr);
	// 	// }

	// 	switch ($_type) {
	// 		case 'element':
	// 			$element = $_fieldAttr['element'];
				
	// 			$value = $this->_View->element($element, ['attr' => $_fieldAttr]);
				
	// 			break;

	// 		case 'select':
	// 			if (!empty($_fieldAttr['options'])) {
	// 				reset($_fieldAttr['options']);
	// 				$firstKey = key($_fieldAttr['options']);
	// 				if (is_array($_fieldAttr['options'][$firstKey])) {
	// 					foreach ($_fieldAttr['options'] as $fkey => $fvalue) {
	// 						if ($fvalue['value'] == $value) {
	// 							$value = $fvalue['name'];
	// 						}
	// 					}
	// 				} else {
	// 					if (array_key_exists($value, $_fieldAttr['options'])) {
	// 						$value = $_fieldAttr['options'][$value];
	// 					}
	// 				}
	// 			}
	// 			break;

	// 		case 'text':
	// 			$value = nl2br($value);
	// 			break;

	// 		case 'image':
	// 			//$value = $this->Image->getBase64Image($data[$model][$_field . '_name'], $data[$model][$_field], $_fieldAttr['attr']);
	// 			break;
				
	// 		case 'download':
	// 			$value = $this->Html->link($value, $_fieldAttr['attr']['url']);
	// 			break;
				
	// 		case 'date':
	// 			//$value = $this->Utility->formatDate($value, null, false);
	// 			break;

	// 		case 'chosen_select':
	// 			$chosenSelectList = [];
	// 			if (!empty($value)) {
	// 				foreach ($value as $obj) {
	// 					$chosenSelectList[] = $obj->name;
	// 				}
	// 			}

	// 			$value = implode(', ', $chosenSelectList);
	// 			break;

	// 		case 'bool':
	// 			$value = $value==1 ? '<span class="green">&#10003;</span>' : '<span class="red">&#10008;</span>';
	// 			break;

	// 		case 'color':
	// 			$value = '<div style="background-color:'.$value.'">&nbsp;</div>';
	// 			break;
				
	// 		default:
	// 			break;
	// 	}
	// 	return $value;
	// }

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
				$event = new Event('ControllerAction.Model.onGetLabel', $this, ['module' => $_fieldModel, 'field' => $_field, 'language' => $language, 'autoHumanize' => false]);
				$event = $table->eventManager()->dispatch($event);
				// end attach event

				if ($event->result) {
					$label = $event->result;
				}
				if ($label !== false) {
					$options['label'] = $label;
				}

				// trigger event for custom field types
				$method = 'onGet' . Inflector::camelize($_type) . 'Element';
				$eventKey = 'ControllerAction.Model.' . $method;
				$event = $this->dispatchEvent($table, $eventKey, $method, ['action' => 'edit', 'entity' => $data, 'attr' => $_fieldAttr, 'form' => $this->Form, 'options' => $options]);
				
				if (!empty($event->result)) {
					$html .= $event->result;
				} else {
					// mapped to a current function in this class
					$function = 'get' . Inflector::camelize($_type) . 'Element';
					if (method_exists($this, $function)) {
						$html .= $this->$function('edit', $data, $_fieldAttr, $options);
					}
				}
			}
		}
		foreach ($this->includes as $include) {
			if ($include['include']) {
				if (array_key_exists('css', $include)) {
					echo $this->Html->css($include['css'], ['block' => true]);
				}
				if (array_key_exists('js', $include)) {
					echo $this->Html->script($include['js'], ['block' => true]);
				}
				if (array_key_exists('element', $include)) {
					echo $this->_View->element($include['element']);
				}
			}
		}
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

		$allowTypes = array('element', 'disabled', 'chosen_select');

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
				
				if (is_null($table)) {
					$table = TableRegistry::get($attr['className']);
				}

				// attach event to get labels for fields
				$event = new Event('ControllerAction.Model.onGetLabel', $this, ['module' => $_fieldModel, 'field' => $_field, 'language' => $language]);
				$event = $table->eventManager()->dispatch($event);
				// end attach event

				if ($event->result) {
					$label = $event->result;
				}

				// attach event for index columns
				$method = 'onGet' . Inflector::camelize($_field);
				$eventKey = 'ControllerAction.Model.' . $method;
				$event = $this->dispatchEvent($table, $eventKey, $method, ['entity' => $data]);
				// end attach event

				if ($event->result) {
					$value = $event->result;
					$data->$_field = $event->result;
				} else if ($this->endsWith($_field, '_id')) {
					$table = TableRegistry::get($attr['className']);
					$associatedObject = $table->ControllerAction->getAssociatedEntityArrayKey($_field);
					if (is_object($data->$associatedObject)) {
						$value = $data->$associatedObject->name;
						$data->$_field = $value;
					}
				}

				// trigger event for custom field types
				$method = 'onGet' . Inflector::camelize($_type) . 'Element';
				$eventKey = 'ControllerAction.Model.' . $method;
				$event = $this->dispatchEvent($table, $eventKey, $method, ['action' => 'view', 'entity' => $data, 'attr' => $_fieldAttr, 'form' => $this->Form]);
				
				if (!empty($event->result)) {
					$value = $event->result;
				} else {
					// mapped to a current function in this class
					$function = 'get' . Inflector::camelize($_type) . 'Element';
					if (method_exists($this, $function)) {
						$value = $this->$function('view', $data, $_fieldAttr);
					}
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

	// Elements definition starts here

	public function getStringElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $data->$attr['field'];
		} else if ($action == 'edit') {
			$options['type'] = 'string';
			if (array_key_exists('length', $attr)) {
				$options['maxlength'] = $attr['length'];
			}
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function getSelectElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			if (!empty($attr['options'])) {
				reset($attr['options']);
				$firstKey = key($attr['options']);
				if (is_array($attr['options'][$firstKey])) {
					foreach ($attr['options'] as $fkey => $fvalue) {
						if ($fvalue['value'] == $value) {
							$value = $fvalue['name'];
						}
					}
				} else {
					$value = $data->$attr['field'];
					if (array_key_exists($value, $attr['options'])) {
						$value = $attr['options'][$value];
					}
				}
			} else {
				$value = $data->$attr['field'];
			}
		} else if ($action == 'edit') {
			if (isset($attr['options'])) {
				if (empty($attr['options'])) {
					//$options['empty'] = isset($attr['empty']) ? $attr['empty'] : $this->getLabel('general.noData');
				} else {
					if (isset($attr['default'])) {
						$options['default'] = $attr['default'];
					} else {
						// if (!empty($this->request->data)) {
						// 	if(!empty($this->request->data->$attr['field'])) {
						// 		$options['default'] = $this->request->data->$attr['field'];
						// 	}
						// }
					}
				}
				$options['options'] = $attr['options'];
			}
			if (isset($attr['attr'])) {
				$options = array_merge($options, $attr['attr']);
			}

			// get rid of options that obsolete and not the default
			// if (!empty($attr['options'])) {
			// 	reset($attr['options']);
			// 	$first_key = key($attr['options']);
			// 	if (is_array($attr['options'][$first_key])) {
			// 		foreach ($options['options'] as $okey => $ovalue) {
			// 			if (isset($ovalue['obsolete']) && $ovalue['obsolete'] == '1') {
			// 				if (!array_key_exists('default', $options) || $ovalue['value']!=$options['default']) {
			// 					unset($options['options'][$okey]);
			// 				}
			// 			}
			// 		}
			// 	}
			// }

			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function getTextElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = nl2br($data->$attr['field']);
		} else if ($action == 'edit') {
			$options['type'] = 'textarea';
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function getHiddenElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			// no logic required
		} else if ($action == 'edit') {
			$options['type'] = 'hidden';
			if (array_key_exists('value', $attr)) {
				$options['value'] = $attr['value'];
			}
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function getReadonlyElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$value = $attr['value'];
		} else if ($action == 'edit') {
			$this->getDisabledElement($action, $data, $attr, $options);
			$value = $this->Form->input('disabled-'.$attr['field'], $options);
			unset($options['disabled']);
			unset($options['value']);
			$value .= $this->getHiddenElement($action, $data, $attr, $options);
		}
		return $value;
	}

	public function getDisabledElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $attr['value'];
		} else if ($action == 'edit') {
			$options['type'] = 'text';
			$options['disabled'] = 'disabled';
			if (isset($attr['options']) && !isset($attr['attr']['value'])) {
				$options['value'] = $attr['options'][$data->$attr['field']];
			} elseif (isset($attr['attr']['value'])) {
				$options['value'] = $attr['attr']['value'];
			} else {
				$options['value'] = $data->$attr['field'];
			}
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function getImageElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		$defaultWidth = 90;
		$defaultHeight = 115;

		//Get image default width and height if specified in entity class
		//else default values
		//$defaultWidth = ($data::imageWidth > 0) ? ($data::imageWidth) : $defaultWidth;
		//$defaultHeight = ($data::imageHeight > 0) ? ($data::imageHeight) : $defaultHeight;

		if ($action == 'index' || $action == 'view') {
			if (!is_null($data->photo_content)) {
				$file = ''; 
				while (!feof($data->photo_content)) {
					$file .= fread($data->photo_content, 8192); 
				} 
				fclose($data->photo_content); 	
			}
			$src = null;
			if(!empty($data->photo_name) && !empty($file)) {
				$temp = explode('.', $data->photo_name);
				$ext = strtolower(array_pop($temp));
				if($ext === 'jpg') {
					$ext = 'jpeg';
				}
				$src = sprintf('data: image/%s; base64,%s', $ext, base64_encode($file));
				$value = '<img src="'.$src.'" class="profile-image" alt="90x115" />';
			} else {
				$value = $this->Html->image('Student.default_student_profile.jpg');
			}
		} else if ($action == 'edit') {
			// $imageAttr = $attr['attr'];
			// $imageAttr['field'] = $_field;
			// $imageAttr['label'] = $label;
			// if (isset($data->{$_field . '_name'}) && isset($data->$_field)) {
			// 	$imageAttr['src'] = $this->Image->getBase64($data->{$_field . '_name'}, $data->$_field);
			// }
			// echo $this->_View->element('layout/file_upload_preview', $imageAttr);

			//$style = 'width: ' . $defaultWidth . 'px; height: ' . $defaultHeight . 'px';
			$defaultImageFromHolder = '<img data-src="holder.js/100%x100%" alt="...">';
			$showRemoveButton = false;

			$tmp_file = ((is_array($data[$attr['field']])) && (file_exists($data[$attr['field']]['tmp_name']))) ? $data[$attr['field']]['tmp_name'] : "";
			$tmp_file_read = (!empty($tmp_file)) ? file_get_contents($tmp_file) : ""; 

			$src = (!empty($tmp_file_read)) ? '<img id="existingImage" data-src="holder.js/'.$defaultWidth.'x'.$defaultHeight.'" src="data:image/jpeg;base64,'.base64_encode( $tmp_file_read ).'"/>' : $defaultImageFromHolder;
			$showRemoveButton = (!empty($tmp_file)) ? true : false; 

			if(!is_array($data[$attr['field']])) {
			  $imageContent = !is_null($data[$attr['field']]) ? stream_get_contents($data[$attr['field']]) : "";
			  $src = (!empty($imageContent)) ? '<img id="existingImage" data-src="holder.js/'.$defaultWidth.'x'.$defaultHeight.'" src="data:image/jpeg;base64,'.base64_encode( $imageContent ).'"/>' : $defaultImageFromHolder;
			  $showRemoveButton = true;	
			}

			header('Content-Type: image/jpeg'); 

			$this->includes['jasny']['include'] = true;
			$value = $this->_View->element('ControllerAction.bootstrap-jasny/image_uploader', ['attr' => $attr, 'src' => $src, 
																							'defaultImageFromHolder' => $defaultImageFromHolder, 
																							'defaultWidth' => $defaultWidth,
																							'defaultHeight' => $defaultHeight,
																							'showRemoveButton' => $showRemoveButton]);

		} else if ($action == 'index') {
			if(!empty($data->photo_content)){
				$style = 'width: ' . $defaultWidth . 'px; height: ' . $defaultHeight . 'px';
            	$value = '<img src="data:image/jpeg;base64,'.base64_encode( stream_get_contents($data->photo_content) ).'" style="'.$style.'"/>';
			}	
		}
		return $value;
	}

	public function getDownloadElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $this->Html->link($data->{$attr['field']}, $attr['attr']['url']);
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	public function getElementElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';

		$element = $attr['element'];
		$attr['id'] = $attr['model'] . '_' . $attr['field'];
		if ($action == 'view' || $action == 'index') {
			$value = $this->_View->element($element, ['attr' => $attr]);
		} else if ($action == 'edit') {
			$value = $this->_View->element($element, ['attr' => $attr]);
		}
		return $value;
	}

	public function getDateTimeElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		$_options = [
			'format' => 'dd-mm-yyyy H:i:s',
			'todayBtn' => 'linked',
			'orientation' => 'top auto'
		];

		if (!isset($attr['date_options'])) {
			$attr['date_options'] = [];
		}

		$field = $attr['field'];
		$value = $data->$field;

		if ($action == 'index' || $action == 'view') {
			if (!is_null($value)) {
				$table = TableRegistry::get($attr['className']);
				$event = new Event('ControllerAction.Model.onFormatDateTime', $this, compact('value'));
				$event = $table->eventManager()->dispatch($event);
				if (strlen($event->result) > 0) {
					$value = $event->result;
				}
			}
		}
		return $value;
	}

	public function getDateElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		$_options = [
			'format' => 'dd-mm-yyyy',
			'todayBtn' => 'linked',
			'orientation' => 'top auto'
		];

		if (!isset($attr['date_options'])) {
			$attr['date_options'] = [];
		}
		if (!isset($attr['default_date'])) {
			$attr['default_date'] = true;
		}

		$field = $attr['field'];
		$value = $data->$field;

		if ($action == 'index' || $action == 'view') {
			if (!is_null($value)) {
				$table = TableRegistry::get($attr['className']);
				$event = new Event('ControllerAction.Model.onFormatDate', $this, compact('value'));
				$event = $table->eventManager()->dispatch($event);
				if (strlen($event->result) > 0) {
					$value = $event->result;
				}
			}
		} else if ($action == 'edit') {
			$attr['id'] = $attr['model'] . '_' . $field;
			$attr['date_options'] = array_merge($_options, $attr['date_options']);
			if (!is_null($value)) {
				$attr['value'] = date('d-m-Y', strtotime($value));
			} else if ($attr['default_date']) {
				$attr['value'] = date('d-m-Y');
			}

			if (!is_null($this->_View->get('datepicker'))) {
				$datepickers = $this->_View->get('datepicker');
				$datepickers[] = $attr;
				$this->_View->set('datepicker', $datepickers);
			} else {
				$this->_View->set('datepicker', [$attr]);
			}
			$value = $this->_View->element('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);
			$this->includes['datepicker']['include'] = true;
		}
		return $value;
	}

	public function getTimeElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		$_options = [
			'defaultTime' => false
		];

		if (!isset($attr['time_options'])) {
			$attr['time_options'] = [];
		}
		if (!isset($attr['default_time'])) {
			$attr['default_time'] = true;
		}

		$field = $attr['field'];
		$value = $data->$field;

		if ($action == 'index' || $action == 'view') {
			if (!is_null($value)) {
				$table = TableRegistry::get($attr['className']);
				$event = new Event('ControllerAction.Model.onFormatTime', $this, compact('value'));
				$event = $table->eventManager()->dispatch($event);
				if (strlen($event->result) > 0) {
					$value = $event->result;
				}
			}
		} else if ($action == 'edit') {
			$attr['id'] = $attr['model'] . '_' . $field;
			$attr['time_options'] = array_merge($_options, $attr['time_options']);
			if (!is_null($value)) {
				$attr['value'] = date('h:i A', strtotime($value));
				$attr['time_options']['defaultTime'] = $attr['value'];
			} else if ($attr['default_time']) {
				$attr['time_options']['defaultTime'] = date('h:i A');
			}
			if (!is_null($this->_View->get('timepicker'))) {
				$timepickers = $this->_View->get('timepicker');
				$timepickers[] = $attr;
				$this->_View->set('timepicker', $timepickers);
			} else {
				$this->_View->set('timepicker', [$attr]);
			}
			$value = $this->_View->element('ControllerAction.bootstrap-timepicker/timepicker_input', ['attr' => $attr]);
			$this->includes['timepicker']['include'] = true;
		}

		return $value;
	}

	public function getChosenSelectElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';

		$_options = [
			'class' => 'chosen-select',
			'multiple' => true
		];

		if ($action == 'index') {
			$value = $data->$attr['field'];
			$chosenSelectList = [];
			if (!empty($value)) {
				foreach ($value as $obj) {
					$chosenSelectList[] = $obj->name;
				}
			}

			$value = implode(', ', $chosenSelectList);
		} else if ($action == 'view') {
			$chosenSelectList = [];
			if (!empty($data->$attr['fieldNameKey'])) {
				foreach ($data->$attr['fieldNameKey'] as $obj) {
					$chosenSelectList[] = $obj->name;
				}
			}
			$value = implode(', ', $chosenSelectList);
		} else if ($action == 'edit') {
			$_options['options'] = isset($attr['options']) ? $attr['options'] : [];
			$_options['data-placeholder'] = isset($attr['placeholder']) ? $attr['placeholder'] : '';
			$options = array_merge($_options, $options);

			$this->includes['chosen']['include'] = true;

			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function getBinaryElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$table = TableRegistry::get($attr['className']);
			$fileUpload = $table->behaviors()->get('FileUpload');
			$name = '&nbsp;';
			if (!empty($fileUpload)) {
				$name = $fileUpload->config('name');
			}
			$buttons = $this->_View->get('_buttons');
			$action = $buttons['download']['url'];
			$value = $this->Html->link($data->$name, $action);
		} else if ($action == 'edit') {
			$this->includes['jasny']['include'] = true;
			$value = $this->_View->element('ControllerAction.file_input', ['attr' => $attr]);
		}
		return $value;
	}

	public function getColorElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = '<div style="background-color:'.$data->$attr['field'].'">&nbsp;</div>';
		} else if ($action == 'edit') {
			$options['type'] = 'color';
			$options['onchange'] = 'clickColor(0, -1, -1, 5);';
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	// a template function for creating new elements
	public function getTestElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	// Elements definition ends here
}
