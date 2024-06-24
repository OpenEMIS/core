<?php

use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class POCOR7981 extends AbstractMigration
{
    private $presentOption = 'None';
    private $presentOptionID = 0;
    private $unhcrIdentityID = 0;
    private $unhcrNationalityID = 0;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        if (!$this->hasTable('z_7981_config_items')) {
            $this->execute('CREATE TABLE `z_7981_config_items` LIKE `config_items`');
        } else {
            $this->execute('TRUNCATE TABLE `z_7981_config_items`');

        }
        if (!$this->hasTable('z_7981_nationalities')) {
            $this->execute('CREATE TABLE `z_7981_nationalities` LIKE `nationalities`');
        } else {
            $this->execute('TRUNCATE TABLE `z_7981_nationalities`');
        }
        if (!$this->hasTable('z_7981_identity_types')) {
            $this->execute('CREATE TABLE `z_7981_identity_types` LIKE `identity_types`');
        } else {
            $this->execute('TRUNCATE TABLE `z_7981_identity_types`');
        }
        $this->execute('INSERT IGNORE INTO `z_7981_config_items` SELECT * FROM `config_items`');
        $this->execute('INSERT IGNORE INTO `z_7981_nationalities` SELECT * FROM `nationalities`');
        $this->execute('INSERT IGNORE INTO `z_7981_identity_types` SELECT * FROM `identity_types`');
        $this->getPresentOption();
        $this->changeConfigItems();
        $this->insertNewConfigItems();
        $this->setPresentOption();
        $this->insertIdentityUNHCR();
        $this->insertNationalityUNHCR();
        //External Data Source - Identity
    }

    public function getPresentOption()
    {
        $ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
        $presentOption = $ConfigItemsTable->find()
            ->where([
                $ConfigItemsTable->aliasField('code') => 'external_data_source_type'
            ])
            ->first();
        if (!empty($presentOption)) {
//            if ($presentOption->value != "OpenEMIS Identity") { // "OpenEMIS Identity" is ignored
            $this->presentOption = $presentOption->value;
            $this->presentOptionID = $presentOption->id;
//            }
        }
    }

    private function changeConfigItems()
    {
        $this->execute("UPDATE `config_items` SET
                          `name` = 'Custom',
                          `label` = 'Custom',
                          `code` = 'external_data_source_custom',
                          `value` = '0',
                          `value_selection` = '',
                          `default_value` = '0',
                          `option_type` = 'completeness'
                      WHERE `code` = 'external_data_source_type'");
    }

    /**
     * @return void
     */
    public function insertNewConfigItems()
    {
        $table = $this->table('config_items');
        $data = [
//            $this->generateData(
//                'Custom',
//                'external_data_source_custom',
//                'External Data Source - Identity',
//                'Custom'),
            $this->generateData(
                'Jordan CSPD',
                'external_data_source_jordan_cspd',
                'External Data Source - Identity',
                'Jordan CSPD'),
//            $this->generateData(
//                'Refugee ID',
//                'external_data_source_refujee_id',
//                'External Data Source - Identity',
//                'Refugee ID'),
            $this->generateData(
                'UNHCR',
                'external_data_source_unhcr',
                'External Data Source - Identity',
                'UNHCR',
                '1'),

        ];
//        try {
        $table->insert($data)->save();
//        } catch (\Exception $exception) {
//
//        }
    }

    private function generateData($name, $code, $type, $label, $value = '0')
    {
        return [
            'id' => NULL,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'label' => $label,
            'value' => $value,
            'value_selection' => '0',
            'default_value' => '0',
            'editable' => '1',
            'visible' => '1',
            'field_type' => 'Dropdown',
            'option_type' => 'completeness',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
    }

    private function setPresentOption()
    {
        $presentOption = $this->presentOption;
        $presentOptionID = $this->presentOptionID;
        if ($presentOption != "None") {
            if ($presentOption != "OpenEMIS Identity") {
                $this->execute("UPDATE `config_items` SET
                          `value` = '1'
                      WHERE `name` = '" . $presentOption . "'");
                $this->execute("UPDATE `nationalities` SET
                          `external_validation` = $presentOptionID
                      WHERE `external_validation` = 1");
            }else{
                $this->execute("UPDATE `nationalities` SET
                          `external_validation` = 0
                      WHERE `external_validation` = 1");
            }
        }
    }

    public function insertIdentityUNHCR()
    {
        $order_data = $this->fetchRow("SELECT  max(`order`) FROM `identity_types`");
        $max_order = $order_data[0];
        $id_data = $this->fetchRow("SELECT  max(`id`) FROM `identity_types`");
        $max_id = $id_data[0];
        $unhcrIdentityID = $max_id + 1;
        $this->unhcrIdentityID = $unhcrIdentityID;
        $data_ex = [
            'id' => $unhcrIdentityID,
            'name' => 'UNHCR',
            'validation_pattern' => null,
            'order' => $max_order + 1,
            'visible' => 1,
            'editable' => 1,
            'default' => 0,
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('identity_types');
        $table->insert($data_ex);
        $table->saveData();

    }

    private function insertNationalityUNHCR()
    {
        $order_data = $this->fetchRow("SELECT max(`order`) FROM `nationalities`");
        $max_order = $order_data[0];
        $max_data = $this->fetchRow("SELECT max(`id`) FROM `nationalities`");
        $max_id = $max_data[0];
        $unhcrNationalityID = $max_id + 1;
        $this->unhcrNationalityID = $unhcrNationalityID;
        $unhcrIdentityID = $this->unhcrIdentityID;
        $external_validation_data = $this->fetchRow("SELECT `id` FROM `config_items` where `code` = 'external_data_source_unhcr'");
        $unhcrValidationID = $external_validation_data['id'];
        $data_ex = [
            'id' => $unhcrNationalityID,
            'name' => 'UNHCR',
            'order' => $max_order + 1,
            'visible' => 0,
            'editable' => 1,
            'identity_type_id' => $unhcrIdentityID,
            'default' => 0,
            'international_code' => null,
            'national_code' => null,
            'external_validation' => $unhcrValidationID,
            'is_refugee' => 0,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('nationalities');
        $table->insert($data_ex);
        $table->saveData();
    }

    public function down()
    {
        if ($this->hasTable('z_7981_config_items')) {
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `z_7981_config_items` TO `config_items`');
        }
        if ($this->hasTable('z_7981_nationalities')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `nationalities`');
            $this->execute('RENAME TABLE `z_7981_nationalities` TO `nationalities`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_7981_identity_types')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `identity_types`');
            $this->execute('RENAME TABLE `z_7981_identity_types` TO `identity_types`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }


}
