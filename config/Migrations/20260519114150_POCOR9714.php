<?php

use Migrations\AbstractMigration;

class POCOR9714 extends AbstractMigration
{
    public function up()
    {
        // 1. backup summary tables
        $this->execute('CREATE TABLE `zz_9714_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_9714_config_items` SELECT * FROM `config_items`');

        $this->execute('CREATE TABLE `zz_9714_themes` LIKE `themes`');
        $this->execute('INSERT INTO `zz_9714_themes` SELECT * FROM `themes`');
        $this->execute("
            UPDATE config_items
            SET 
                name = 'OpenEMIS Admissions',
                code = 'openemis_admissions',
                label = 'OpenEMIS Admissions'
            WHERE code = 'openemis_registration'
        ");

        $this->execute("
            UPDATE themes
            SET
                value = REPLACE(value, 'Registration', 'Admissions'),
                default_value = 'OpenEMIS Admissions'
            WHERE default_value = 'OpenEMIS Core'
        ");

        
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS config_items');
        $this->execute('RENAME TABLE zz_9714_config_items TO config_items');

        $this->execute('DROP TABLE IF EXISTS themes');
        $this->execute('RENAME TABLE zz_9714_themes TO themes');
    }
}
