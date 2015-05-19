<?php
namespace ControllerAction\View\Helper;

use Cake\View\Helper;

class ControllerActionHelper extends Helper {
	public $helpers = ['Html', 'Form', 'Paginator', 'Label'];

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
		$options = array(
			'class' => 'form-horizontal',
			'novalidate' => true
		);
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
				$html = __('Display');
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

	public function getEditElements($fields = array(), $exclude = array()) {
		$formDefaults = $this->getFormDefaults();
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

		$_attrDefaults = array(
			'type' => 'string',
			'model' => $model,
			'label' => true
		);

		$includes = array(
			'datepicker' => array(
				'include' => false,
				'css' => 'ControllerAction.../plugins/datepicker/css/datepicker',
				'js' => 'ControllerAction.../plugins/datepicker/js/bootstrap-datepicker',
				'element' => 'ControllerAction.datepicker'
			),
			'timepicker' => array(
				'include' => false,
				'css' => 'ControllerAction.../plugins/timepicker/bootstrap-timepicker',
				'js' => 'ControllerAction.../plugins/timepicker/bootstrap-timepicker',
				'element' => 'ControllerAction.timepicker'
			),
			'chosen' => array(
				'include' => false,
				'css' => 'ControllerAction.../plugins/chosen/chosen.min',
				'js' => 'ControllerAction.../plugins/chosen/chosen.query.min'
			)
		);

		foreach ($displayFields as $_field => $attr) {
			$_fieldAttr = array_merge($_attrDefaults, $attr);
			$_type = $_fieldAttr['type'];
			$visible = $this->isFieldVisible($_fieldAttr, 'edit');
			//$template = $this->getFormTemplate();
			//$this->Form->templates($template);

			if ($visible) {
				$_fieldModel = array_key_exists('model', $_fieldAttr) ? $_fieldAttr['model'] : $model;
				$_fieldModel = $_fieldAttr['model'];
				$fieldName = $_fieldModel . '.' . $_field;
				$options = isset($_fieldAttr['attr']) ? $_fieldAttr['attr'] : array();
				
				$label = $this->getLabel($_fieldModel, $_field, $_fieldAttr);
				if (!empty($label)) {
					$options['label'] = $label;
				}
				
				switch ($_type) {
					case 'disabled': 
						$options['type'] = 'text';
						$options['disabled'] = 'disabled';
						if (isset($_fieldAttr['options'])) {
							$options['value'] = $_fieldAttr['options'][$this->request->data->$_field];
						}
						//echo $this->Form->hidden($fieldName);
						break;
						
					case 'select':
						if (isset($_fieldAttr['options'])) {
							if (empty($_fieldAttr['options'])) {
								$options['empty'] = isset($_fieldAttr['empty']) ? $_fieldAttr['empty'] : $this->Label->get('general.noData');
							} else {
								if (isset($_fieldAttr['default'])) {
									$options['default'] = $_fieldAttr['default'];
								} else {
									if (!empty($this->request->data)) {
										if(!empty($this->request->data->$_field)) {
											$options['default'] = $this->request->data->$_field;
										}
									}
								}
							}
							$options['options'] = $_fieldAttr['options'];
						}

						// get rid of options that obsolete and not the default
						if (!empty($_fieldAttr['options'])) {
							reset($_fieldAttr['options']);
							$first_key = key($_fieldAttr['options']);
							if (is_array($_fieldAttr['options'][$first_key])) {
								foreach ($options['options'] as $okey => $ovalue) {
									if (isset($ovalue['obsolete']) && $ovalue['obsolete'] == '1') {
										if (!array_key_exists('default', $options) || $ovalue['value']!=$options['default']) {
											unset($options['options'][$okey]);
										}
									}
								}
							}
						}
						break;

					case 'string':
						$options['type'] = 'string';
						if (array_key_exists('length', $_fieldAttr)) {
							$options['maxlength'] = $_fieldAttr['length'];
						}
						break;
						
					case 'text':
						$options['type'] = 'textarea';
						break;
					
					case 'hidden':
						//$template = $this->getFormTemplate();
						//unset($template['input']);
						//$this->Form->resetTemplates();
						//$this->Form->templates($template);
						$options['type'] = 'hidden';
						$options['label'] = false;
						break;
						
					case 'element':
							$element = $_fieldAttr['element'];
							$elementData = (array_key_exists('data', $_fieldAttr))? $_fieldAttr['data']: array();
							if (array_key_exists('class', $_fieldAttr)) {
								$class = $_fieldAttr['class'];
							};
							// pr($element);
							// $value = $this->element($element, $elementData);
							$value = $this->_View->element($element, $elementData);
							break;
						
					case 'image':
						$attr = $_fieldAttr['attr'];
						$attr['field'] = $_field;
						$attr['label'] = $label;
						if (isset($this->data->{$_field . '_name'}) && isset($this->data->$_field)) {
							$attr['src'] = $this->Image->getBase64($this->data->{$_field . '_name'}, $this->data->$_field);
						}
						echo $this->_View->element('layout/file_upload_preview', $attr);
						break;
						
					case 'date':
						$attr = array('id' => $_fieldModel . '_' . $_field);
						$attr['data-date'] = $this->request->data->$_field;
						if (array_key_exists('attr', $_fieldAttr)) {
							$attr = array_merge($attr, $_fieldAttr['attr']);
						}

						$attr['label'] = $label;
						$includes['datepicker']['include'] = true;
						echo $this->datepicker($fieldName, $attr);
						break;
						
					case 'time':
						$attr = array('id' => $_fieldModel . '_' . $_field);

						if (array_key_exists('attr', $_fieldAttr)) {
							$attr = array_merge($attr, $_fieldAttr['attr']);
						}

						$attr['value'] = $this->request->data->$_field;
						$attr['label'] = $label;
						$includes['timepicker']['include'] = true;
						echo $this->timepicker($fieldName, $attr);
						break;

					case 'file':
						echo $this->_View->element('layout/attachment');
						break;

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
						$includes['chosen']['include'] = true;
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
				} else if (!in_array($_type, array('image', 'date', 'time', 'file', 'file_upload', 'element'))) {
					echo $this->Form->input($fieldName, $options);
				}
			}
		}
		foreach ($includes as $include) {
			if ($include['include']) {
				if (array_key_exists('css', $include)) {
					echo $this->Html->css($include['css'], 'stylesheet', array('inline' => false));
				}
				if (array_key_exists('js', $include)) {
					echo $this->Html->script($include['js'], array('inline' => false));
				}
				if (array_key_exists('element', $include)) {
					echo $this->_View->element($include['element']);
				}
			}
		}
	}

