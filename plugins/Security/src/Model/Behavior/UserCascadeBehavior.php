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

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$userId = $entity->id;
		$this->cleanUserRecords($userId);

        $body = [];
        $body = [
        	'security_user_id' => $userId
        ];

		$Webhooks = TableRegistry::get('Webhook.Webhooks');
		$Webhooks->triggerShell('security_user_delete', ['username' => ''], $body);
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
					foreach ($fields as $field) {
                        $column = $tableObj->getSchema()->getColumn($field); // POCOR-8683
                        if ($column) {
							$tableObj->deleteAll([$field => $userId]);
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
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }
}
