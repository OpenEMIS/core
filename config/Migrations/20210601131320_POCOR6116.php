<?php
use Migrations\AbstractMigration;

class POCOR6116 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6116_institution_rooms` LIKE `institution_rooms`');
        $this->execute('INSERT INTO `zz_6116_institution_rooms` SELECT * FROM `institution_rooms`');

        $this->execute('ALTER TABLE `institution_rooms` ADD COLUMN `area` FLOAT NULL AFTER `infrastructure_condition_id`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_rooms`');
        $this->execute('RENAME TABLE `zz_6116_institution_rooms` TO `institution_rooms`');
    }
}
