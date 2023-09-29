<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;


/**
 * Depends on ControllerActionComponent's function "getAssociatedBelongsToModel()"
 */
class TrackActivityBehavior extends Behavior {
	protected $_defaultConfig = [
		'target' => '',
		'key' => '',
		'session' => '',
		'keyField' => 'id'
	];
	private $_exclude = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
	private $_excludeType = ['binary'];
	private $_session;

	public function initialize(array $config) {
		$this->_defaultConfig = array_merge($this->_defaultConfig, $config);
		$this->config($this->_defaultConfig);
		$this->_session = new Session;
		$this->_table->trackActivity = true;
	}


	
/******************************************************************************************************************
**
** CakePhp events
**
******************************************************************************************************************/
	public function beforeSave(Event $event, Entity $entity) {
		if (!empty($entity->id) && $entity->dirty() && $this->_table->trackActivity && isset($this->_table->fields)) { // edit operation
			$model = $this->_table;
		    $schema = $model->schema();
		    $session = $this->_session->read($this->config('session'));
		    $ActivityModel = TableRegistry::get($this->config('target'));
		    $obj = [
		    	'model' => $model->alias(),
		    	'model_reference' => $entity->id,
		    	$this->config('key') => !empty($session) ? $session : $entity->{$this->config('keyField')}
		    ];

			foreach ($entity->extractOriginalChanged($entity->visibleProperties()) as $field=>$value) {
		    	if (!in_array($field, $this->_exclude) && $entity->has($field)) {
					if (!is_null($schema->column($field)) && !in_array($schema->columnType($field), $this->_excludeType)) {

		    			$oldValue = $entity->getOriginal($field);
						
						/**
		    			 * Added extra condition to convert old field data to db date format since the new data is in db date format else, 
		    			 * there will always be a new history record for date fields even though the date is the same.
		    			 */
		    			$fieldType = $model->fields[$field]['type'];
						if (array_key_exists($fieldType, $ActivityModel->dateTypes)) {
							$dateType = $ActivityModel->dateTypes[$fieldType];
							$oldValue = date($dateType, strtotime($oldValue));
							$newValue = date($dateType, strtotime($entity->{$field}));
							$dateType = null;
						} else {
							$newValue = $entity->{$field};
						}
		    			
		    			/**
		    			 * Added extra conditions; if oldData is 'World' and newData is an empty string, skip it as location 'World' is the same as an empty string on user views.
		    			 */
		    			if ($oldValue != 'World' && $oldValue != $newValue) {

			    			/**
			    			 * PHPOE-2081 - changed getAssociatedBelongsToModel function to getAssociatedTable function and duplicate it in App\Model\Table\AppTable
			    			 */
							$relatedModel = $model->getAssociatedTable($field, $model);

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
							 * saves the field type so that if a field is date, time or datetime type, we will convert it to show the system's selected date config using formatToSystemConfig()
							 */
							$obj['field_type'] = $fieldType;
								
							$allData = ['old'=>$oldValue, 'new'=>$newValue];
							$track = true;
							foreach ($allData as $allDataKey=>$allDataValue) {

								// if related model exists, get related data
								if (is_object($relatedModel)) {
									$relatedModelSchema = $relatedModel->schema();

									if ($relatedModelSchema->column('name')) {
										try {
											$obj[$allDataKey.'_value'] = $relatedModel->get($allDataValue)->name;
										} catch (RecordNotFoundException $ex) {
											$track = false;
											Log::write('debug', $ex->getMessage());
											Log::write('debug', $allDataKey);
											break;
										} catch (InvalidPrimaryKeyException $ex) {
											$obj[$allDataKey.'_value'] = null;
										}
									} else {
										$obj[$allDataKey.'_value'] = ($allDataValue) ? $allDataValue : ' ';
									}

								} else {
									// check if field is supposed to be a foreignKey
									if (substr_count($field, '_id')>0) {
										// log if relation is missing
										Log::write('debug', $field." is not defined in belongsTo ".$model->alias()." @ TrackActivityBehaviour beforeSave()");
									}
									$obj[$allDataKey.'_value'] = ($allDataValue) ? $allDataValue : ' ';
								}
							}

							if ($track) {
								$obj['operation'] = 'edit';
								$data = $ActivityModel->newEntity();
								$data = $ActivityModel->patchEntity($data, $obj);
								$ActivityModel->save($data);
							}
						}
					}
				}
			}
		}
		return true;
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		if (!empty($entity->id) && $this->_table->trackActivity) {
			$alias = $this->_table->alias();
			$id = $entity->id;
			$keyField = $entity->{$this->config('keyField')};
			$activity['model'] = $alias;
			$activity['model_reference'] = $id;
			$activity['field'] = '';
			$activity['field_type'] = '';
			$activity['old_value'] = '';
			$activity['new_value'] = '';
			$activity[$this->config('key')] = $keyField;
			$activity['operation'] = 'delete';

			$ActivityModel = TableRegistry::get($this->config('target'));
			$newEntity = $ActivityModel->newEntity($activity);
			if (!$ActivityModel->save($newEntity)) {
				Log::write('debug', $newEntity->errors());
			}
		}
	}
}
