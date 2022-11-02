<?php
use Migrations\AbstractMigration;

class POCOR6858 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_6858_health_immunization_types` LIKE `health_immunization_types`');
        $this->execute('INSERT INTO `zz_6858_health_immunization_types` SELECT * FROM `health_immunization_types`');
        // End

        $this->execute("ALTER TABLE `health_immunization_types` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; ");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `health_immunization_types`');
        $this->execute('RENAME TABLE `zz_6858_health_immunization_types` TO `health_immunization_types`');
    }
}
