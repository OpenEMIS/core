<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\LogTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use ControllerAction\Model\Traits\UtilityTrait;
use ControllerAction\Model\Traits\ControllerActionTrait;

class AppTable extends Table {
	use ControllerActionTrait;
	use UtilityTrait;
	use LogTrait;

	public function initialize(array $config) {
		Time::$defaultLocale = 'en_US';
		
		$_config = [
			'Modified' => true,
			'Created' => true
		];
		$_config = array_merge($_config, $config);
		parent::initialize($config);

		$schema = $this->schema();
		$columns = $schema->columns();

		if (in_array('modified', $columns) || in_array('created', $columns)) {
			$this->addBehavior('Timestamp', [
				'events' => [
            		'Model.beforeSave' => [
                		'created' => 'new',
               			'modified' => 'existing'
           			]
        		]
			]);
		}

		if (in_array('modified_user_id', $columns) && $_config['Modified']) {
			$this->belongsTo('ModifiedUser', ['className' => 'User.Users', 'foreignKey' => 'modified_user_id']);
		}

		if (in_array('created_user_id', $columns) && $_config['Created']) {
			$this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);
		}

		if (in_array('visible', $columns)) {
			$this->addBehavior('Visible');
		}

		if (in_array('order', $columns)) {
			$this->addBehavior('Reorder');
			// to be removed after field_option_values is dropped
			if ($this->table() == 'field_option_values') {
				if ($this->behaviors()->has('Reorder')) {
					$this->behaviors()->get('Reorder')->config([
						'filter' => 'field_option_id',
					]);
				}
			}
		}

