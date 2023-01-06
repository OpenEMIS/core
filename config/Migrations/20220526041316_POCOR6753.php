<?php
use Migrations\AbstractMigration;

class POCOR6753 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_6753_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_6753_config_items` SELECT * FROM `config_items`');

        $this->execute(
            'UPDATE `config_items` SET `value` = 1 
            WHERE `name` = "First Day of Week" AND `code` = "first_day_of_week"
            AND `type` = "Attendance"
            AND `label` = "First Day of Week"'
        );
    }

    public function down()
    {
        // rollback
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6753_config_items` TO `config_items`');
    }
}
