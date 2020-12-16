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

class UserCascadeBehavior extends Behavior {
	public function initialize(array $config) {
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
		$tables = ConnectionManager::get('default')->schemaCollection()->listTables();

		// will update this table to set value to 0 instead of deleting
		$excludes = ['institution_classes'];
		$fields = ['security_user_id', 'student_id', 'staff_id', 'guardian_id', 'trainee_id'];

		foreach ($tables as $key => $table) {
			if ($this->_table->startsWith($table, 'z_')) { // to exclude all z_ prefix tables
				continue;
			}
			try {
				$tableObj = TableRegistry::get($table);
				$columns = $tableObj->schema()->columns();

				if (!in_array($table, $excludes)) {
					foreach ($fields as $field) {
						if (in_array($field, $columns)) {
							$tableObj->deleteAll([$field => $userId]);
						}
					}
				}
			} catch (Exception $ex) {
				Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
			}
		}

		$table = TableRegistry::get('institution_classes');
		$table->updateAll(
			['staff_id' => 0],
			['staff_id' => $userId]
		);
	}

	private function showSQL() {
		$tables = ConnectionManager::get('default')->schemaCollection()->listTables();

		// will update this table to set value to 0 instead of deleting
		$excludes = ['institution_classes'];
		$fields = ['security_user_id', 'student_id', 'staff_id', 'guardian_id', 'trainee_id'];
		pr('show sql');
		foreach ($tables as $key => $table) {
			try {
				$tableObj = TableRegistry::get($table);
				$columns = $tableObj->schema()->columns();

				if (!in_array($table, $excludes)) {
					foreach ($fields as $field) {
						if (in_array($field, $columns)) {
							echo 'DELETE FROM ' . $table . ' WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = ' . $table . '.' . $field . ');<br>';
						}
					}
				}
			} catch (Exception $ex) {
				Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
			}
		}
	}
}
