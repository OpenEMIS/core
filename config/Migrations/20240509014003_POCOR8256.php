<?php
use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR8256 extends AbstractMigration
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
        // backup locale_contents table
        $this->execute('DROP TABLE IF EXISTS `z_8256_locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `z_8256_training_sessions`');
        $this->execute('DROP TABLE IF EXISTS `z_8256_training_sessions`');
        $this->execute('DROP TABLE IF EXISTS `training_session_evaluators`');
        $this->execute('CREATE TABLE `z_8256_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_8256_locale_contents` SELECT * FROM `locale_contents`');

        $this->execute('CREATE TABLE `z_8256_training_sessions` LIKE `training_sessions`');
        $this->execute('INSERT INTO `z_8256_training_sessions` SELECT * FROM `training_sessions`');

        #Insert data into locale_contents table
        $this->execute('INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("Training Center",NULL,NULL,"1",NOW())');
        $this->execute('INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("Training Evaluators",NULL,NULL,"1",NOW())');

        // Create new column in training_sessions table
       $this->execute("ALTER TABLE `training_sessions` ADD COLUMN `training_center` varchar(100)  AFTER `area_id`");

        // create training_session_evaluators table
       $this->execute("CREATE TABLE `training_session_evaluators` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id', 
                     `evaluator_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11)  NOT NULL,
                      `created` datetime NOT NULL,
                        PRIMARY KEY (`id`)
                      )ENGINE=InnoDB DEFAULT CHARSET=utf8");
       $this->execute("ALTER TABLE `training_session_evaluators` ADD CONSTRAINT `train_sess_eval_fk_train_sess_id` FOREIGN KEY (`training_session_id`) REFERENCES training_sessions(`id`)");
       $this->execute("ALTER TABLE `training_session_evaluators` ADD CONSTRAINT `train_sess_eval_fk_eval_id`FOREIGN KEY (`evaluator_id`) REFERENCES security_users(`id`)");

    }

    public function down() {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_8256_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `training_sessions`');
        $this->execute('RENAME TABLE `z_8256_training_sessions` TO `training_sessions`');
        $this->execute('DROP TABLE IF EXISTS `training_session_evaluators`');
    }
}
