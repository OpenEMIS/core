<?php
use Migrations\AbstractMigration;

class POCOR9679 extends AbstractMigration
{
    /**
     * Up Method
     *
     * Updates Health Body Mass and Insurance permissions
     * to align with Profiles controller permission mapping.
     *
     * @return void
     */
    public function up()
    {
        /** Backup */
        $this->execute('CREATE TABLE `zz_9679_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9679_security_functions` SELECT * FROM `security_functions`');

        /** Update Body Mass permission mapping */
        $this->execute("
            UPDATE `security_functions`
            SET
                `controller` = 'Profiles',
                `_view` = 'HealthBodyMasses.index|HealthBodyMasses.view',
                `_edit` = 'HealthBodyMasses.edit',
                `_add` = 'HealthBodyMasses.add',
                `_delete` = 'HealthBodyMasses.remove'
            WHERE `name` = 'Body Mass'
              AND `module` = 'Personal'
              AND `category` = 'Health'
        ");

        /** Update Insurance permission mapping */
        $this->execute("
            UPDATE `security_functions`
            SET
                `controller` = 'Profiles',
                `_view` = 'HealthInsurances.index|HealthInsurances.view',
                `_edit` = 'HealthInsurances.edit',
                `_add` = 'HealthInsurances.add',
                `_delete` = 'HealthInsurances.remove'
            WHERE `name` = 'Insurances'
              AND `module` = 'Personal'
              AND `category` = 'Health'
        ");
    }

    /**
     * Rollback Method
     *
     * Restores original security_functions table.
     *
     * @return void
     */
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_9679_security_functions` TO `security_functions`');
    }
}