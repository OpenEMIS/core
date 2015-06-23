<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use ControllerAction\Model\Traits\ControllerActionTrait;
use ControllerAction\Model\Traits\UtilityTrait;

class AppTable extends Table {
	use ControllerActionTrait;
	use UtilityTrait;

	public function initialize(array $config) {
		$_config = [
			'Modified' => true,
			'Created' => true
		];
		$_config = array_merge($_config, $config);
		parent::initialize($config);

		$schema = $this->schema();
		$columns = $schema->columns();

		if (in_array('modified', $columns) || in_array('created', $columns)) {
			$this->addBehavior('Timestamp');
		}

		if (in_array('modified_user_id', $columns) && $_config['Modified']) {
			$this->belongsTo('ModifiedUser', [
				'className' => 'User.Users',
				'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
				'foreignKey' => 'modified_user_id'
			]);
		}

		if (in_array('created_user_id', $columns) && $_config['Created']) {
			$this->belongsTo('CreatedUser', [
				'className' => 'User.Users',
				'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
				'foreignKey' => 'created_user_id'
			]);
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
	}

	// Event: 'ControllerAction.Model.onPopulateSelectOptions'
	public function onPopulateSelectOptions(Event $event, $query) {
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
		return $dateObject->format($format);
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
		return $dateObject->format($format);
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
		return $dateObject->format($format);
	}

	// Event: 'ControllerAction.Model.onGetLabel'
	public function onGetLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		$Labels = TableRegistry::get('Labels');
		$label = $Labels->getLabel($module, $field, $language);

		if ($label === false && $autoHumanize) {
			$label = Inflector::humanize($field);
			if ($this->endsWith($field, '_id') && $this->endsWith($label, ' Id')) {
				$label = str_replace(' Id', '', $label);
			}
		}
		return $label;
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
	
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		$schema = $this->schema();
		$columns = $schema->columns();

		if (in_array('modified_user_id', $columns)) {
			$entity->modified_user_id = 1;
		}
		if (in_array('created_user_id', $columns)) {
			$entity->created_user_id = 1;
		}
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
}
