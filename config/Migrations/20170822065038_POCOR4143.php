<?php

use Phinx\Migration\AbstractMigration;

class POCOR4143 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        //training_session_trainers
        //backup the table
        $this->execute('CREATE TABLE `z_4143_training_session_trainers` LIKE `training_session_trainers`');
        $this->execute('INSERT INTO `z_4143_training_session_trainers` 
                        SELECT * FROM `training_session_trainers` 
                        WHERE NOT EXISTS (
                            SELECT 1 FROM `security_users` 
                            WHERE `training_session_trainers`.`trainer_id` = `security_users`.`id`)'
                    );

        //delete orpan records
        $this->execute('DELETE FROM `training_session_trainers` 
                        WHERE NOT EXISTS (
                            SELECT 1 FROM `security_users` 
                            WHERE `training_session_trainers`.`trainer_id` = `security_users`.`id`
                    )');
    }

    public function down()
    {
        $this->execute('INSERT INTO `training_session_trainers` SELECT * FROM `z_4143_training_session_trainers`'); 
        $this->execute('DROP TABLE IF EXISTS `z_4143_training_session_trainers`');
    }
}
