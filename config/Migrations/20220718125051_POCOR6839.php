<?php
use Migrations\AbstractMigration;

class POCOR6839 extends AbstractMigration
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
        /** backup */
        $this->execute('CREATE TABLE `zz_6839_scholarships` LIKE `scholarships`');
        $this->execute('INSERT INTO `zz_6839_scholarships` SELECT * FROM `scholarships`');
        /** updating existing record */
        
        $this->execute("CREATE TABLE IF NOT EXISTS `scholarship_financial_assistances` (
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

        $this->execute('ALTER TABLE `scholarships` ADD `scholarship_financial_assistance_id` INT(11) NULL AFTER `scholarship_financial_assistance_type_id`');

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `scholarships`');
        $this->execute('RENAME TABLE `zz_6839_scholarships` TO `scholarships`');


        $this->execute('DROP TABLE IF EXISTS `scholarship_financial_assistances`');
        $this->execute('RENAME TABLE `zz_6839_scholarship_financial_assistances` TO `scholarship_financial_assistances`');
    }
}
