<?php
use Migrations\AbstractMigration;

class POCOR7223 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7223_student_behaviours` LIKE `student_behaviours`');
        $this->execute('INSERT INTO `zz_7223_student_behaviours` SELECT * FROM `student_behaviours`');
        $this->execute('ALTER TABLE `student_behaviours` DROP COLUMN `title`');

        $this->execute("CREATE TABLE IF NOT EXISTS `student_behaviour_classifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `order` int(3) DEFAULT NULL,
            `visible` int(1) DEFAULT '1',
			`editable` int(1) DEFAULT '1',
			`default` int(1) DEFAULT '0',
			`international_code` varchar(50) DEFAULT NULL,
			`national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");
    
    $this->execute('ALTER TABLE `student_behaviours` ADD COLUMN student_behaviour_classification_id INT(11)');
    $this->execute('SET FOREIGN_KEY_CHECKS=0;');
    $this->execute('ALTER TABLE `student_behaviours` ADD FOREIGN KEY (`student_behaviour_classification_id`) REFERENCES `student_behaviour_classifications` (`id`)');
    $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_behaviours`');
        $this->execute('RENAME TABLE `zz_7223_student_behaviours` TO `student_behaviours`');

        $this->execute('DROP TABLE IF EXISTS `student_behaviour_classifications`');
    }
}
