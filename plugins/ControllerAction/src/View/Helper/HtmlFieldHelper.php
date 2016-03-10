<?php
namespace ControllerAction\View\Helper;

use ArrayObject;
use Cake\View\UrlHelper;
use Cake\Event\Event;
use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\I18n\Time;

use Cake\View\Helper\IdGeneratorTrait;

class HtmlFieldHelper extends Helper {
	use IdGeneratorTrait;

	public $table = null;

	public $helpers = ['Html', 'Form', 'Url'];

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

	public function renderElement($element, $attr) {
		return $this->_View->element($element, $attr);
	}

	public function viewSet($element, $attr) {
		if (!is_null($this->_View->get($element))) {
			$options = $this->_View->get($element);
			$options[] = $attr;
			$this->_View->set($element, $options);
		} else {
			$this->_View->set($element, [$attr]);
		}
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
	
	public function render($type, $action, Entity $data, array $attr, array $options = []) {
		$html = '';

		if (is_null($this->table)) {
			$this->table = TableRegistry::get($attr['className']);
		}

		// trigger event for custom field types
		$method = 'onGet' . Inflector::camelize($type) . 'Element';
		$eventKey = 'ControllerAction.Model.' . $method;
		$event = $this->dispatchEvent($this->table, $eventKey, $method, ['action' => $action, 'entity' => $data, 'attr' => $attr, 'options' => $options]);
		
		if (!empty($event->result)) {
			$html = $event->result;
		} else {
			if (method_exists($this, $type)) {
				$html = $this->$type($action, $data, $attr, $options);
			}
		}
		return $html;
	}

	public function includes($table=null, $action) {
		$includes = new ArrayObject($this->includes);

		if (!is_null($table)) {
			// trigger event to update inclusion of css/js files
			$eventKey = 'ControllerAction.Model.onUpdateIncludes';
			$event = $this->dispatchEvent($table, $eventKey, null, [$includes, $action]);
		}

		foreach ($includes as $include) {
			if ($include['include']) {
				if (array_key_exists('css', $include)) {
					if (is_array($include['css'])) {
						foreach ($include['css'] as $css) {
							echo $this->Html->css($css, ['block' => true]);
						}
					} else {
						echo $this->Html->css($include['css'], ['block' => true]);
					}
				}
				if (array_key_exists('js', $include)) {
					if (is_array($include['js'])) {
						foreach ($include['js'] as $js) {
							echo $this->Html->script($js, ['block' => true]);
						}
					} else {
						echo $this->Html->script($include['js'], ['block' => true]);
					}
				}
				if (array_key_exists('element', $include)) {
					echo $this->_View->element($include['element']);
				}
			}
		}
	}

	// Elements definition starts here

	public function string($action, Entity $data, $attr, $options=[]) {
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

	public function float($action, Entity $data, $attr, $options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $data->$attr['field'];
			//check whether value is float
			if(is_float($value))
				$value = sprintf('%0.2f', $value);
		} else if ($action == 'edit') {
			$options['type'] = 'number';
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function integer($action, Entity $data, $attr, $options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $data->$attr['field'];
		} else if ($action == 'edit') {
			$options['type'] = 'number';
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function password($action, Entity $data, $attr, $options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			if (!empty($data->$attr['field'])) {
				$value = '***************';
			}
		} else if ($action == 'edit') {
			$options['type'] = 'password';
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function select($action, Entity $data, $attr, $options=[]) {
		$value = '';
		$field = $attr['field'];
		if ($action == 'index' || $action == 'view') {
			if (!empty($attr['options'])) {
				if (empty($data->$field)) {
					return '';
				} else {
					if (array_key_exists($data->$field, $attr['options'])) {
						$value = $attr['options'][$data->$field];
						if (is_array($value)) {
							$value = __($value['text']);
						} else {
							$value = __($value);
						}
					}
				}
				
			}
			
			if (empty($value)) {
				$value = __($data->$field);
			}
		} else if ($action == 'edit') {
			if (array_key_exists('empty', $attr)) {
				if ($attr['empty'] === true) {
					$options['empty'] = '-- ' . __('Select') . ' --';
				} else {
					$options['empty'] = '-- ' . __($attr['empty']) . ' --';
				}
			}
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

	public function text($action, Entity $data, $attr, $options=[]) {
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

	public function hidden($action, Entity $data, $attr, $options=[]) {
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

	public function readonly($action, Entity $data, $attr, $options=[]) {
		$value = '';
		if ($action == 'view' || $action == 'index') {
			if (array_key_exists('value', $attr)) {
				$value = $attr['value'];
			} else {
				$value = $data->$attr['field'];
			}
		} else if ($action == 'edit') {
			$value = $this->disabled($action, $data, $attr, $options);
			unset($options['disabled']);
			unset($options['value']);
			$value .= $this->hidden($action, $data, $attr, $options);
		}
		return $value;
	}

	public function disabled($action, Entity $data, $attr, $options=[]) {
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

	public function image($action, Entity $data, $attr, $options=[]) {
		$value = '';
		$defaultWidth = 90;
		$defaultHeight = 115;

		$maxImageWidth = 60;

		if ($action == 'index' || $action == 'view') {
			$src = $data->photo_content;

			if (array_key_exists('ajaxLoad', $attr) && $attr['ajaxLoad']) {				
				$imageUrl = '';
				if (array_key_exists('imageUrl', $attr) && $attr['imageUrl']) {
					$imageUrl = $this->Url->build($attr['imageUrl'], true);
				}
				$imageDefault = (array_key_exists('imageDefault', $attr) && $attr['imageDefault'])? '<i class='.$attr['imageDefault'].'></i>': '';
				$value= '<div class="table-thumb" 
					data-load-image=true 
					data-image-width='.$maxImageWidth.' 
					data-image-url='.$imageUrl.'
					>
					<div class="profile-image-thumbnail">
					'.$imageDefault.'
					</div>
					</div>';
			} else {
				if (!empty($src)) {
					$value = (base64_decode($src, true)) ? '<div class="table-thumb"><img src="data:image/jpeg;base64,'.$src.'" style="max-width:'.$maxImageWidth.'px;" /></div>' : $src;
				}	
			}			
		} else if ($action == 'edit') {
			$defaultImgViewClass = $this->table->getDefaultImgViewClass();
			$defaultImgMsg = $this->table->getDefaultImgMsg();
			$defaultImgView = $this->table->getDefaultImgView();
			
			$showRemoveButton = false;
			if (isset($data[$attr['field']]['tmp_name'])) {
				$tmp_file = ((is_array($data[$attr['field']])) && (file_exists($data[$attr['field']]['tmp_name']))) ? $data[$attr['field']]['tmp_name'] : "";
				$tmp_file_read = (!empty($tmp_file)) ? file_get_contents($tmp_file) : ""; 
			} else {
				$tmp_file = true;
				$tmp_file_read = $data[$attr['field']];
			}

			if (!is_resource($tmp_file_read)) {
				$src = (!empty($tmp_file_read)) ? '<img id="existingImage" class="'.$defaultImgViewClass.'" src="data:image/jpeg;base64,'.base64_encode( $tmp_file_read ).'"/>' : $defaultImgView;
				$showRemoveButton = (!empty($tmp_file)) ? true : false; 
			} else {
				$tmp_file_read = stream_get_contents($tmp_file_read);
				$src = (!empty($tmp_file_read)) ? '<img id="existingImage" class="'.$defaultImgViewClass.'" src="data:image/jpeg;base64,'.base64_encode( $tmp_file_read ).'"/>' : $defaultImgView;
				$showRemoveButton = true;
			}

			header('Content-Type: image/jpeg'); 

			$this->includes['jasny']['include'] = true;
			$value = $this->_View->element('ControllerAction.bootstrap-jasny/image_uploader', ['attr' => $attr, 'src' => $src, 
																							'defaultWidth' => $defaultWidth,
																							'defaultHeight' => $defaultHeight,
																							'showRemoveButton' => $showRemoveButton,
																							'defaultImgMsg' => $defaultImgMsg,
																							'defaultImgView' => $defaultImgView]);

		} 

		return $value;
	}

	public function download($action, Entity $data, $attr, $options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $this->Html->link($data->{$attr['field']}, $attr['attr']['url']);
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	public function element($action, Entity $data, $attr, $options=[]) {
		$value = '';

		$element = $attr['element'];
		$attr['id'] = $attr['model'] . '_' . $attr['field'];
		$attr['label'] = array_key_exists('label', $options) ? $options['label'] : Inflector::humanize($attr['field']);
		
		if ($action == 'view' || $action == 'index') {
			$value = $this->_View->element($element, ['entity' => $data, 'attr' => $attr]);
		} else if ($action == 'edit') {
			$value = $this->_View->element($element, ['entity' => $data, 'attr' => $attr]);
		}
		return $value;
	}

	public function dateTime($action, Entity $data, $attr, $options=[]) {
		$value = '';
		$_options = [
			'format' => 'dd-mm-yyyy H:i:s',
			'todayBtn' => 'linked',
			'orientation' => 'auto'
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

	public function date($action, Entity $data, $attr, $options=[]) {
		$value = '';
		$_options = [
			'format' => 'dd-mm-yyyy',
			'todayBtn' => 'linked',
			'orientation' => 'auto',
			'autoclose' => true,
		];

		$field = $attr['field'];
		$table = TableRegistry::get($attr['className']);
		$schema = $table->schema();

		$columnAttr = $schema->column($field);
		$defaultDate = true;
		if ($columnAttr['null'] == true) {
			$defaultDate = false;
		}

		if (!isset($attr['date_options'])) {
			$attr['date_options'] = [];
		}
		
		if (!isset($attr['default_date'])) {
			$attr['default_date'] = $defaultDate;
		}
		
		$value = $data->$field;
		
		if ($action == 'index' || $action == 'view') {
			if (!is_null($value)) {
				$event = new Event('ControllerAction.Model.onFormatDate', $this, compact('value'));
				$event = $table->eventManager()->dispatch($event);
				if (strlen($event->result) > 0) {
					$value = $event->result;
				}
			}
		} else if ($action == 'edit') {
			$attr['id'] = $attr['model'] . '_' . $field; 
			if (array_key_exists('fieldName', $attr)) {
				$attr['id'] = $this->_domId($attr['fieldName']);
			}

			$attr['date_options'] = array_merge($_options, $attr['date_options']);
			if (!array_key_exists('value', $attr)) {
				if (!is_null($value)) {
					if ($value instanceof Time) {
						$attr['value'] = $value->format('d-m-Y');
					} else {
						$attr['value'] = date('d-m-Y', strtotime($value));
					}
				} else if ($attr['default_date']) {
					$attr['value'] = date('d-m-Y');
				}
			} else {
				if ($attr['value'] instanceof Time) {
					$attr['value'] = $attr['value']->format('d-m-Y');
				} else {
					$attr['value'] = date('d-m-Y', strtotime($attr['value']));
				}
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

	public function time($action, Entity $data, $attr, $options=[]) {
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

			if (!isset($attr['id'])) {
				$attr['id'] = $attr['model'] . '_' . $field;
			}
			
			if (array_key_exists('fieldName', $attr)) {
				$attr['id'] = $this->_domId($attr['fieldName']);
			}
			$model = split('\.', $attr['model']);
			$newModel = '';
			foreach($model as $part) {
				if (empty($newModel)) {
					$newModel = $part;
				} else {
					$newModel .= '['.$part.']';
				}
			}
			$attr['model'] = $newModel;
			$attr['time_options'] = array_merge($_options, $attr['time_options']);

			if (!array_key_exists('value', $attr)) {
				if (!is_null($value)) {
					$attr['value'] = date('h:i A', strtotime($value));
					$attr['time_options']['defaultTime'] = $attr['value'];
				} else if ($attr['default_time']) {
					$attr['time_options']['defaultTime'] = date('h:i A');
				}
			} else {
				if ($attr['value'] instanceof Time) {
					$attr['value'] = $attr['value']->format('h:i A');
					$attr['time_options']['defaultTime'] = $attr['value'];
				} else {
					$attr['value'] = date('h:i A', strtotime($attr['value']));
					$attr['time_options']['defaultTime'] = $attr['value'];
				}
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

	public function chosenSelect($action, Entity $data, $attr, $options=[]) {
		$value = '';

		$_options = [
			'class' => 'chosen-select',
			'multiple' => true
		];

		if ($action == 'index' || $action == 'view') {
			$value = $data->$attr['field'];
			$chosenSelectList = [];
			if (!empty($value)) {
				foreach ($value as $obj) {
					$chosenSelectList[] = $obj->name;
				}
				$value = implode(', ', $chosenSelectList);
			} else {
				$value = isset($attr['valueWhenEmpty']) ? $attr['valueWhenEmpty'] : '';
			}
		} else if ($action == 'edit') {
			$_options['options'] = isset($attr['options']) ? $attr['options'] : [];
			$_options['data-placeholder'] = isset($attr['placeholder']) ? $attr['placeholder'] : '';
			$options = array_merge($_options, $options);

			$this->includes['chosen']['include'] = true;

			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			} else {
				$fieldName = $attr['model'] . '.' . $attr['field'] . '._ids';
			}
			$value = $this->Form->input($fieldName, $options);
		}
		return $value;
	}

	public function binary($action, Entity $data, $attr, $options=[]) {
		$value = '';
		$table = TableRegistry::get($attr['className']);
		$fileUpload = $table->behaviors()->get('FileUpload');
		$name = '&nbsp;';
		if (!empty($fileUpload)) {
			$name = $fileUpload->config('name');
		}

		if ($action == 'index' || $action == 'view') {
			// $buttons = $this->_View->get('_buttons');
			$buttons = $this->_View->get('ControllerAction');
			$buttons = $buttons['buttons'];
			$action = $buttons['download']['url'];
			$value = $this->Html->link($data->$name, $action);
		} else if ($action == 'edit') {
			$this->includes['jasny']['include'] = true;
			if (isset($data->$name)) {
				$attr['value'] = $data->$name;
			}
			$value = $this->_View->element('ControllerAction.file_input', ['attr' => $attr]);
		}
		return $value;
	}

	public function color($action, Entity $data, $attr, $options=[]) {
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

	public function autocomplete($action, Entity $data, $attr, &$options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			$value = $data->$attr['field'];
		} else if ($action == 'edit') {
			$options['type'] = 'string';
			$options['class'] = "form-control autocomplete form-error ui-autocomplete-input";
			if (array_key_exists('length', $attr)) {
				$options['maxlength'] = $attr['length'];
			}
			if (array_key_exists('placeholder', $attr)) {
				$options['placeholder'] = $attr['placeholder'];
			}
			if (array_key_exists('url', $attr)) {
				$options['url'] = $this->Url->build($attr['url'], true);
			}
			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}

			$value = $this->_View->element('ControllerAction.autocomplete', ['attr' => $attr, 'options' => $options]);
		}
		return $value;
	}

	public function table($action, Entity $data, $attr, $options=[]) {
		$html = '
			<div class="input clearfix">
				<label>%s</label>
				<div class="table-wrapper">
					<div class="table-in-view">
						<table class="table">
							<thead>%s</thead>
							<tbody>%s</tbody>
						</table>
					</div>
				</div>
			</div>
		';

		$headers = $this->Html->tableHeaders($attr['headers']);
		$cells = $this->Html->tableCells($attr['cells']);

		$html = sprintf($html, $attr['label'], $headers, $cells);
		return $html;
	}

	// a template function for creating new elements
	public function test($action, Entity $data, $attr, $options=[]) {
		$value = '';
		if ($action == 'index' || $action == 'view') {
			
		} else if ($action == 'edit') {
			
		}
		return $value;
	}

	// Elements definition ends here
}
