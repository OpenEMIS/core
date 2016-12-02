<?php
namespace ControllerAction\View\Helper;

use ArrayObject;
use Cake\Event\Event;
use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\I18n\I18n;
use Cake\ORM\Table;
use Cake\Utility\Security;

class ControllerActionHelper extends Helper {
	public $helpers = ['Html', 'ControllerAction.HtmlField', 'Form', 'Paginator', 'Label', 'Url'];

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

	public function onEvent($subject, $eventKey, $method) {
		$eventMap = $subject->implementedEvents();
		if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
			if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
				$subject->eventManager()->on($eventKey, [], [$subject, $method]);
			}
		}
	}

	public function dispatchEvent($subject, $eventKey, $method=null, $params=[]) {
		$this->onEvent($subject, $eventKey, $method);
		$event = new Event($eventKey, $this, $params);
		return $subject->eventManager()->dispatch($event);
	}

	public function getFormTemplate()
	{
		return [
			'select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>',
			'radio'  => '<input type="radio" class = "iradio_minimal-grey icheck-input" name="{{name}}" value="{{value}}"{{attrs}}>'
		];
	}

	public function getFormOptions() {
		$options = [
			'id' => 'content-main-form',
			'class' => 'form-horizontal',
			'novalidate' => true
		];

		$config = $this->_View->get('ControllerAction');
		$fields = $config['fields'];
		if (!empty($fields)) {
			$types = ['binary','image', 'custom_file'];
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
		$buttons = new ArrayObject([]);

		// save button
		$buttons[] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Save'),
			'attr' => ['class' => 'btn btn-default btn-save', 'div' => false, 'name' => 'submit', 'value' => 'save']
		];

		// cancel button
		$backBtn = $this->_View->get('backButton');
		$buttons[] = [
			'name' => '<i class="fa fa-close"></i> ' . __('Cancel'),
			'attr' => ['class' => 'btn btn-outline btn-cancel', 'escape' => false],
			'url' => !is_null($backBtn) ? $backBtn['url'] : []
		];

		$config = $this->_View->get('ControllerAction');
		$table = $config['table'];

		// attach event for updating form buttons
		$eventKey = 'ControllerAction.Model.onGetFormButtons';
		$event = $this->dispatchEvent($table, $eventKey, null, [$buttons]);
		// end attach event

		$html = '';
		if ($buttons->count() > 0) {
			$html = '<div class="form-buttons"><div class="button-label"></div>';
			foreach ($buttons as $btn) {
				if (!array_key_exists('url', $btn)) {
					$html .= $this->Form->button($btn['name'], $btn['attr']);
				} else {
					$html .= $this->Html->link($btn['name'], $btn['url'], $btn['attr']);
				}
			}
			$html .= $this->Form->button('reload', ['id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden']);
			$html .= '</div>';
		}
		return $html;
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

	public function locale($locale = null) {
		if (!empty($locale)) {
			return I18n::locale($locale);
		} else {
			return I18n::locale();
		}
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
						$label = __($event->result);
					}

					if ($attr['sort']) {
						$sortField = $field;
						$sortTitle = ($label!='') ? $label : __($field);
						if (is_array($attr['sort'])) {
							if (array_key_exists('field', $attr['sort'])) {
								$sortField = $attr['sort']['field'];
							}
							if (array_key_exists('title', $attr['sort'])) {
								$sortTitle = $attr['sort']['title'];
							}
						}
						$label = $this->Paginator->sort($sortField, $sortTitle);
					}

					$method = 'onGet' . Inflector::camelize($field);
					$eventKey = 'ControllerAction.Model.' . $method;
					$this->onEvent($table, $eventKey, $method);

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

    public function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function paramsDecode($params)
    {
        $paramArr = explode('.', $params);
        if (count($paramArr) != 2) {
            throw new SecurityException('Wrong number of segments');
        }
        list($payload, $signature) = $paramArr;
        $payload = $this->urlsafeB64Decode($payload);
        $signature = $this->urlsafeB64Decode($signature);

        $payload = json_decode($payload, true);
        $sessionId = Security::hash('session_id', 'sha256');
        if (!isset($payload[$sessionId])) {
            throw new SecurityException('No session id in payload');
        } else {
            $checkPayload = $payload;
            $checkPayload[$sessionId] = session_id();
            $checkSignature = Security::hash(json_encode($checkPayload), 'sha256', true);
            if ($signature !== $checkSignature) {
                throw new SecurityException('Query String has been tampered');
            }
        }
        unset($payload[$sessionId]);
        return $payload;
    }

    public function paramsEncode($params = [])
    {
        $sessionId = Security::hash('session_id', 'sha256');
        $params[$sessionId] = session_id();
        $jsonParam = json_encode($params);
        $base64Param = $this->urlsafeB64Encode($jsonParam);
        $signature = Security::hash($jsonParam, 'sha256', true);
        $base64Signature = $this->urlsafeB64Encode($signature);
        return "$base64Param.$base64Signature";
    }

	public function getTableRow(Entity $entity, array $fields, $searchableFields = []) {
		$row = [];

		$search = '';
		if (isset($this->request->data['Search']) && array_key_exists('searchField', $this->request->data['Search'])) {
			$search = $this->request->data['Search']['searchField'];
		}

		$table = null;
		// For XSS
		$this->escapeHtmlSpecialCharacters($entity);
		$count = 0;
		foreach ($fields as $field => $attr) {
			$model = $attr['model'];
			$value = $entity->$field;
			$type = $attr['type'];

			if (is_null($table)) {
				$table = TableRegistry::get($attr['className']);
			}

			// attach event for index columns
			// EventManager->on is triggered at getTableHeader()
			$method = 'onGet' . Inflector::camelize($field);
			$eventKey = 'ControllerAction.Model.' . $method;

			$event = new Event($eventKey, $this, [$entity]);
			$event = $table->eventManager()->dispatch($event);

			// end attach event
			$associatedFound = false;
			if (strlen($event->result) > 0) {
				$allowedTranslation = ['string','text'];//array that will be translate
				if (in_array($attr['type'], $allowedTranslation)) {
					$value = __($event->result);
				} else {
					$value = $event->result;
				}
				$entity->$field = $value;
			} else if ($this->endsWith($field, '_id')) {
				$associatedObject = '';
				if (isset($table->CAVersion) && $table->CAVersion=='4.0') {
					$associatedObject = $table->getAssociatedEntity($field);
				} else {
					$associatedObject = $table->ControllerAction->getAssociatedEntityArrayKey($field);
				}

                if ($entity->has($associatedObject) && $entity->$associatedObject instanceof Entity && $entity->$associatedObject->has('name')) {
                    $value = __($entity->$associatedObject->name);
                    $associatedFound = true;
                }
			}



			if (!$associatedFound) {
				$value = $this->HtmlField->render($type, 'index', $entity, $attr);
			}

			if (!empty($search)) {
				if (in_array($field, $searchableFields)) {
					$value = $this->highlight($search, $value);
				}
			}

			if (isset($attr['tableColumnClass'])) {
				$row[] = [$value, ['class' => $attr['tableColumnClass']]];
			} else {
				$row[] = $value;
			}
		}
		$primaryKeys = $table->primaryKey();
		$primaryKeyValue = [];
		if (is_array($primaryKeys)) {
			foreach ($primaryKeys as $key) {
				$primaryKeyValue[$key] = $entity->$key;
			}
		} else {
			$primaryKeyValue[$primaryKeys] = $entity->$primaryKeys;
		}

		$encodedKeys = $this->paramsEncode($primaryKeyValue);
		$row[0] = [$row[0], ['data-row-id' => $encodedKeys]];

		return $row;
	}

	public function getLabel($model, $field, $attr=array()) {
		return $this->Label->getLabel($model, $field, $attr);
	}

	public function getPaginatorButtons($type='prev') {
		$icon = array('prev' => '', 'next' => '');
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
		$config = $this->_View->get('ControllerAction');

		if (!is_null($config['pageOptions'])) {
			$pageOptions = $config['pageOptions'];

			if (!empty($pageOptions)) {
				$html .= $this->Form->input('Search.limit', [
					'label' => false,
					'options' => $pageOptions,
					'onchange' => "$(this).closest('form').submit()",
					'templates' => $this->getFormTemplate()
				]);
			}
		}
		return $html;
	}

	public function getEditElements(Entity $data, $fields = [], $exclude = []) {
		$config = $this->_View->get('ControllerAction');
		$_fields = $config['fields'];

		$html = '';
		$model = $config['table']->alias();
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
					if (!array_key_exists('label', $options)) {
						$_fieldAttr['label'] = $label;
						$options['label'] = __($label);
					} else {
						$_fieldAttr['label'] = $options['label'];
					}
					$_fieldAttr['label'] = __($_fieldAttr['label']);
				}

				if (array_key_exists('autocomplete', $options) && $options['autocomplete'] == 'off') {
					$html .= '<input style="display:none" type="text" name="'.$model.'['.$_field.']"/>';
				}
				$html .= $this->HtmlField->render($_type, 'edit', $data, $_fieldAttr, $options);
			}
		}
		$this->HtmlField->includes($table, 'edit');
		return $html;
	}

	private function escapeHtmlSpecialCharacters(Entity $entity)
	{
		$model = TableRegistry::get($entity->source());
		// For XSS
		$schema = $model->schema();
		$columns = $schema->columns();
		foreach ($columns as $key => $col) {
			$fieldCol = $schema->column($col);
			if ($fieldCol['type'] == 'string' || $fieldCol['type'] == 'text') {
				if ($entity->has($col)) {
					$htmlInfo = $this->HtmlField->escapeHtmlEntity($entity->$col);
					$entity->$col = $htmlInfo;
				}
			}
		}
	}

	public function getViewElements(Entity $data, $fields = [], $exclude = []) {
		//  1. implemented override param for nav_tabs to omit label
		//  2. for case 'element', implemented $elementData for $this->_View->element($element, $elementData)
		$config = $this->_View->get('ControllerAction');
		$_fields = $config['fields'];

		$html = '';
		$row = $_labelCol = $_valueCol = '<div class="%s">%s</div>';

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
		// For XSS
		$this->escapeHtmlSpecialCharacters($data);

		foreach ($displayFields as $_field => $attr) {
			$_rowClass = array('row');
			$_labelClass = array('col-xs-6 col-md-3 form-label'); // default bootstrap class for labels
			$_valueClass = array('form-input'); // default bootstrap class for values

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
					$associatedObject = '';
					if (isset($table->CAVersion) && $table->CAVersion=='4.0') {
						$associatedObject = $table->getAssociatedEntity($_field);
					} else {
						$table = TableRegistry::get($attr['className']);
						$associatedObject = $table->ControllerAction->getAssociatedEntityArrayKey($_field);
					}

					if (!empty($associatedObject) && $data->has($associatedObject)) {
						$value = __($data->$associatedObject->name);
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
					$rowContent = sprintf($_labelCol.$_valueCol, $labelClass, __($label), $valueClass, $value);
				} else { // no label
					$rowContent = sprintf($_valueCol, $valueClass, $value);
				}
				if (!array_key_exists('override', $_fieldAttr)) {
					$html .= sprintf($row, $rowClass, $rowContent);
				} else {
					$html .= sprintf($row, $rowClass, $value);
				}
			}
		}
		$this->HtmlField->includes($table, 'view');
		return $html;
	}
}
