<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9590 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();

        //POCOR-9590: Add sync_status to track whether a user has been synced with external identity source
        $this->table('security_users')
            ->addColumn('sync_status', 'integer', [
                'default' => 0,
                'null' => false,
                'limit' => 1,
                'after' => 'external_reference',
                'comment' => 'POCOR-9590: 0=not synced, 1=synced with external identity source',
            ])
            ->update();

        //POCOR-9590: Insert sync_mode and missing field mappings for Seychelles Civil Status
        $now = date('Y-m-d H:i:s');
        $rows = [
            //POCOR-9590: sync_mode=hide means Synced indicator is hidden (Seychelles behaviour)
            [
                'id' => $this->newUuid(),
                'external_data_source_type' => 'Seychelles Civil Status',
                'attribute_field' => 'sync_mode',
                'attribute_name' => 'Sync Mode',
                'value' => 'hide',
                'created' => $now,
                'created_user_id' => 1,
            ],
            [
                'id' => $this->newUuid(),
                'external_data_source_type' => 'Seychelles Civil Status',
                'attribute_field' => 'first_name_mapping',
                'attribute_name' => 'First Name Mapping',
                'value' => 'givennames',
                'created' => $now,
                'created_user_id' => 1,
            ],
            [
                'id' => $this->newUuid(),
                'external_data_source_type' => 'Seychelles Civil Status',
                'attribute_field' => 'last_name_mapping',
                'attribute_name' => 'Last Name Mapping',
                'value' => 'presentsurname',
                'created' => $now,
                'created_user_id' => 1,
            ],
            [
                'id' => $this->newUuid(),
                'external_data_source_type' => 'Seychelles Civil Status',
                'attribute_field' => 'token_uri',
                'attribute_name' => 'Token URI',
                'value' => 'http://flask-seychelles:5000/identityserverbeta/connect/token',
                'created' => $now,
                'created_user_id' => 1,
            ],
            [
                'id' => $this->newUuid(),
                'external_data_source_type' => 'Seychelles Civil Status',
                'attribute_field' => 'user_endpoint_uri',
                'attribute_name' => 'User Endpoint URI',
                'value' => 'http://flask-seychelles:5000/NPDService/api/v1/NIN/NINExt/{identity_number}',
                'created' => $now,
                'created_user_id' => 1,
            ],
            //POCOR-9590: sync_mode=hide means Synced indicator is hidden (Jamaica/OpenEMIS Identity behaviour)
            [
                'id' => $this->newUuid(),
                'external_data_source_type' => 'OpenEMIS Identity',
                'attribute_field' => 'sync_mode',
                'attribute_name' => 'Sync Mode',
                'value' => 'hide',
                'created' => $now,
                'created_user_id' => 1,
            ],
        ];

        $this->table('external_data_source_attributes')->insert($rows)->saveData();
    }

    public function down()
    {
        $this->restoreTables();
    }

    private function backupTables()
    {
        $tables = ['security_users', 'external_data_source_attributes'];
        foreach ($tables as $t) {
            $backup = 'z_9590_' . $t;
            if (!$this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `{$backup}` LIKE `{$t}`");
                $this->execute("INSERT INTO `{$backup}` SELECT * FROM `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTables()
    {
        $tables = ['security_users', 'external_data_source_attributes'];
        foreach ($tables as $t) {
            $backup = 'z_9590_' . $t;
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$t}`");
                $this->execute("RENAME TABLE `{$backup}` TO `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function newUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
