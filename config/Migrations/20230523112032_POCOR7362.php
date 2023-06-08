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
        //for textbook dimension
        // $this->execute("CREATE TABLE IF NOT EXISTS `textbook_dimensions` (
        //     `id` int(11) NOT NULL AUTO_INCREMENT,
        //     `name` varchar(50) NOT NULL,
        //     `order` int(3) NOT NULL,
        //     `visible` int(1) NOT NULL DEFAULT '1',
		// 	`editable` int(1) NOT NULL DEFAULT '1',
		// 	`default` int(1) NOT NULL DEFAULT '0',
		// 	`international_code` varchar(50) DEFAULT NULL,
		// 	`national_code` varchar(50) DEFAULT NULL,
        //     `modified_user_id` int(11) DEFAULT NULL,
        //     `modified` datetime DEFAULT NULL,
        //     `created_user_id` int(11) NOT NULL,
        //     `created` datetime NOT NULL,
        //      PRIMARY KEY (`id`)
        //   )  ENGINE=InnoDB DEFAULT CHARSET=utf8");


        // $this->execute("INSERT INTO `textbook_dimensions` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`,`modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, '50mmx76mm', '1', '1', '1', '0', '', '', NULL, NULL, '1', '2023-05-23 12:00:00')");

        // $this->execute("INSERT INTO `textbook_dimensions` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`,`modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, '90mmx140mm', '2', '1', '1', '0', '', '', NULL, NULL, '1', '2023-05-23 12:00:00')");

        // $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        // $this->execute('ALTER TABLE textbooks
        // ADD COLUMN textbook_dimension_id INT(11) AFTER education_subject_id,
        // ADD CONSTRAINT fk_textbook_dimensions FOREIGN KEY (textbook_dimension_id) REFERENCES textbook_dimensions (id);');
        // $this->execute('SET FOREIGN_KEY_CHECKS = 1;');

        //text dimensions ends


        //for textbook statuses

        // $this->execute('CREATE TABLE `zz_7362_textbook_statuses` LIKE `textbook_statuses`');
        // $this->execute('INSERT INTO `zz_7362_textbook_statuses` SELECT * FROM `textbook_statuses`');

        // $this->execute("ALTER TABLE `textbook_statuses` ADD `order` int(3) NOT NULL");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `visible` int(1) NOT NULL DEFAULT '1'");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `editable` int(1) NOT NULL DEFAULT '1'");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `default` int(1) NOT NULL DEFAULT '0'");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `international_code` varchar(50) DEFAULT NULL");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `national_code` varchar(50) DEFAULT NULL");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `modified_user_id` int(11) DEFAULT NULL");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `modified` datetime DEFAULT NULL");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `created_user_id` int(11) NOT NULL");
        // $this->execute("ALTER TABLE `textbook_statuses` ADD `created` datetime");

        // $this->execute("UPDATE `textbook_statuses` SET `order` = '1' WHERE `id`= 1");
        // $this->execute("UPDATE `textbook_statuses` SET `international_code` = '' WHERE `id`= 1");
        // $this->execute("UPDATE `textbook_statuses` SET `national_code` = '' WHERE `id`= 1");
        // $this->execute("UPDATE `textbook_statuses` SET `created_user_id` = '1' WHERE `id`= 1");
        // $this->execute("UPDATE `textbook_statuses` SET `created` = '2023-06-07 12:00:00' WHERE `id`= 1");

        // $this->execute("UPDATE `textbook_statuses` SET `order` = '2' WHERE `id`= 2");
        // $this->execute("UPDATE `textbook_statuses` SET `international_code` = '' WHERE `id`= 2");
        // $this->execute("UPDATE `textbook_statuses` SET `national_code` = '' WHERE `id`= 2");
        // $this->execute("UPDATE `textbook_statuses` SET `created_user_id` = '1' WHERE `id`= 2");
        // $this->execute("UPDATE `textbook_statuses` SET `created` = '2023-06-07 12:00:00' WHERE `id`= 2");

        // textbook status ends

        //for field option

        $this->execute('CREATE TABLE `zz_7362_field_options` LIKE `field_options`');
        $this->execute('INSERT INTO `zz_7362_field_options` SELECT * FROM `field_options`');
       
        $order = $this->fetchRow("SELECT `order` FROM `field_options` ORDER BY `id` DESC LIMIT 1");
     
        $data=[
                [
                    'name' => 'Textbook Dimensions',
                    'category' => 'Institution',
                    'table_name' => 'textbook_dimensions',
                    'order' => $order[0]+1,
                    'modified_by' => NULL,
                    'modified'=>NULL,
                    'created_by' =>'1',
                    'created' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Textbook Statuses',
                    'category' => 'Institution',
                    'table_name' => 'textbook_statuses',
                    'order' => $order[0]+2,
                    'modified_by' => NULL,
                    'modified'=>NULL,
                    'created_by' =>'1',
                    'created' => date('Y-m-d H:i:s'),
                ]
            ]; 
            $this->insert('field_options', $data);

        //field options ends
    }

    public function down()
    {

        // field options starts

        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `zz_7362_field_options` TO `field_options`');

        // field options ends


        // textbook status starts

        // $this->execute('DROP TABLE IF EXISTS `textbook_statuses`');
        // $this->execute('RENAME TABLE `zz_7362_textbook_statuses` TO `textbook_statuses`');

        // textbook status ends

        // textbook dimensions starts

        // $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        // $this->execute('ALTER TABLE textbooks DROP FOREIGN KEY fk_textbook_dimensions');
        // $this->execute('SET FOREIGN_KEY_CHECKS = 1;');
        // $this->execute('ALTER TABLE textbooks DROP COLUMN textbook_dimension_id');
        // $this->execute('DROP TABLE IF EXISTS `textbook_dimensions`');

        // textbook dimensions ends
    }
}