	public function getViewElements() {
		//  1. implemented override param for nav_tabs to omit label
		//  2. for case 'element', implemented $elementData for $this->_View->element($element, $elementData)

		$formDefaults = $this->getFormDefaults();
		$_fields = $this->_View->get('_fields');
		$model = $this->_View->get('model');
		$data = $this->_View->get('data');

		$html = '';
		$row = '<div class="%s">%s</div>';
		$_rowClass = array('row');

		$_labelCol = '<div class="%s">%s</div>';
		$_labelClass = array('col-xs-6 col-md-3 form-label'); // default bootstrap class for labels

		$_valueCol = '<div class="%s">%s</div>';
		$_valueClass = array('col-xs-6 col-md-6'); // default bootstrap class for values

		$allowTypes = array('element', 'disabled', 'chosen_select');

		$displayFields = $_fields;

		if (isset($fields)) { // if we only want specific fields to be displayed
			foreach ($displayFields as $_field => $attr) {
				if (!in_array($displayFields, $fields)) {
					unset($displayFields[$_field]);
				}
			}
		}

		if (isset($exclude)) {
			foreach ($exclude as $f) {
				if (array_key_exists($f, $displayFields)) {
					unset($displayFields[$f]);
				}
			}
		}

		$_attrDefaults = array(
			'type' => 'string',
			'model' => $model,
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
						$elementData = (array_key_exists('data', $_fieldAttr))? $_fieldAttr['data']: array();
						if (array_key_exists('class', $_fieldAttr)) {
							$class = $_fieldAttr['class'];
						};
						$value = $this->_View->element($element, $elementData);
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
					
					case 'modified_user_id':
					case 'created_user_id':
						$dataModel = $_fieldAttr['dataModel'];
						//if (isset($data[$dataModel]['first_name']) && isset($data[$dataModel]['last_name'])) {
							//$value = $data[$dataModel]['first_name'] . ' ' . $data[$dataModel]['last_name'];
						//}
						break;
						
					default:
						break;
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
		echo $html;
	}
}
