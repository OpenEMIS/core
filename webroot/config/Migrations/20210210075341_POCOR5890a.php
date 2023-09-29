<?php
use Migrations\AbstractMigration;

class POCOR5890a extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5890_user_health_immunizations` LIKE `user_health_immunizations`');
        $this->execute('INSERT INTO `zz_5890_user_health_immunizations` SELECT * FROM `user_health_immunizations`');
        // End

        $this->execute("ALTER TABLE `user_health_immunizations` CHANGE `dosage` `dosage` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; ");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `user_health_immunizations`');
        $this->execute('RENAME TABLE `zz_5890_user_health_immunizations` TO `user_health_immunizations`');
    }
}
