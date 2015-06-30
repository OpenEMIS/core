<?php
namespace ControllerAction\View\Helper;

use Cake\View\UrlHelper;
use Cake\Event\Event;
use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class HtmlFieldHelper extends Helper {
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

	public function includes() {
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
				if (array_key_exists($data->$field, $attr['options'])) {
					$value = $attr['options'][$data->$field];
					if (is_array($value)) {
						$value = $value['text'];
					}
				}
			} else {
				$value = $data->$field;
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
		if ($action == 'view') {
			$value = $attr['value'];
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

		//Get image default width and height if specified in entity class
		//else default values
		//$defaultWidth = ($data::imageWidth > 0) ? ($data::imageWidth) : $defaultWidth;
		//$defaultHeight = ($data::imageHeight > 0) ? ($data::imageHeight) : $defaultHeight;
		
		if ($action == 'index' || $action == 'view') {
			$src = $data->photo_content;
			$style = 'width: ' . $defaultWidth . 'px; height: ' . $defaultHeight . 'px';
			if(!empty($src)){
				$value = (base64_decode($src, true)) ? '<img src="data:image/jpeg;base64,'.$src.'" style="'.$style.' class="profile-image" "/>' : $this->Html->image($src, ['plugin' => true]);
				//$value = (is_resource($src)) ? '<img src="data:image/jpeg;base64,'.base64_encode( stream_get_contents($src) ).'" style="'.$style.' class="profile-image" "/>' : $this->Html->image($src, ['plugin' => true]);
			}	
		} else if ($action == 'edit') {
			$defaultImageFromHolder = '<img data-src="holder.js/'.$defaultWidth.'x'.$defaultHeight.'" alt="...">';
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
			$value = $this->_View->element($element, ['attr' => $attr]);
		} else if ($action == 'edit') {
			$value = $this->_View->element($element, ['attr' => $attr]);
		}
		return $value;
	}

	public function dateTime($action, Entity $data, $attr, $options=[]) {
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

	public function date($action, Entity $data, $attr, $options=[]) {
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

	public function chosenSelect($action, Entity $data, $attr, $options=[]) {
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

	public function binary($action, Entity $data, $attr, $options=[]) {
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

	public function autocomplete($action, Entity $data, $attr, &$options=[]){
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
