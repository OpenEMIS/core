<?php
namespace App\Model\Behavior;

use DateTime;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Network\Session;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;

/**
 * Depends on ControllerActionComponent's function "getAssociatedBelongsToModel()"
 */
class TrackActivityBehavior extends Behavior {
	protected $_defaultConfig = [
		'target' => '',
		'key' => '',
		'session' => ''
	];
	private $_dateFields = [];
	private $_exclude = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
	private $_excludeType = ['binary'];
	private $_session;

	public function initialize(array $config) {
		$this->_defaultConfig = array_merge($this->_defaultConfig, $config);
		$this->config($this->_defaultConfig);
		$this->_session = new Session;
		$this->_table->trackActivity = true;
	}

	public function beforeSave(Event $event, Entity $entity) {
		if (!empty($entity->id) && $entity->dirty() && $this->_table->trackActivity) { // edit operation
			$model = $this->_table;
			foreach ($model->fields as $key=>$field) {
				if (substr_count($field['type'], 'date')>0) {
					$this->_dateFields[] = $key;
				}
			}
		    $schema = $model->schema();
		    $session = $this->_session->read($this->config('session'));
		    $obj = [
		    	'model' => $model->alias(),
		    	'model_reference' => $entity->id,
		    	$this->config('key') => !empty($session) ? $session : $entity->id
		    ];

			foreach ($entity->extractOriginalChanged($entity->visibleProperties()) as $field=>$value) {
		    	if (!in_array($field, $this->_exclude) && $entity->has($field)) {
					if (!is_null($schema->column($field)) && !in_array($schema->columnType($field), $this->_excludeType)) {

		    			$oldValue = $entity->getOriginal($field);
						
						/**
		    			 * Added extra condition to convert old field data to db date format since the new data is in db date format else, 
		    			 * there will always be a new history record for date fields even though the date is the same.
		    			 */
		    			$proceed = true;
						if (in_array($field, $this->_dateFields)) {
							$oldValue = date('Y-m-d', strtotime($oldValue));
							if ($oldValue == $entity->$field) {
								$proceed = false;
							}
						}
		    			
		    			/**
		    			 * Added extra conditions; if oldData is 'World' and newData is an empty string, skip it as location 'World' is the same as an empty string on user views.
		    			 */
		    			if ($oldValue != 'World' && $entity->$field != '' && $proceed) {

							$relatedModel = $model->ControllerAction->getAssociatedBelongsToModel($field);
							
							// check if related model's table is actually field_option_values by reading its useTable instance
							if (is_object($relatedModel) && $relatedModel->hasBehavior('FieldOption')) {
								// foreignKey value has to be related model's name instead of field_option_value_id which does not exists in $model's column
								// Update all hasMany relation foreignKey so as to avoid undefined column name error when extracting data 
								foreach ($relatedModel->associations() as $assocAlias=>$assoc) {
									if ($assoc->type() == 'oneToMany') {
										$relatedModel->hasMany($assocAlias, ['foreignKey' => $field]);
									}
								}
							}
							$obj['field'] = $field;
								
							/**
			    			 * Added extra condition to convert both old field data and new field data to selected system date format before saving its value.
			    			 */
							if (in_array($field, $this->_dateFields)) {
								$ConfigItem = TableRegistry::get('ConfigItems');
								$format = $ConfigItem->value('date_format');

								$oldValue = new Time($oldValue);
								$oldValue = $oldValue->format($format);

								$newValue = new Time($entity->$field);
								$newValue = $newValue->format($format);
							} else {
								$newValue = $entity->$field;
							}

							$allData = ['old'=>$oldValue, 'new'=>$newValue];
							foreach ($allData as $allDataKey=>$allDataValue) {

								// if related model exists, get related data
								if (is_object($relatedModel)) {
									$relatedModelSchema = $relatedModel->schema();

									if ($relatedModelSchema->column('name')) {
										$obj[$allDataKey.'_value'] = $relatedModel->get($allDataValue)->name;
									} else {
										$obj[$allDataKey.'_value'] = ($allDataValue) ? $allDataValue : ' ';
									}

								} else {
									// check if field is supposed to be a foreignKey
									if (substr_count($field, '_id')>0) {
										// log if relation is missing
										$this->log($field." is not defined in belongsTo ".$model->alias()." @ TrackActivityBehaviour beforeSave()", 'debug');
									}
									$obj[$allDataKey.'_value'] = ($allDataValue) ? $allDataValue : ' ';
								}
							}

							$obj['operation'] = 'edit';
						    $ActivityModel = TableRegistry::get($this->config('target'));
							$data = $ActivityModel->newEntity();
							$data = $ActivityModel->patchEntity($data, $obj);
							$ActivityModel->save($data);

						}
					}
				}
			}
		}
		return true;
	}

}
