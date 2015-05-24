<?php
namespace ControllerAction\View\Helper;

use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ControllerActionHelper extends Helper {
	public $helpers = ['Html', 'Form', 'Paginator', 'Label'];

	public $includes = [
		'datepicker' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/datepicker/css/datepicker',
			'js' => 'ControllerAction.../plugins/datepicker/js/bootstrap-datepicker',
			'element' => 'ControllerAction.datepicker'
		],
		'timepicker' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/timepicker/bootstrap-timepicker',
			'js' => 'ControllerAction.../plugins/timepicker/bootstrap-timepicker',
			'element' => 'ControllerAction.timepicker'
		],
		'chosen' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/chosen/chosen.min',
			'js' => 'ControllerAction.../plugins/chosen/chosen.query.min'
		],
		'jasny' => [
			'include' => false,
			'css' => 'ControllerAction.../plugins/jasny/css/jasny-bootstrap.min',
			'js' => 'ControllerAction.../plugins/jasny/js/jasny-bootstrap.min'
		]
	];

	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	public function getFormTemplate() {
		return [
			'inputContainer' => '<div class="form-group row">{{content}}</div>',
			'input' => '<div class="col-md-4"><input type="{{type}}" name="{{name}}" {{attrs}} class="form-control" /></div>',
			'label' => '<label class="col-md-3 form-label" {{attrs}}>{{text}}</label>',
			'select' => '<div class="col-md-4"><select class="form-control" name="{{name}}" {{attrs}}>{{content}}</select></div>',
			//'error' => '<div class="form-group row error">{{text}}</div>'
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
			$types = ['binary'];
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
	
		echo '<div class="form-buttons">';
		echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
		echo $this->Form->button(__('Save'), array('class' => 'btn btn-default btn-save', 'div' => false));
		echo $this->Html->link(__('Cancel'), $buttons['back']['url'], array('class' => 'btn btn-outline btn-cancel'));
		echo '</div>';
	}

	public function highlight($needle, $haystack){
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
		$excludedTypes = array('hidden', 'image', 'file', 'file_upload', 'element');
		$attrDefaults = array(
			'type' => 'string',
			'model' => $model,
			'sort' => false
		);

		$tableHeaders = array();
		foreach ($fields as $field => $attr) {
			$attr = array_merge($attrDefaults, $attr);
			$type = $attr['type'];
			$visible = $this->isFieldVisible($attr, 'index');

			if ($visible && $type != 'hidden') {
				$fieldModel = $attr['model'];
				
				if (!in_array($type, $excludedTypes)) {
					if ($attr['sort']) {
						$label = $this->Paginator->sort($field);
					} else {
						$label = $this->getLabel($fieldModel, $field, $attr);
						if ($this->endsWith($field, '_id') && $this->endsWith($label, ' Id')) {
							$label = str_replace(' Id', '', $label);
						}
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

		foreach ($fields as $field => $attr) {
			$model = $attr['model'];
			$value = $obj->$field;

			if ($this->endsWith($field, '_id')) {
				$associatedObject = str_replace('_id', '', $field);
				if (is_object($obj->$associatedObject)) {
					$value = $obj->$associatedObject->name;
				}
			}
			
			$value = $this->getIndexElement($value, $attr);
			if (!empty($search)) {
				$value = $this->highlight($search, $value);
			}

			if (isset($attr['tableRowClass'])) {
				$row[] = array($value, array('class' => $attr['tableRowClass']));
			} else {
				$row[] = $value;
			}
		}
		return $row;
	}
	
	public function getExecuteButton($options) {
		return $this->Html->link($options['buttonName'],array($options['actionURL'], $options['param']));
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
				$html = __('<span>Display</span>');
				$html .= $this->Form->create(NULL, ['type' => 'post', 'style' => 'display: inline-block']);
				$html .= $this->Form->input('Search.limit', [
					'label' => false,
					'options' => $pageOptions,
					'onchange' => "$(this).closest('form').submit()"
				]);
				$html .= __('records');
				$html .= $this->Form->end();
			}
		}
		return $html;
	}

	public function getLabel($model, $field, $attr=array()) {
		return $this->Label->getLabel($model, $field, $attr);
	}

	public function datepicker($field, $options=array()) {
		$dateFormat = 'dd-mm-yyyy';
		$icon = '<span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
		$_options = array(
			'id' => 'date',
			'data-date-format' => $dateFormat,
			'data-date-autoclose' => 'true',
			'label' => false,
			'disabled' => false
		);
		$label = isset($options['label']) ? $options['label'] : $_options['label'];
		unset($_options['label']);
		$disabled = isset($options['disabled']) ? $options['disabled'] : $_options['disabled'];
		unset($_options['disabled']);
		$wrapper = $this->Html->div('input-group date', null, $_options);
		if(!empty($options)) {
			$_options = array_merge($_options, $options);
		}
		$defaults = $this->Form->inputDefaults();
		$inputOptions = array(
			'id' => $_options['id'],
			'type' => 'text',
			'between' => $defaults['between'] . $wrapper,
			'after' => $icon . $defaults['after'],
			'value' => isset($_options['data-date'])?$_options['data-date']:date('d-m-Y')
		);
		$inputOptions = array_merge($_options, $inputOptions);

		if($label !== false) {
			$inputOptions['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
		}
		if($disabled !== false) {
			$inputOptions['disabled'] = $disabled;
		}
		$html = $this->Form->input($field, $inputOptions);
	
		$_datepickerOptions = array();
		$_datepickerOptions['id'] = $_options['id'];
		if(!empty($_options['startDate'])){
			$_datepickerOptions['startDate'] = $_options['startDate'];
		}
		if(!empty($_options['endDate'])){
			$_datepickerOptions['endDate'] = $_options['endDate'];
		}
		if($disabled !== false) {
			$_datepickerOptions['disabled'] = $disabled;
		}
		if (array_key_exists('dateOptions', $_options)) {
			$_datepickerOptions = array_merge($_datepickerOptions, $options['dateOptions']);
		}
		if(!is_null($this->_View->get('datepicker'))) {
			$datepickers = $this->_View->get('datepicker');
			$datepickers[] = $_datepickerOptions;
			$this->_View->set('datepicker', $datepickers);
		} else {
			$this->_View->set('datepicker', array($_datepickerOptions));
		}
		return $html;
	}
	
	public function timepicker($field, $options=array()) {
		$id = isset($options['id']) ? $options['id'] : 'time';
		$wrapper = '<div class="input-group bootstrap-timepicker">';
		$icon = '<span class="input-group-addon"><i class="fa fa-clock-o"></i></span></div>';
		$defaults = $this->Form->inputDefaults();
		if(!empty($options['label'])) {
			$options['label'] = array('text' => $options['label'], 'class' => $defaults['label']['class']);
		}
		$inputOptions = array(
			'id' => $id,
			'type' => 'text',
			'between' => $defaults['between'] . $wrapper,
			'after' => $icon . $defaults['after']
		);

		$inputOptions = array_merge($inputOptions, $options);
		$html = $this->Form->input($field, $inputOptions);
		
		$fields = array('id' => 'id', 'value' => 'defaultTime');
		$_timepickerOptions = array();
		foreach ($fields as $key => $field) {
			if (isset($inputOptions[$key])) {
				$_timepickerOptions[$field] = $inputOptions[$key];
			}
		}
		
		if(!is_null($this->_View->get('timepicker'))) {
			$timepickers = $this->_View->get('timepicker');
			$timepickers[] = $_timepickerOptions;
			$this->_View->set('timepicker', $timepickers);
		} else {
			$this->_View->set('timepicker', array($_timepickerOptions));
		}
		return $html;
	}

	public function getIndexElement($value, $_fieldAttr) {
		$_type = $_fieldAttr['type'];
		switch ($_type) {
			case 'disabled':
				$value = $_fieldAttr['value'];
				break;
				
			case 'select':
				if (!empty($_fieldAttr['options'])) {
					reset($_fieldAttr['options']);
					$firstKey = key($_fieldAttr['options']);
					if (is_array($_fieldAttr['options'][$firstKey])) {
						foreach ($_fieldAttr['options'] as $fkey => $fvalue) {
							if ($fvalue['value'] == $value) {
								$value = $fvalue['name'];
							}
						}
					} else {
						if (array_key_exists($value, $_fieldAttr['options'])) {
							$value = $_fieldAttr['options'][$value];
						}
					}
				}
				break;

			case 'text':
				$value = nl2br($value);
				break;

			case 'image':
				//$value = $this->Image->getBase64Image($data[$model][$_field . '_name'], $data[$model][$_field], $_fieldAttr['attr']);
				break;
				
			case 'download':
				$value = $this->Html->link($value, $_fieldAttr['attr']['url']);
				break;
				
			case 'element':
				$element = $_fieldAttr['element'];
				if (array_key_exists('class', $_fieldAttr)) {
					$class = $_fieldAttr['class'];
				}
				$value = $this->_View->element($element);
				break;
				
			case 'date':
				//$value = $this->Utility->formatDate($value, null, false);
				break;

			case 'chosen_select':
				$_fieldAttr['dataModel'] = isset($_fieldAttr['dataModel']) ? $_fieldAttr['dataModel'] : Inflector::classify($_field);
				$_fieldAttr['dataField'] = isset($_fieldAttr['dataField']) ? $_fieldAttr['dataField'] : 'id';
				//$value = $this->_View->element('ControllerAction/chosen_select', $_fieldAttr);

				$chosenSelectList = array();
				if (isset($data[$dataModel])) {
					foreach ($data[$dataModel] as $obj) {
						$chosenSelectData = isset($obj[$dataField]) ? $obj[$dataField] : '';
						if (!empty($chosenSelectData)) {
							$chosenSelectList[] = $chosenSelectData;
						}
					}
				}
				echo implode(', ', $chosenSelectList);
				break;

			case 'bool':
				$value = $value==1 ? '<span class="green">&#10003;</span>' : '<span class="red">&#10008;</span>';
				break;
			
			case 'modified_user_id':
			case 'created_user_id':
				$dataModel = $_fieldAttr['dataModel'];
				if (isset($data[$dataModel]['first_name']) && isset($data[$dataModel]['last_name'])) {
					$value = $data[$dataModel]['first_name'] . ' ' . $data[$dataModel]['last_name'];
				}
				break;
				
			default:
				break;
		}
		return $value;
	}

	public function getEditElements(Entity $data, $fields = [], $exclude = []) {
		$_fields = $this->_View->get('_fields');
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

		foreach ($displayFields as $_field => $attr) {
			$_fieldAttr = array_merge($_attrDefaults, $attr);
			$visible = $this->isFieldVisible($_fieldAttr, 'edit');

			if ($visible) {
				$_type = $_fieldAttr['type'];
				$_fieldModel = $_fieldAttr['model'];
				$fieldName = $_fieldModel . '.' . $_field;
				$options = isset($_fieldAttr['attr']) ? $_fieldAttr['attr'] : array();
				
				$label = $this->getLabel($_fieldModel, $_field, $_fieldAttr);
				if (!empty($label)) {
					$options['label'] = $label;
				}

				$function = 'get' . Inflector::camelize($_type) . 'Element';
				if (method_exists($this, $function)) {
					$this->$function('edit', $data, $_fieldAttr, $options);
				}
				
				switch ($_type) {
					case 'file_upload';
						$attr = array('field' => $_field);
						echo $this->_View->element('layout/attachment_upload', $attr);
						break;

					case 'chosen_select':
						$options['options'] = isset($attr['options']) ? $attr['options'] : array();
						$options['class'] = 'chosen-select';
						$options['multiple'] = true;
						$options['data-placeholder'] = isset($attr['placeholder']) ? $attr['placeholder'] : '';
						$fieldName = $attr['id'];
						$this->includes['chosen']['include'] = true;
						break;

					default:
						break;
					
				}
				if (array_key_exists('dataModel', $_fieldAttr) && array_key_exists('dataField', $_fieldAttr)) {
					$dataModel = $_fieldAttr['dataModel'];
					$dataField = $_fieldAttr['dataField'];
					$options['value'] = $this->request->data->$dataField;
				} else if (isset($_fieldAttr['value'])) {
					$options['value'] = $_fieldAttr['value'];
				}
				if (array_key_exists('override', $_fieldAttr)) { 
					echo '<div class="row">' . $value . '</div>';
				} else if (!in_array($_type, array('image', 'date', 'time', 'binary', 'file_upload', 'element'))) {
					echo $this->Form->input($fieldName, $options);
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
	}

	public function getViewElements(Entity $data, $fields = [], $exclude = []) {
		//  1. implemented override param for nav_tabs to omit label
		//  2. for case 'element', implemented $elementData for $this->_View->element($element, $elementData)
		$_fields = $this->_View->get('_fields');

		$html = '';
		$row = $_labelCol = $_valueCol = '<div class="%s">%s</div>';
		$_rowClass = array('row');
		$_labelClass = array('col-xs-6 col-md-3 form-label'); // default bootstrap class for labels
		$_valueClass = array('col-xs-6 col-md-6'); // default bootstrap class for values

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

		foreach ($displayFields as $_field => $attr) {
			$_fieldAttr = array_merge($_attrDefaults, $attr);
			$_type = $_fieldAttr['type'];
			$visible = $this->isFieldVisible($_fieldAttr, 'view');

			if ($visible && $_type != 'hidden') {
				$_fieldModel = $_fieldAttr['model'];
				
				$label = $this->getLabel($_fieldModel, $_field, $_fieldAttr);
				
				$value = $data->$_field;
				if ($this->endsWith($_field, '_id')) {
					$associatedObject = str_replace('_id', '', $_field);
					if (is_object($data->$associatedObject)) {
						$value = $data->$associatedObject->name;
					}
					$label = $this->getLabel($_fieldModel, $associatedObject, $_fieldAttr);
				}

				// mapped to a current function in this class
				$function = 'get' . Inflector::camelize($_type) . 'Element';
				if (method_exists($this, $function)) {
					$value = $this->$function('view', $data, $_fieldAttr);
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
					$rowContent = sprintf($valueCol, $valueClass, $value);
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
		if ($action == 'view') {
			$value = $data->$attr['field'];
		} else if ($action == 'edit') {
			$options['type'] = 'string';
			if (array_key_exists('length', $attr)) {
				$options['maxlength'] = $attr['length'];
			}
		}
		return $value;
	}

	public function getSelectElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
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
					if (array_key_exists($value, $attr['options'])) {
						$value = $attr['options'][$value];
					}
				}
			}
		} else if ($action == 'edit') {
			if (isset($attr['options'])) {
				if (empty($attr['options'])) {
					$options['empty'] = isset($attr['empty']) ? $attr['empty'] : $this->getLabel('general.noData');
				} else {
					if (isset($attr['default'])) {
						$options['default'] = $attr['default'];
					} else {
						if (!empty($this->request->data)) {
							if(!empty($this->request->data->$_field)) {
								$options['default'] = $this->request->data->$_field;
							}
						}
					}
				}
				$options['options'] = $attr['options'];
			}

			// get rid of options that obsolete and not the default
			if (!empty($attr['options'])) {
				reset($attr['options']);
				$first_key = key($attr['options']);
				if (is_array($attr['options'][$first_key])) {
					foreach ($options['options'] as $okey => $ovalue) {
						if (isset($ovalue['obsolete']) && $ovalue['obsolete'] == '1') {
							if (!array_key_exists('default', $options) || $ovalue['value']!=$options['default']) {
								unset($options['options'][$okey]);
							}
						}
					}
				}
			}
		}
		return $value;
	}

	public function getTextElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$value = nl2br($data->$attr['field']);
		} else if ($action == 'edit') {
			$options['type'] = 'textarea';
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
		}
		return $value;
	}

	public function getDisabledElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$value = $attr['value'];
		} else if ($action == 'edit') {
			$options['type'] = 'text';
			$options['disabled'] = 'disabled';
			if (isset($_fieldAttr['options'])) {
				//$options['value'] = $_fieldAttr['options'][$this->request->data->$_field];
			}
			//echo $this->Form->hidden($fieldName);
		}
		return $value;
	}

	public function getImageElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$value = $this->Image->getBase64Image($data->{$_field . '_name'}, $data->$_field, $attr['attr']);
		} else if ($action == 'edit') {
			$imageAttr = $attr['attr'];
			$imageAttr['field'] = $_field;
			$imageAttr['label'] = $label;
			if (isset($data->{$_field . '_name'}) && isset($data->$_field)) {
				$imageAttr['src'] = $this->Image->getBase64($data->{$_field . '_name'}, $data->$_field);
			}
			echo $this->_View->element('layout/file_upload_preview', $imageAttr);
		}
		return $value;
	}

	public function getDownloadElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$value = $this->Html->link($value, $attr['attr']['url']);
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	public function getElementElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';

		// seems like view and edit actions have the same logic?
		if ($action == 'view') {
			$element = $attr['element'];
			$elementData = (array_key_exists('data', $attr)) ? $attr['data']: array();
			if (array_key_exists('class', $attr)) {
				$class = $attr['class'];
			};
			$value = $this->_View->element($element, $elementData);
		} else if ($action == 'edit') {
			$element = $attr['element'];
			$elementData = (array_key_exists('data', $attr)) ? $attr['data']: array();
			if (array_key_exists('class', $attr)) {
				$class = $attr['class'];
			};
			$value = $this->_View->element($element, $elementData);
		}
		return $value;
	}

	public function getDateElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			//$value = $this->Utility->formatDate($value, null, false);
		} else if ($action == 'edit') {
			$field = $attr['field'];
			$attr = array('id' => $attr['model'] . '_' . $field);
			$attr['data-date'] = $data->$field;
			if (array_key_exists('attr', $attr)) {
				$options = array_merge($attr, $attr['attr']);
			}
			//$includes['datepicker']['include'] = true;
			//echo $this->datepicker($fieldName, $options);
		}
		return $value;
	}

	public function getTimeElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			
		} else if ($action == 'edit') {
			$attr = array('id' => $_fieldModel . '_' . $_field);
			
			if (array_key_exists('attr', $_fieldAttr)) {
				$attr = array_merge($attr, $_fieldAttr['attr']);
			}

			$attr['value'] = $this->request->data->$_field;
			$attr['label'] = $label;
			$includes['timepicker']['include'] = true;
			echo $this->timepicker($fieldName, $attr);
		}
		return $value;
	}

	public function getChosenSelectElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$attr['dataModel'] = isset($attr['dataModel']) ? $attr['dataModel'] : Inflector::classify($_field);
			$attr['dataField'] = isset($attr['dataField']) ? $attr['dataField'] : 'id';
			//$value = $this->_View->element('ControllerAction/chosen_select', $_fieldAttr);
			$chosenSelectList = array();
			if (isset($data[$dataModel])) {
				foreach ($data[$dataModel] as $obj) {
					$chosenSelectData = isset($obj[$dataField]) ? $obj[$dataField] : '';
					if (!empty($chosenSelectData)) {
						$chosenSelectList[] = $chosenSelectData;
					}
				}
			}
			echo implode(', ', $chosenSelectList);
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	public function getBinaryElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			$table = TableRegistry::get($attr['className']);
			$fileUpload = $table->behaviors()->get('FileUpload');
			$name = '';
			if (!empty($fileUpload)) {
				$name = $fileUpload->config('name');
			}
			$primaryKey = $table->primaryKey();
			$action = ['action' => 'download'];
			if ($this->_View->get('_triggerFrom') == 'Model') {
				$action['action'] = $this->_View->get('_alias');
				$action[] = 'download';
			}
			$action[] = $data->$primaryKey;
			$value = $this->Html->link($data->$name, $action);
		} else if ($action == 'edit') {
			$this->includes['jasny']['include'] = true;
			echo $this->_View->element('ControllerAction.file_input', ['attr' => $attr]);
		}
		return $value;
	}

	// a template function for creating new elements
	public function getTestElement($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'view') {
			
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	// Elements definition ends here
}
