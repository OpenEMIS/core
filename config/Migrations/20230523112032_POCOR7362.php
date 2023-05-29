<?php
use Migrations\AbstractMigration;

class POCOR7362 extends AbstractMigration
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
        
        $this->execute("CREATE TABLE IF NOT EXISTS `textbook_dimensions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `order` int(3) NOT NULL,
            `visible` int(1) NOT NULL DEFAULT '1',
			`editable` int(1) NOT NULL DEFAULT '1',
			`default` int(1) NOT NULL DEFAULT '0',
			`international_code` varchar(50) DEFAULT NULL,
			`national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");


        $this->execute("INSERT INTO `textbook_dimensions` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`,`modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, '50mmx76mm', '1', '1', '1', '0', '', '', NULL, NULL, '1', '2023-05-23 12:00:00')");

        $this->execute("INSERT INTO `textbook_dimensions` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`,`modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, '90mmx140mm', '2', '1', '1', '0', '', '', NULL, NULL, '1', '2023-05-23 12:00:00')");

        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        $this->execute('ALTER TABLE textbooks
        ADD COLUMN textbook_dimension_id INT(11) AFTER education_subject_id,
        ADD CONSTRAINT fk_textbook_dimensions FOREIGN KEY (textbook_dimension_id) REFERENCES textbook_dimensions (id);');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1;');

    }

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        $this->execute('ALTER TABLE textbooks DROP FOREIGN KEY fk_textbook_dimensions');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1;');
        $this->execute('ALTER TABLE textbooks DROP COLUMN textbook_dimension_id');
        $this->execute('DROP TABLE IF EXISTS `textbook_dimensions`');
    }
}
