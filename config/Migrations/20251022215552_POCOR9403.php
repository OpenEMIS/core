<?php

declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;

class POCOR9403 extends AbstractMigration
{
    const OPEN_EMIS_EXAMS = 'OpenEMIS Exams';
    const EXTERNAL_DATA_SOURCE_WEBHOOK = 'External Data Source - Webhook';
    const EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM = 'external_data_source_webhooks_custom';
    const EXTERNAL_DATA_SOURCE_WEBHOOKS_EXAMS = 'external_data_source_webhooks_exams';

    public function up()
    {
//        return;

        $this->backupTables();
//
        $this->insertNewExternalDataSourceAttributes();
//
        $this->insertNewConfigItems();

        $this->addNewExternalDataSource();

        $this->addNewWebhookFields();

    }

    // rollback

    /**
     * @return void
     */
    public function backupTables()
    {
        if(!$this->hasTable('z_9403_config_items')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9403_config_items` LIKE `config_items`');
            $this->execute('INSERT INTO `z_9403_config_items` SELECT * FROM `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if(!$this->hasTable('z_9403_external_data_source_attributes')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9403_external_data_source_attributes` LIKE `external_data_source_attributes`');
            $this->execute('INSERT INTO `z_9403_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if(!$this->hasTable('z_9403_webhooks')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9403_webhooks` LIKE `webhooks`');
            $this->execute('INSERT INTO `z_9403_webhooks` SELECT * FROM `webhooks`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if(!$this->hasTable('z_9403_webhook_events')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9403_webhook_events` LIKE `webhook_events`');
            $this->execute('INSERT INTO `z_9403_webhook_events` SELECT * FROM `webhook_events`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function down()
    {
        $this->restoreTable();
    }

    /**
     * @return void
     */
    public function restoreTable()
    {
        if ($this->hasTable('z_9403_config_items')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `z_9403_config_items` TO `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_9403_external_data_source_attributes')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `external_data_source_attributes`');
            $this->execute('RENAME TABLE `z_9403_external_data_source_attributes` TO `external_data_source_attributes`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_9403_webhooks')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `webhooks`');
            $this->execute('RENAME TABLE `z_9403_webhooks` TO `webhooks`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_9403_webhook_events')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `webhook_events`');
            $this->execute('RENAME TABLE `z_9403_webhook_events` TO `webhook_events`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    function generateConfigData($name, $code, $type, $label) {
        return [
            'id' => NULL,
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

    /**
     * @return void
     */
    public function insertNewConfigItems()
    {
        $table = $this->table('config_items');
        $data = [
            $this->generateConfigData(
                self::OPEN_EMIS_EXAMS,
                self::EXTERNAL_DATA_SOURCE_WEBHOOKS_EXAMS,
                self::EXTERNAL_DATA_SOURCE_WEBHOOK,
                self::OPEN_EMIS_EXAMS),
            $this->generateConfigData(
                'Custom',
                self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM,
                self::EXTERNAL_DATA_SOURCE_WEBHOOK,
                'Custom'),
        ];
        $table->insert($data)->save();
    }

    public function insertNewExternalDataSourceAttributes()
    {
        $table = $this->table('external_data_source_attributes');
        $password = 'demo';
//        $password = (new DefaultPasswordHasher)->hash($password);

        $attributes = [
            // API Credentials
            ['api_url', 'api_url', 'https://demo.openemis.org/exams/api/v5'],
            ['username', 'username', 'admin'],
            ['password', 'password', $password],
            ['api_key', 'api_key', 'apikeytest'],

        ];

        $data = array_map(fn($attr) => $this->generateExternalDataSourceAttribute(self::OPEN_EMIS_EXAMS, ...$attr), $attributes);

        $table->insert($data)->save();
    }

    public function addNewExternalDataSource()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('ALTER TABLE `webhooks` ADD COLUMN external_data_source_id INT(11) NOT NULL after `status`');
        $configItemEntity = $this->fetchRow("SELECT `id` FROM `config_items` WHERE `code` = '" . self::EXTERNAL_DATA_SOURCE_WEBHOOKS_CUSTOM . "'");
        $configItemId = $configItemEntity['id'];

        $this->execute("UPDATE `webhooks` set `external_data_source_id` = {$configItemId}");
        $this->execute('ALTER TABLE `webhooks` ADD FOREIGN KEY (`external_data_source_id`) REFERENCES `config_items` (`id`)');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }

    public function addNewWebhookFields()
    {
        $this->execute('ALTER TABLE `webhooks` ADD `event_key` VARCHAR(45) DEFAULT NULL');
        $this->execute('ALTER TABLE `webhooks` ADD  `query_template` VARCHAR(255) DEFAULT NULL');
        $this->execute('ALTER TABLE `webhooks` ADD  `body_template` TEXT DEFAULT NULL');
        $locator = TableRegistry::getTableLocator();
        $WebhookEvents = $locator->get('Webhook.WebhookEvents');
        $Webhooks = $locator->get('Webhook.Webhooks');
        $allLinks = $WebhookEvents->find()->all();
        foreach ($allLinks as $link) {
            $webhook = $Webhooks->get($link->webhook_id);
            $new = $Webhooks->newEntity($webhook->toArray());
            $new->id = null;
            $new->event_key = $link->event_key;
            $Webhooks->save($new);
        }
        $this->execute('TRUNCATE TABLE `webhook_events`');
        $Webhooks->deleteAll(['event_key IS' => null]);
        $this->execute('ALTER TABLE `webhooks` MODIFY `event_key` VARCHAR(45) NOT NULL');
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
