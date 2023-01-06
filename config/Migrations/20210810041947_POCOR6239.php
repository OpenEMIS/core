<?php
use Migrations\AbstractMigration;

class POCOR6239 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6239_training_achievement_types` LIKE `training_achievement_types`');
        $this->execute('INSERT INTO `zz_6239_training_achievement_types` SELECT * FROM `training_achievement_types`');
        
        //Drop table training_achievement_types
        $this->execute('DROP TABLE IF EXISTS `training_achievement_types`');
    }

    //rollback
    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `training_achievement_types`');
       $this->execute('RENAME TABLE `zz_6239_training_achievement_types` TO `training_achievement_types`');
    }
}
