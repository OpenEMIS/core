<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Http\Session;


/**
 * Depends on ControllerActionComponent's function "getAssociatedBelongsToModel()"
 *
 * --------------------------------------------------------------------------
 * POCOR-9697 — Logging policy mirror (CakePHP / Laravel parity)
 *
 * Both sides of OpenEMIS — this CakePHP behavior AND the Laravel
 * `App\Models\Concerns\UserActivityLog` trait (api/app/Models/Concerns/
 * UserActivityLog.php) — write into the same `user_activities` table
 * using the same row shape:
 *
 *   edit    → one row per dirty field, real old/new values
 *             ('[REDACTED]' for password / super_admin)
 *   delete  → single summary row, empty strings for field/field_type/old/new
 *   create  → API side only (port of the missing Cake behavior here); same
 *             empty-string summary shape
 *
 * The `user_activities` rows ARE the audit trail. A separate framework-level
 * log entry (`Log::write` here, `Log::warning` on Laravel) is reserved for
 * SUSPICIOUS or DANGEROUS events only — never normal CRUD:
 *
 *   • Attempts to set / probe `password` or `super_admin`
 *   • `created_user_id` / `modified_user_id` forgery in a payload
 *   • ACL denial against SecurityUsers endpoints
 *   • Audit-row INSERT itself failing (defensive fallback)
 *
 * If you add new audit behavior here, keep the row shape and the
 * "log suspicious only" policy aligned with the Laravel trait.
 *
 * Note: `user_activities.security_user_id` has a FK to `security_users.id`
 * with `ON DELETE RESTRICT`. Hard-deletes of `security_users` are
 * therefore blocked at the schema level — any single create/edit row
 * about a user blocks their delete. The production table has zero
 * `delete` rows for this reason (OpenEMIS soft-deletes via `status`).
 * The per-field snapshot code in `afterDelete` ships ready for any
 * non-RESTRICTed table or a future FK relaxation but does not
 * by itself make `security_users` hard-deletable.
 * --------------------------------------------------------------------------
 */
class TrackActivityBehavior extends Behavior {
	protected $session;
	protected $_defaultConfig = [
		'target' => '',
		'key' => '',
		'session' => '',
		'keyField' => 'id'
	];
	private $_exclude = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
	private $_excludeType = ['binary'];
	//POCOR-9697: parity with Laravel App\Models\Concerns\UserActivityLog.
	//Fields here emit an audit row but the values are forced to '[REDACTED]'.
	//Takes precedence over $_excludeType so photo_content (longblob, normally
	//skipped as 'binary') still surfaces as a row noting the photo changed.
	private $_redact = ['password', 'super_admin', 'photo_content'];
	private $_session;

