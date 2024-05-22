<?php
use Migrations\AbstractMigration;

class POCOR7049 extends AbstractMigration
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
        // Backup user_special_needs_plans table
        $this->execute('CREATE TABLE `zz_7049_user_special_needs_plans` LIKE `user_special_needs_plans`');
        $this->execute('INSERT INTO `zz_7049_user_special_needs_plans` SELECT * FROM `user_special_needs_plans`');

        $this->execute('ALTER TABLE `user_special_needs_plans` ADD `special_needs_plan_types_id` INT NOT NULL AFTER `academic_period_id`');
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `user_special_needs_plans`');
        $this->execute('RENAME TABLE `zz_7049_user_special_needs_plans` TO `user_special_needs_plans`'); 
    }
}
