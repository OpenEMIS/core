<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Exception;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table; // POCOR-8683
use Cake\Utility\Inflector; // POCOR-8683

class UserCascadeBehavior extends Behavior {
	public function initialize(array $config): void {
		// $this->showSQL();
	}

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options): void
    {
        $userId = $entity->id;
        $this->cleanUserRecords($userId);

        $this->triggerSecurityUserWebhook($entity, 'security_user_delete');
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $eventKey = $entity->isNew()
            ? 'security_user_create'
            : 'security_user_update';

        $this->triggerSecurityUserWebhook($entity, $eventKey);


    }

    /**
     * Try to resolve the current user via options or session.
     */
    private function resolveCurrentUser(ArrayObject $options = null): ?array
    {
        if(!$options){
            $options = [];
        }
        if (!empty($options['user'])) {
            return $options['user'];
        }
        try {
            if (method_exists($this->_table, 'getRequest')) {
                $session = $this->_table->getRequest()->getSession();
                if ($session->check('Auth.User.id')) {
                    $userId = $session->read('Auth.User.id');
                }
            } else {
                if (property_exists($this->_table, 'Auth')) {
                    $userId = $this->_table->Auth->user('id');
                }
                if (property_exists($this->_table, 'Auth')) {
                    $session = $this->_table->Session;
                    if ($session->check('Auth.User.id')) {
                        $userId = $session->read('Auth.User.id');
                    }
                }
            }
            if($userId){
                $Users = TableRegistry::getTableLocator()->get('User.Users');
                $user = $Users->find()
                    ->where([$Users->aliasField('id') => $userId])
                    ->first();
            }
            return $user ? $user->toArray() : null;
        } catch (\Throwable $e) {
            Log::error('UserCascadeBehavior: unable to resolve user - ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Triggers a webhook for user-related changes.
     */
    private function triggerSecurityUserWebhook(Entity $entity, string $eventKey): void
    {
        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');

        if(empty($user)){
            $user = $Webhooks->resolveCurrentUser();
        }
//        Log::debug(print_r($entity, true));
        $body = $Webhooks->prepareWebhookBody('User.Users', $entity, []);
        Log::debug(print_r(['body' => $body], true));

        if ($eventKey === 'security_user_delete') {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $user['openemis_no'] ?? $user['username'] ?? 'system';
        }

        try {
            $Webhooks->triggerCommand($eventKey, $body);
        } catch (\Throwable $e) {
            Log::warning("Webhook trigger failed in afterSave: " . $e->getMessage());
        }
    }

    /**
     * Fetches and prepares the full entity for webhook body.
     */
    private function prepareWebhookBody(Entity $entity, array $contain = []): array
    {
        $Table = $this->_table;

        $record = $Table->find()
            ->where([$Table->aliasField('id') => $entity->id])
            ->contain($contain)
            ->first();

        if (!$record) {
            $record = $entity;
        }

        return $record->toArray();
    }

	// this function is to delete all records from user's related tables
	// (tables that contains security_user_id, student_id, staff_id, guardian_id)
	// excluding the one specified
	private function cleanUserRecords($userId) {
		$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();

		// will update this table to set value to 0 instead of deleting
		$excludes = ['institution_classes'];
		$fields = ['security_user_id', 'student_id', 'staff_id', 'guardian_id', 'trainee_id'];

		foreach ($tables as $key => $table) {
            $table = Inflector::underscore($table);
			if ($this->_table->startsWith($table, 'z_')) { // to exclude all z_ prefix tables
				continue;
			}
			if ($this->_table->startsWith($table, 'zz_')) { // POCOR-8683 to exclude all z_ prefix tables
				continue;
			}
			if ($this->_table->startsWith($table, 'inserted_records')) { // POCOR-8683 to exclude all z_ prefix tables
				continue;
			}
			try {
				if (!in_array($table, $excludes)) {
                    $tableObj = self::getDynamicTableInstance($table); // POCOR-8683
                    $table = Inflector::underscore($table);

					foreach ($fields as $field) {
                        $column = $tableObj->getSchema()->getColumn($field); // POCOR-8683
                        if ($column) {
                            $connection = $tableObj->getConnection();
                            $quotedTable = $connection->quoteIdentifier($table);
                            $quotedField = $connection->quoteIdentifier($field);
                            $sql = "DELETE FROM {$quotedTable} WHERE {$quotedField} = :userId";
                            $connection->execute($sql, ['userId' => $userId]);
                        }
					}
				}
			} catch (Exception $ex) {
				Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
			}
		}

		$table = TableRegistry::get('Institution.InstitutionClasses');
		$table->updateAll(
			['staff_id' => 0],
			['staff_id' => $userId]
		);
	}

	private function showSQL() {
		$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables(); // POCOR-8683

		// will update this table to set value to 0 instead of deleting
		$excludes = ['institution_classes'];
		$fields = ['security_user_id', 'student_id', 'staff_id', 'guardian_id', 'trainee_id'];
		pr('show sql');
		foreach ($tables as $key => $table) {
			try {
				$tableObj = self::getDynamicTableInstance($table); // POCOR-8683
				$columns = $tableObj->schema()->columns();

				if (!in_array($table, $excludes)) {
					foreach ($fields as $field) {
						if (in_array($field, $columns)) {
							echo 'DELETE FROM ' . $tableObj->getTable() . ' WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = ' . $table . '.' . $field . ');<br>'; // POCOR-8683
						}
					}
				}
			} catch (Exception $ex) {
				Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
			}
		}
	}

    /*
     * POCOR-8683
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        $locator = TableRegistry::getTableLocator();

        // First check for exact alias
        if ($locator->exists($tableName)) {
            return $locator->get($tableName);
        }

        // Parse plugin and table
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Try fallback to underscored DB table name
        $fallbackTable = Inflector::underscore($table);
        $fallbackAlias = Inflector::camelize($fallbackTable);

        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $fallbackAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $fallbackAlias . 'Table';
        }

        if (!class_exists($className)) {
            $className = Table::class; // fallback to base table class
        }

        if ($locator->exists($fallbackAlias)) {
            $existingConfig = $locator->getConfig($fallbackAlias);

            // Only override if the existing table config is incorrect
            if (
                empty($existingConfig['table']) ||
                $existingConfig['table'] !== $fallbackTable
            ) {
                // Remove and reset only if the config is wrong
                $locator->remove($fallbackAlias);

                $locator->setConfig($fallbackAlias, [
                    'className' => $className,
                    'table' => $fallbackTable,
                    'alias' => $fallbackAlias,
                ]);
            }
        } else {
            // Table not registered yet, safe to register
            $locator->setConfig($fallbackAlias, [
                'className' => $className,
                'table' => $fallbackTable,
                'alias' => $fallbackAlias,
            ]);
        }

        return $locator->get($fallbackAlias);
    }

}