	public function initialize(array $config): void {
		$this->_defaultConfig = array_merge($this->_defaultConfig, $config);
		$this->getConfig((string) $this->_defaultConfig);
		//$this->_session = new Session;
		$this->_session = new Session();
		$this->_table->trackActivity = true;
	}


	
/******************************************************************************************************************
**
** CakePhp events
**
******************************************************************************************************************/
public function beforeSave(EventInterface $event, Entity $entity) {
	if (!empty($entity->id) && $entity->isDirty() && $this->_table->trackActivity && isset($this->_table->fields)) { // edit operation
		$model = $this->_table;
		$schema = $model->getSchema();
		$session = $this->_session->read($this->getConfig('session'));
		$ActivityModel = TableRegistry::getTableLocator()->get($this->getConfig('target'));
		$obj = [
			'model' => $model->getAlias(),
			'model_reference' => $entity->id,
			$this->getConfig('key') => !empty($session) ? $session : $entity->{$this->getConfig('keyField')}
		];

		foreach ($entity->extractOriginalChanged($entity->getVisible()) as $field=>$value) {
			if (!in_array($field, $this->_exclude) && $entity->has($field)) {
				//POCOR-9697: redact sensitive / binary fields — emit a row to
				//show the field changed, but never persist the value. Mirrors
				//the Laravel UserActivityLog trait (password / super_admin /
				//photo_content). Takes precedence over $_excludeType so the
				//photo_content (longblob, normally skipped) still produces a
				//visible audit row.
				if (in_array($field, $this->_redact)) {
					$redactObj = $obj;
					$redactObj['field']      = $field;
					$redactObj['field_type'] = 'string';
					$redactObj['old_value']  = '[REDACTED]';
					$redactObj['new_value']  = '[REDACTED]';
					$redactObj['operation']  = 'edit';
					$redactEntity = $ActivityModel->newEmptyEntity();
					$redactEntity = $ActivityModel->patchEntity($redactEntity, $redactObj);
					if (!$ActivityModel->save($redactEntity)) {
						Log::write('debug', $redactEntity->getErrors());
					}
					continue;
				}
				if (!is_null($schema->getColumn($field)) && !in_array($schema->getColumnType($field), $this->_excludeType)) {

					$oldValue = $entity->getOriginal($field);
					
					/**
					 * Added extra condition to convert old field data to db date format since the new data is in db date format else, 
					 * there will always be a new history record for date fields even though the date is the same.
					 */
					$fieldType = $model->fields[$field]['type'] ? $model->fields[$field]['type'] : "" ;
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
								$relatedModelSchema = $relatedModel->getSchema();

								if ($relatedModelSchema->getColumn('name')) {
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
							$data = $ActivityModel->newEmptyEntity();
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

	public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options) {
		if (!empty($entity->id) && $this->_table->trackActivity) {
			$model = $this->_table;
			$alias = $model->getAlias();
			$schema = $model->getSchema();
			$id = $entity->id;
			$keyField = $entity->{$this->getConfig('keyField')};
			$ActivityModel = TableRegistry::getTableLocator()->get($this->getConfig('target'));

			$base = [
				'model' => $alias,
				'model_reference' => $id,
				$this->getConfig('key') => $keyField,
				'operation' => 'delete',
			];

			// 1) Summary row — preserves existing dashboard behavior (one
			//    "row was deleted" entry per delete event).
			$summary = $base + [
				'field' => '',
				'field_type' => '',
				'old_value' => '',
				'new_value' => '',
			];
			$summaryEntity = $ActivityModel->newEntity($summary);
			if (!$ActivityModel->save($summaryEntity)) {
				Log::write('debug', $summaryEntity->getErrors());
			}

			//POCOR-9697: 2) Per-field snapshot of the deleted entity so
			//forensic / undelete code can reconstruct who the user was.
			//Mirrors Laravel UserActivityLog::logDeleteSnapshot.
			//  - password / super_admin → '[REDACTED]'
			//  - photo_content (longblob)   → '[...]'
			//  - other binary columns       → skipped via $_excludeType
			//  - excluded audit columns     → skipped via $_exclude
			foreach ($schema->columns() as $field) {
				if (in_array($field, $this->_exclude)) {
					continue;
				}
				if (!$entity->has($field)) {
					continue;
				}

				$isRedacted = in_array($field, $this->_redact);
				if (!$isRedacted && in_array($schema->getColumnType($field), $this->_excludeType)) {
					// Non-redacted binary column — skip entirely.
					continue;
				}

				$oldRaw = $entity->{$field};
				if ($isRedacted) {
					$oldValue = ($field === 'photo_content') ? '[...]' : '[REDACTED]';
				} else {
					$oldValue = (string) $oldRaw;
					if (strlen($oldValue) > 255) {
						$oldValue = substr($oldValue, 0, 252) . '...';
					}
				}

				$fieldType = isset($model->fields[$field]['type'])
					? $model->fields[$field]['type']
					: ($schema->getColumnType($field) ?: 'string');

				$row = $base + [
					'field' => $field,
					'field_type' => $fieldType,
					'old_value' => $oldValue,
					'new_value' => '',
				];
				$rowEntity = $ActivityModel->newEntity($row);
				if (!$ActivityModel->save($rowEntity)) {
					Log::write('debug', $rowEntity->getErrors());
				}
			}
		}
	}
}
