<?php

use Phinx\Migration\AbstractMigration;

class POCOR4253 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup the table
        $this->execute('RENAME TABLE `institution_network_connectivities` TO `z_4253_institution_network_connectivities`');
        $this->execute('CREATE TABLE `z_4253_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `z_4253_institutions` SELECT * FROM `institutions`');
        $this->execute('CREATE TABLE `z_4253_utility_internet_types` LIKE `utility_internet_types`');
        $this->execute('INSERT INTO `z_4253_utility_internet_types` SELECT * FROM `utility_internet_types`');
        $this->execute('CREATE TABLE `z_4253_infrastructure_utility_internets` LIKE `infrastructure_utility_internets`');
        $this->execute('INSERT INTO `z_4253_infrastructure_utility_internets` SELECT * FROM `infrastructure_utility_internets`');
        // end backup

        // insert data to utility_internet_types from z_4253_institution_network_connectivities
        $this->execute('
            INSERT INTO `utility_internet_types` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_4253_institution_network_connectivities`
        ');
        // end of insert data

        // remove column institution_network_connectivity_id from institutions
        $table = $this->table('institutions');
        $table
            ->removeColumn('institution_network_connectivity_id')
            ->save();
        // end remove column

        // utility_internet_bandwidths
        $table = $this->table('utility_internet_bandwidths', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains bandwidth of internet utilities'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end utility_internet_bandwidths

        // infrastructure_utility_internets
        $table = $this->table('infrastructure_utility_internets');
        $table
            ->addColumn('utility_internet_bandwidth_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to utility_internet_bandwidths.id',
                'after' => 'institution_id'
            ])
            ->addIndex('utility_internet_bandwidth_id')
            ->save();

        $this->execute("ALTER TABLE `infrastructure_utility_internets` CHANGE `utility_internet_condition_id` `utility_internet_condition_id` INT(11) NULL DEFAULT NULL COMMENT 'links to utility_internet_conditions.id'");
        $this->execute("ALTER TABLE `infrastructure_utility_internets` CHANGE `internet_purpose` `internet_purpose` INT(11) NULL DEFAULT NULL COMMENT '1 => Teaching, 2 => Non-Teaching'");
        // end infrastructure_utility_internets

        // insert data infrastructure_utility_internets from institutions institution_network_connectivity_id
        $this->execute('
            INSERT INTO `infrastructure_utility_internets` (`academic_period_id`, `institution_id`, `utility_internet_type_id`, `created_user_id`, `created`)
            SELECT (SELECT `id` FROM `academic_periods` WHERE `current` = 1), `id`, `institution_network_connectivity_id`, 1, NOW()
            FROM  `z_4253_institutions`
        ');
        // end of insert data
    }

    // rollback
    public function down()
    {
        // restore the backup table
        $this->execute('DROP TABLE IF EXISTS `institution_network_connectivities`');
        $this->execute('RENAME TABLE `z_4253_institution_network_connectivities` TO `institution_network_connectivities`');
        $this->execute('DROP TABLE IF EXISTS `institutions`');
        $this->execute('RENAME TABLE `z_4253_institutions` TO `institutions`');
        $this->execute('DROP TABLE IF EXISTS `utility_internet_types`');
        $this->execute('RENAME TABLE `z_4253_utility_internet_types` TO `utility_internet_types`');
        $this->execute('DROP TABLE IF EXISTS `infrastructure_utility_internets`');
        $this->execute('RENAME TABLE `z_4253_infrastructure_utility_internets` TO `infrastructure_utility_internets`');
        // end restore

        // dropping table
        $this->execute('DROP TABLE `utility_internet_bandwidths`');
        // end dropping table
    }
}
