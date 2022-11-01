<?php

use Phinx\Migration\AbstractMigration;

class POCOR5144 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup locale_contents table
        $this->execute('CREATE TABLE `z_5144_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5144_locale_contents` SELECT * FROM `locale_contents`');

        #Insert data into locale_contents table

        $this->execute('INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("Institution Code",NULL,NULL,"1",NOW())');
        $this->execute('INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("Institution Type",NULL,NULL,"1",NOW())');
        $this->execute('INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("Homeroom Teacher",NULL,NULL,"1",NOW())');
        $this->execute('INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("Total Students",NULL,NULL,"1",NOW())');
    }

    public function down() {
       $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5144_locale_contents` TO `locale_contents`');
    }
}