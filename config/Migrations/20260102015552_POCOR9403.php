<?php

declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Log\Log;

class POCOR9403 extends AbstractMigration
{
    const OPEN_EMIS_EXAMS = 'OpenEMIS Exams';
    const OPEN_EMIS_CORE = 'OpenEMIS Core';
    const EXTERNAL_DATA_SOURCE_WEBHOOK = 'External Data Source - Webhook';
    const EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM = 'external_data_source_webhooks_custom';
    const EXTERNAL_DATA_SOURCE_WEBHOOKS_EXAMS = 'external_data_source_webhooks_exams';
    const EXTERNAL_DATA_SOURCE_WEBHOOKS_CORE = 'external_data_source_webhooks_core';
    const WEBHOOK_URL_CUSTOM = '';
    const WEBHOOK_URL_EXAMS = '';
    const WEBHOOK_URL_CORE = '';
    const WEBHOOK_STATUS = 0;

    public function up()
    {
//        return;
        $this->backupTables();
        $this->insertNewExternalDataSourceAttributes();
        $this->insertNewConfigItems();
        $this->addNewExternalDataSource();
        $this->addNewWebhookFields();
        $this->insertWebhookDefinitions();
    }

    public function down()
    {
        $this->restoreTable();
    }

    private function backupTables()
    {
        $tables = [
            'config_items',
            'external_data_source_attributes',
            'webhooks',
            'webhook_events'
        ];

        foreach ($tables as $table) {
            $backup = 'z_9403_' . $table;
            if (!$this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `$backup` LIKE `$table`");
                $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTable()
    {
        $tables = [
            'config_items',
            'external_data_source_attributes',
            'webhooks',
            'webhook_events'
        ];

        foreach ($tables as $table) {
            $backup = 'z_9403_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `$table`");
                $this->execute("RENAME TABLE `$backup` TO `$table`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function generateConfigData($name, $code, $type, $label)
    {
        return [
            'id' => null,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'label' => $label,
            'value' => '1',
            'value_selection' => '0',
            'default_value' => '0',
            'editable' => '1',
            'visible' => '1',
            'field_type' => 'Dropdown',
            'option_type' => 'completeness',
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
    }

    private function insertNewConfigItems()
    {
        $table = $this->table('config_items');
        $data = [
            $this->generateConfigData(
                self::OPEN_EMIS_EXAMS,
                self::EXTERNAL_DATA_SOURCE_WEBHOOKS_EXAMS,
                self::EXTERNAL_DATA_SOURCE_WEBHOOK,
                self::OPEN_EMIS_EXAMS
            ),
            $this->generateConfigData(
                self::OPEN_EMIS_CORE,
                self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CORE,
                self::EXTERNAL_DATA_SOURCE_WEBHOOK,
                self::OPEN_EMIS_CORE
            ),
            $this->generateConfigData(
                'Custom',
                self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM,
                self::EXTERNAL_DATA_SOURCE_WEBHOOK,
                'Custom'
            )
        ];
        $table->insert($data)->save();
    }

    private function insertNewExternalDataSourceAttributes()
    {
        $table = $this->table('external_data_source_attributes');
        $password = 'password';

        $attributes = [
            ['api_url', 'api_url', self::WEBHOOK_URL_EXAMS],
            ['username', 'username', 'user'],
            ['password', 'password', $password],
            ['api_key', 'api_key', 'api_key']
        ];

        $data = array_map(
            fn($attr) => $this->generateExternalDataSourceAttribute(self::OPEN_EMIS_EXAMS, ...$attr),
            $attributes
        );

        $table->insert($data)->save();

        $attributes = [
            ['api_url', 'api_url', self::WEBHOOK_URL_CORE],
            ['username', 'username', 'user'],
            ['password', 'password', $password],
            ['api_key', 'api_key', 'api_key']
        ];

        foreach ($attributes as $attr) {
            [$field, $name, $value] = $attr;

            $exists = $this->fetchRow(
                "SELECT id FROM external_data_source_attributes
         WHERE attribute_field = '" . $field . "'
           AND external_data_source_type = '" . self::OPEN_EMIS_CORE . "'
         LIMIT 1"
            );

            if (!$exists) {
                $data = $this->generateExternalDataSourceAttribute(
                    self::OPEN_EMIS_CORE,
                    $field,
                    $name,
                    $value
                );

                $table->insert([$data])->save();
                Log::info("Inserted external attribute: {$field} for " . self::OPEN_EMIS_CORE);
            } else {
                Log::debug("Skipped existing external attribute: {$field} for " . self::OPEN_EMIS_CORE);
            }
        }

    }

    private function addNewExternalDataSource()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('ALTER TABLE `webhooks` ADD COLUMN external_data_source_id INT(11) NOT NULL AFTER `status`');
        $configItemEntity = $this->fetchRow("SELECT `id` FROM `config_items` WHERE `code` = '" . self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM . "'");
        $configItemId = $configItemEntity['id'];
        $this->execute("UPDATE `webhooks` SET `external_data_source_id` = {$configItemId}");
        $this->execute('ALTER TABLE `webhooks` ADD FOREIGN KEY (`external_data_source_id`) REFERENCES `config_items` (`id`)');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function addNewWebhookFields()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('ALTER TABLE `webhooks` ADD `event_key` VARCHAR(45) DEFAULT NULL AFTER `method`');
        $this->execute('ALTER TABLE `webhooks` ADD `query_template` VARCHAR(255) DEFAULT NULL AFTER `event_key`');
        $this->execute('ALTER TABLE `webhooks` ADD `body_template` TEXT DEFAULT NULL AFTER `query_template`');

        // Cleanup and structure
        $locator = TableRegistry::getTableLocator();
        $WebhookEvents = $locator->get('Webhook.WebhookEvents');
        $Webhooks = $locator->get('Webhook.Webhooks');

        // Duplicate old webhooks per event_key
        $allLinks = $WebhookEvents->find()->all();
        $i = 1;
        foreach ($allLinks as $link) {
            $webhook = $Webhooks->get($link->webhook_id);
            $new = $Webhooks->newEntity($webhook->toArray());
            $new->id = null;
            $new->event_key = $link->event_key;
            $new->name = $new->name . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $i++;
            $Webhooks->saveOrFail($new);
        }

        $this->execute('TRUNCATE TABLE `webhook_events`');
        $Webhooks->deleteAll(['event_key IS' => null]);

        $this->execute('ALTER TABLE `webhooks` MODIFY `event_key` VARCHAR(45) NOT NULL');
        $this->execute('ALTER TABLE webhooks ADD CONSTRAINT webhooks_name UNIQUE (name);');
        $this->execute('CREATE INDEX `webhooks_idx_event_key` ON `webhooks` (`event_key`)');
        $this->execute('CREATE INDEX `webhooks_idx_event_status` ON `webhooks` (`event_key`, `status`)');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function insertWebhookDefinitions()
    {
        $Webhooks = TableRegistry::getTableLocator()->get('Webhook.Webhooks');

        $customConfig = $this->fetchRow("SELECT id FROM config_items WHERE code = '" . self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM . "'");
        $examsConfig = $this->fetchRow("SELECT id FROM config_items WHERE code = '" . self::EXTERNAL_DATA_SOURCE_WEBHOOKS_EXAMS . "'");
        $coreConfig = $this->fetchRow("SELECT id FROM config_items WHERE code = '" . self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CORE . "'");

        $customDataSourceId = $customConfig['id'];
        $examDataSourceId = $examsConfig['id'];
        $coreDataSourceId = $coreConfig['id'];

        $webhooks = [
            ['event_key' => 'logout', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/logout', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institutions', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institutions', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institutions/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'academic_period_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/academic-periods', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'academic_period_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/academic-periods/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'academic_period_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/academic-periods', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'area_education_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/areas', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'area_education_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/areas/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'area_education_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/areas', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_grade_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-grades', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-grades/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-grades', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_cycle_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-cycles', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_cycle_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-cycles/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_cycle_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-cycles', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_grade_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-grade-subjects', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-grade-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-grade-subjects', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_level_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-levels', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_level_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-levels/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_level_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-levels', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_programme_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-programmes', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_programme_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-programmes/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_programme_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-programmes', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_system_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-systems', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_system_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-systems/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_system_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-systems', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-subjects', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/education-subjects', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_role_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-roles/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_user_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-users', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_user_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-users/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_user_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-users', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_role_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-roles/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'staff_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-staff', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'staff_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-staff/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'staff_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-staff', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'student_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-student', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-student/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-student', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'class_student_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-class-student', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'class_student_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-class-student/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'class_student_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-class-student', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],


            ['event_key' => 'institution_class_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-classes', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_class_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-classes/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_class_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-classes', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_grade_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-grades', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_grade_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-grades/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_grade_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-grades', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-subjects', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/institution-subjects', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'student_guardian_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/student-guardians', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_guardian_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/student-guardians/${id}', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_guardian_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_EXAMS, 'query_template' => '/student-guardians', 'body_template' => '', 'external_data_source_id' => $examDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institutions', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institutions', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institutions/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'academic_period_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/academic-periods', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'academic_period_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/academic-periods/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'academic_period_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/academic-periods', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'area_education_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/areas', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'area_education_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/areas/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'area_education_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/areas', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_grade_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-grades', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-grades/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-grades', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_cycle_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-cycles', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_cycle_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-cycles/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_cycle_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-cycles', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_grade_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-grade-subjects', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-grade-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-grade-subjects', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_level_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-levels', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_level_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-levels/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_level_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-levels', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_programme_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-programmes', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_programme_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-programmes/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_programme_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-programmes', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_system_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-systems', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_system_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-systems/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_system_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-systems', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-subjects', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/education-subjects', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_role_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-roles/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_user_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-users', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_user_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-users/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_user_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-users', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_role_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-roles/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'staff_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-staff', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'staff_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-staff/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'staff_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-staff', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'student_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-student', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-student/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-student', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'class_student_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-class-student', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'class_student_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-class-student/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'class_student_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-class-student', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],


            ['event_key' => 'institution_class_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-classes', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_class_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-classes/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_class_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-classes', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_grade_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-grades', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_grade_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-grades/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_grade_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-grades', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-subjects', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/institution-subjects', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'student_guardian_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/student-guardians', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_guardian_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/student-guardians/${id}', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_guardian_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CORE, 'query_template' => '/student-guardians', 'body_template' => '', 'external_data_source_id' => $coreDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institutions', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institutions', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institutions/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'academic_period_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/academic-periods', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'academic_period_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/academic-periods/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'academic_period_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/academic-periods', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'area_education_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/areas', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'area_education_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/areas/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'area_education_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/areas', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_grade_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-grades', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-grades/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-grades', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_cycle_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-cycles', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_cycle_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-cycles/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_cycle_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-cycles', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_grade_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-grade-subjects', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-grade-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_grade_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-grade-subjects', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_level_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-levels', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_level_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-levels/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_level_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-levels', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_programme_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-programmes', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_programme_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-programmes/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_programme_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-programmes', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_system_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-systems', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_system_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-systems/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_system_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-systems', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'education_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-subjects', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'education_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/education-subjects', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_role_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-roles/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_user_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-users', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_user_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-users/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_user_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-users', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'security_role_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-roles/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'security_role_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/security-roles', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'staff_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-staff', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'staff_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-staff/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'staff_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-staff', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'student_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-student', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-student/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-student', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'class_student_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-class-student', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'class_student_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-class-student/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'class_student_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-class-student', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],


            ['event_key' => 'institution_class_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-classes', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_class_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-classes/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_class_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-classes', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_grade_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-grades', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_grade_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-grades/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_grade_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-grades', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'institution_subject_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-subjects', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_subject_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-subjects/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'institution_subject_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/institution-subjects', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

            ['event_key' => 'student_guardian_create', 'method' => 'POST', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/student-guardians', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_guardian_update', 'method' => 'PUT', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/student-guardians/${id}', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],
            ['event_key' => 'student_guardian_delete', 'method' => 'DELETE', 'url' => self::WEBHOOK_URL_CUSTOM, 'query_template' => '/student-guardians', 'body_template' => '', 'external_data_source_id' => $customDataSourceId, 'status' => self::WEBHOOK_STATUS],

        ];

        $validEventKeys = [];

        foreach ($webhooks as $webhook) {
            $eventKey = $webhook['event_key'];
            $validEventKeys[] = $eventKey;
            $name = Inflector::humanize(Inflector::underscore($eventKey));
            $external_data_source_id = $webhook['external_data_source_id'];
            // Prefix based on URL type
            switch ($webhook['external_data_source_id']) {
                case $customDataSourceId:
                    $name = 'Custom ' . $name;
                    break;
                case $examDataSourceId:
                    $name = 'Exams ' . $name;
                    break;
                case $coreDataSourceId:
                    $name = 'Core ' . $name;
                    break;
            }

            $count = $Webhooks->find()
                ->where([
                    'event_key' => $eventKey,
                    'external_data_source_id' => $external_data_source_id
                ])
                ->count(); // ✅ simpler and faster than ->count()

            if ($count == 0) {
                // Base name from event key

                $webhook['name'] = $name;

                $new = $Webhooks->newEntity($webhook);
                try {
                    $Webhooks->saveOrFail($new);
                    Log::info("Created webhook: {$name} ({$eventKey})");
                } catch (\Throwable $e) {
                    Log::error("Failed to save webhook [{$eventKey}]: " . $e->getMessage());
                }
            }
        }

        $placeholders = implode(',', array_fill(0, count($validEventKeys), '?'));
        $sql = "DELETE FROM webhooks WHERE event_key NOT IN ($placeholders)";
        $this->executeWithParams($sql, $validEventKeys);
    }

    /**
     * Utility method to execute a prepared statement with bindings
     */
    private function executeWithParams(string $sql, array $params)
    {
        $connection = $this->getAdapter()->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
    }

    private function generateExternalDataSourceAttribute($type, $field, $name, $value)
    {
        return [
            'id' => Text::uuid(),
            'external_data_source_type' => $type,
            'attribute_field' => $field,
            'attribute_name' => $name,
            'value' => $value,
            'created' => date('Y-m-d H:i:s'),
            'created_user_id' => 1,
        ];
    }
}