		$dateFields = [];
		$timeFields = [];
		foreach ($columns as $column) {
			if ($schema->columnType($column) == 'date') {
				$dateFields[] = $column;
			} else if ($schema->columnType($column) == 'time') {
				$timeFields[] = $column;
			}
		}
		if (!empty($dateFields)) {
			$this->addBehavior('ControllerAction.DatePicker', $dateFields);
		}
		if (!empty($timeFields)) {
			$this->addBehavior('ControllerAction.TimePicker', $timeFields);
		}
		$this->addBehavior('Validation');
		$this->attachWorkflow();
		$this->addBehavior('Modification');
	}

	public function attachWorkflow($config=[]) {
		// check for session and attach workflow behavior
		if (isset($_SESSION['Workflow']['Workflows']['models'])) {
			if (in_array($this->registryAlias(), $_SESSION['Workflow']['Workflows']['models'])) {
				$config = array_merge($config, ['model' => $this->registryAlias()]);
				$this->addBehavior('Workflow.Workflow', $config);
			}
		}
	}

	// Event: 'ControllerAction.Model.onPopulateSelectOptions'
	public function onPopulateSelectOptions(Event $event, Query $query) {
		return $this->getList($query);
	}

	public function getList($query = null) {
		$schema = $this->schema();
		$columns = $schema->columns();

		if (is_null($query)) {
			$query = $this->find('list');
		}

		if ($this->hasBehavior('FieldOption') && $this->table() == 'field_option_values') {
			$query->innerJoin(
				['FieldOption' => 'field_options'],
				[
					'FieldOption.id = ' . $this->aliasField('field_option_id'),
					'FieldOption.code' => $this->alias()
				]
			)->find('order')->find('visible');
		} else {
			if (in_array('order', $columns)) {
				$query->find('order');
			}

			if (in_array('visible', $columns)) {
				$query->find('visible');
			}
		}
		return $query;
	}

	// Event: 'Model.excel.onFormatDate' ExcelBehavior
	public function onExcelRenderDate(Event $event, Entity $entity, $attr) {
		if (!empty($entity->$attr['field'])) {
			if ($entity->$attr['field'] instanceof Time) {
				return $this->formatDate($entity->$attr['field']);
			} else {
				if ($entity->$attr['field'] != '0000-00-00') {
					$date = new Time($entity->$attr['field']);
					return $this->formatDate($date);
				} else {
					return '';
				}
			}
		} else {
			return $entity->$attr['field'];
		}
	}

	public function onExcelRenderDateTime(Event $event, Entity $entity, $attr) {
		if (!empty($entity->$attr['field'])) {
			if ($entity->$attr['field'] instanceof Time) {
				return $this->formatDate($entity->$attr['field']);
			} else {
				$date = new Time($entity->$attr['field']);
				return $this->formatDate($date);
			}
		} else {
			return $entity->$attr['field'];
		}
	}

	// Event: 'ControllerAction.Model.onFormatDate'
	public function onFormatDate(Event $event, Time $dateObject) {
		return $this->formatDate($dateObject);
	}

	/**
	 * For calling from view files
	 * @param  Time   $dateObject [description]
	 * @return [type]             [description]
	 */
	public function formatDate(Time $dateObject) {
		$ConfigItem = TableRegistry::get('ConfigItems');
		$format = $ConfigItem->value('date_format');
        $value = '';
        if (is_object($dateObject)) {
            $value = $dateObject->format($format);
        }
		return $value;
	}

	// Event: 'ControllerAction.Model.onFormatTime'
	public function onFormatTime(Event $event, Time $dateObject) {
		return $this->formatTime($dateObject);
	}

	/**
	 * For calling from view files
	 * @param  Time   $dateObject [description]
	 * @return [type]             [description]
	 */
	public function formatTime(Time $dateObject) {
		$ConfigItem = TableRegistry::get('ConfigItems');
		$format = $ConfigItem->value('time_format');
		$value = '';
        if (is_object($dateObject)) {
            $value = $dateObject->format($format);
        }
		return $value;
	}

	// Event: 'ControllerAction.Model.onFormatDateTime'
	public function onFormatDateTime(Event $event, Time $dateObject) {
		return $this->formatDateTime($dateObject);
	}

	/**
	 * For calling from view files
	 * @param  Time   $dateObject [description]
	 * @return [type]             [description]
	 */
	public function formatDateTime(Time $dateObject) {
		$ConfigItem = TableRegistry::get('ConfigItems');
		$format = $ConfigItem->value('date_format') . ' - ' . $ConfigItem->value('time_format');
		$value = '';
        if (is_object($dateObject)) {
            $value = $dateObject->format($format);
        }
		return $value;
	}

	// Event: 'ControllerAction.Model.onGetFieldLabel'
	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		return $this->getFieldLabel($module, $field, $language, $autoHumanize);
	}

	public function getFieldLabel($module, $field, $language, $autoHumanize=true) {
		$Labels = TableRegistry::get('Labels');
		$label = $Labels->getLabel($module, $field, $language);
		if ($label === false && $autoHumanize) {
			$label = Inflector::humanize($field);
			if ($this->endsWith($field, '_id') && $this->endsWith($label, ' Id')) {
				$label = str_replace(' Id', '', $label);
			}
		}
		if (substr($label, -1) == ')') {
			$label = $label.' ';
		}
		return $label;
	}

	// Event: 'Model.excel.onExcelGetLabel'
	public function onExcelGetLabel(Event $event, $module, $col, $language) {
		return __($this->getFieldLabel($module, $col, $language));
	}

	// Event: 'ControllerAction.Model.onInitializeButtons'
	public function onInitializeButtons(Event $event, ArrayObject $buttons, $action, $isFromModel) {
		// needs clean up
		$controller = $event->subject()->_registry->getController();
		$access = $controller->AccessControl;

		$toolbarButtons = new ArrayObject([]);
		$indexButtons = new ArrayObject([]);

		$toolbarAttr = [
			'class' => 'btn btn-xs btn-default',
			'data-toggle' => 'tooltip',
			'data-placement' => 'bottom',
			'escape' => false
		];
		$indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

		// Set for roles belonging to the controller
		$roles = [];
		$event = $controller->dispatchEvent('Model.Buttons.onUpdateRoles', null, $this);
    	if ($event->result) {
    		$roles = $event->result;	
    	}

		if ($action != 'index') {
			$toolbarButtons['back'] = $buttons['back'];
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $toolbarAttr;
			$toolbarButtons['back']['attr']['title'] = __('Back');

			if ($action == 'remove' && $buttons['remove']['strategy'] == 'transfer') {
				$toolbarButtons['list'] = $buttons['index'];
				$toolbarButtons['list']['type'] = 'button';
				$toolbarButtons['list']['label'] = '<i class="fa kd-lists"></i>';
				$toolbarButtons['list']['attr'] = $toolbarAttr;
				$toolbarButtons['list']['attr']['title'] = __('List');
			}
		}
		if ($action == 'index') {
			if ($buttons->offsetExists('add') && $access->check($buttons['add']['url'], $roles)) {
				$toolbarButtons['add'] = $buttons['add'];
				$toolbarButtons['add']['type'] = 'button';
				$toolbarButtons['add']['label'] = '<i class="fa kd-add"></i>';
				$toolbarButtons['add']['attr'] = $toolbarAttr;
				$toolbarButtons['add']['attr']['title'] = __('Add');
			}
			if ($buttons->offsetExists('search')) {
				$toolbarButtons['search'] = [
					'type' => 'element', 
					'element' => 'OpenEmis.search',
					'data' => ['url' => $buttons['index']['url']],
					'options' => []
				];
			}
		} else if ($action == 'add' || $action == 'edit') {
			if ($action == 'edit' && $buttons->offsetExists('index')) {
				$toolbarButtons['list'] = $buttons['index'];
				$toolbarButtons['list']['type'] = 'button';
				$toolbarButtons['list']['label'] = '<i class="fa kd-lists"></i>';
				$toolbarButtons['list']['attr'] = $toolbarAttr;
				$toolbarButtons['list']['attr']['title'] = __('List');
			}
		} else if ($action == 'view') {
			// edit button
			if ($buttons->offsetExists('edit') && $access->check($buttons['edit']['url'], $roles)) {
				$toolbarButtons['edit'] = $buttons['edit'];
				$toolbarButtons['edit']['type'] = 'button';
				$toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
				$toolbarButtons['edit']['attr'] = $toolbarAttr;
				$toolbarButtons['edit']['attr']['title'] = __('Edit');
			}

			// delete button
			// disabled for now until better solution
			// if ($buttons->offsetExists('remove')) {
			// 	$toolbarButtons['remove'] = $buttons['remove'];
			// 	$toolbarButtons['remove']['type'] = 'button';
			// 	$toolbarButtons['remove']['label'] = '<i class="fa fa-trash"></i>';
			// 	$toolbarButtons['remove']['attr'] = $toolbarAttr;
			// 	$toolbarButtons['remove']['attr']['title'] = __('Delete');

			// 	if (array_key_exists('removeStraightAway', $buttons['remove']) && $buttons['remove']['removeStraightAway']) {
			// 		$toolbarButtons['remove']['attr']['data-toggle'] = 'modal';
			// 		$toolbarButtons['remove']['attr']['data-target'] = '#delete-modal';
			// 		$toolbarButtons['remove']['attr']['field-target'] = '#recordId';
			// 		// $toolbarButtons['remove']['attr']['field-value'] = $id;
			// 		$toolbarButtons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
			// 	}
			// }
		}

		if ($buttons->offsetExists('view') && $access->check($buttons['view']['url'], $roles)) {
			$indexButtons['view'] = $buttons['view'];
			$indexButtons['view']['label'] = '<i class="fa fa-eye"></i>' . __('View');
			$indexButtons['view']['attr'] = $indexAttr;
		}

		if ($buttons->offsetExists('edit') && $access->check($buttons['edit']['url'], $roles)) {
			$indexButtons['edit'] = $buttons['edit'];
			$indexButtons['edit']['label'] = '<i class="fa fa-pencil"></i>' . __('Edit');
			$indexButtons['edit']['attr'] = $indexAttr;
		}

		if ($buttons->offsetExists('remove') && $access->check($buttons['remove']['url'], $roles)) {
			$indexButtons['remove'] = $buttons['remove'];
			$indexButtons['remove']['label'] = '<i class="fa fa-trash"></i>' . __('Delete');
			$indexButtons['remove']['attr'] = $indexAttr;
		}

		if ($buttons->offsetExists('reorder') && $buttons->offsetExists('edit') && $access->check($buttons['edit']['url'], $roles)) {
		// if ($buttons->offsetExists('reorder') && $access->check($buttons['edit']['url'])) {
			$controller->set('reorder', true);
		}

		$event = new Event('Model.custom.onUpdateToolbarButtons', $this, [$buttons, $toolbarButtons, $toolbarAttr, $action, $isFromModel]);
		$this->eventManager()->dispatch($event);

		if ($toolbarButtons->offsetExists('back')) {
			$controller->set('backButton', $toolbarButtons['back']);
		}
		$controller->set(compact('toolbarButtons', 'indexButtons'));
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$primaryKey = $this->primaryKey();
		$id = $entity->$primaryKey;

		if (array_key_exists('view', $buttons)) {
			$buttons['view']['url'][1] = $id;
		}
		if (array_key_exists('edit', $buttons)) {
			$buttons['edit']['url'][1] = $id;
		}
		if (array_key_exists('remove', $buttons)) {
			if ($buttons['remove']['strategy'] == 'cascade') {
				$buttons['remove']['attr']['data-toggle'] = 'modal';
				$buttons['remove']['attr']['data-target'] = '#delete-modal';
				$buttons['remove']['attr']['field-target'] = '#recordId';
				$buttons['remove']['attr']['field-value'] = $id;
				$buttons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
			} else {
				$buttons['remove']['url'][1] = $id;
			}
		}
		return $buttons;
	}

	public function findVisible(Query $query, array $options) {
		return $query->where([$this->aliasField('visible') => 1]);
	}

	public function findActive(Query $query, array $options) {
		return $query->where([$this->aliasField('active') => 1]);
	}

	public function findOrder(Query $query, array $options) {
		return $query->order([$this->aliasField('order') => 'ASC']);
	}

	public function checkIdInOptions($key, $options) {
		pr('checkIdInOptions is deprecated, please use queryString instead');
		if (!empty($options)) {
			if ($key != 0) {
				if (!array_key_exists($key, $options)) {
					$key = key($options);
				}
			} else {
				$key = key($options);
			}
		}
		return $key;
	}

	public function postString($key) {
		$request = $this->request;
		$selectedId = null;
		if ($request->data($this->aliasField($key))) {
			$selectedId = $request->data($this->aliasField($key));
		}
		return $selectedId;
	}

	public function isForeignKey($field, $table = null) {
		if (is_null($table)) {
			$table = $this;
		}
		foreach ($table->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
	}

	public function getAssociatedTable($field, $table = null) {
		if (is_null($table)) {
			$table = $this;
		}
		$relatedModel = null;

		foreach ($table->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					$relatedModel = $assoc;
					break;
				}
			}
		}
		return $relatedModel;
	}

	public function getAssociatedKey($field, $table = null) {
		if (is_null($table)) {
			$table = $this;
		}
		$tableObj = $this->getAssociatedTable($field, $table);
		$key = null;
		if (is_object($tableObj)) {
			$key = Inflector::underscore(Inflector::singularize($tableObj->alias()));
		}
		return $key;
	}
}
