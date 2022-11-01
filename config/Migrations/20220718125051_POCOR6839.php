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

        $this->execute('CREATE TABLE `zz_6839_scholarship_financial_assistance_types` LIKE `scholarship_financial_assistance_types`');
        $this->execute('INSERT INTO `zz_6839_scholarship_financial_assistance_types` SELECT * FROM `scholarship_financial_assistance_types`');

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
             PRIMARY KEY (`id`),
             FOREIGN KEY (`modified_user_id`) REFERENCES `security_users`(`id`),
             FOREIGN KEY (created_user_id) REFERENCES `security_users`(`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $data = [
            [
                'name'  => 'Full Financial Assistance Award (FFAA)',
                'order'  => '1',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => 'FFAA',
                'national_code' => 'FFAA',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Partial Financial Assistance Award (PFAA)',
                'order'  => '2',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => 'PFAA',
                'national_code' => 'PFAA',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'One-Off Financial Assistance Award (OFAA)',
                'order'  => '3',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => 'OFAA',
                'national_code' => 'OFAA',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Distance Learning Financial Assistance (DLFA)',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => 'DLFA',
                'national_code' => 'DLFA',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('scholarship_financial_assistances')->insert($data)->save(); 

        $this->execute('ALTER TABLE `scholarships` ADD `scholarship_financial_assistance_id` INT(11) NULL AFTER `scholarship_financial_assistance_type_id`, ADD FOREIGN KEY (`scholarship_financial_assistance_id`) REFERENCES `scholarship_financial_assistances`(`id`)');

        $this->execute('DELETE FROM scholarship_financial_assistance_types WHERE code = "FULLSCHOLARSHIP"');
        $this->execute('DELETE FROM scholarship_financial_assistance_types WHERE code = "PARTIALSCHOLARSHIP"');
        $this->execute('DELETE FROM scholarship_financial_assistance_types WHERE code = "DISTANCELEARNING"');

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `scholarships`');
        $this->execute('RENAME TABLE `zz_6839_scholarships` TO `scholarships`');

        $this->execute('DROP TABLE IF EXISTS `scholarship_financial_assistance_types`');
        $this->execute('RENAME TABLE `zz_6839_scholarship_financial_assistance_types` TO `scholarship_financial_assistance_types`');

        $this->execute('DROP TABLE IF EXISTS `scholarship_financial_assistances`');

    }
}
