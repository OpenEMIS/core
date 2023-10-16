<?php
use Migrations\AbstractMigration;

class POCOR5883 extends AbstractMigration
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

        //Creating new table
        $this->execute("CREATE TABLE `staff_salary_transactions` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `amount` decimal(11,2) NOT NULL DEFAULT '0.00',
                        `salary_addition_type_id` int(11) DEFAULT NULL COMMENT 'links to salary_addition_types.id',
                        `salary_deduction_type_id` int(11) DEFAULT NULL COMMENT 'links to salary_deduction_types.id',
                        `staff_salary_id` int(11) NOT NULL COMMENT 'links to staff_salaries.id',
                        `modified_user_id` int(11) DEFAULT NULL,
                        `modified` datetime DEFAULT NULL,
                        `created_user_id` int(11) NOT NULL,
                        `created` datetime NOT NULL,
                        PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains any items with an amount to be added on top of the staff salary'
                    ");

        //Backup of staff_salaries
        $this->execute("CREATE TABLE IF NOT EXISTS `z_5883_staff_salaries` LIKE `staff_salaries`");
        $this->execute("INSERT INTO `z_5883_staff_salaries` SELECT * FROM `staff_salaries`");

        //Removeing  additions, deductions columns from staff_salaries table
        $this->execute("ALTER TABLE `staff_salaries` DROP COLUMN `additions`, DROP COLUMN `deductions`");

        //Backup of staff_salary_additions
        $this->execute("CREATE TABLE IF NOT EXISTS `z_5883_staff_salary_additions` LIKE `staff_salary_additions`");
        $this->execute("INSERT INTO `z_5883_staff_salary_additions` SELECT * FROM `staff_salary_additions`");

        //Altering table staff_salary_additions
        $this->execute('ALTER TABLE `staff_salary_additions` ADD `salary_deduction_type_id` INT NOT NULL AFTER `staff_salary_id`');
        
        $this->execute("ALTER TABLE `staff_salary_additions` CHANGE `salary_deduction_type_id` `salary_deduction_type_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'links to salary_deduction_types.id'");
         
        $this->execute("ALTER TABLE `staff_salary_additions` ADD KEY `salary_deduction_type_id` (`salary_deduction_type_id`)");
    }


    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_salary_transactions`');
        $this->execute('DROP TABLE IF EXISTS `staff_salaries`');
        $this->execute('RENAME TABLE `z_5883_staff_salaries` TO `staff_salaries`');
        $this->execute('DROP TABLE IF EXISTS `staff_salary_additions`');
        $this->execute('RENAME TABLE `z_5883_staff_salary_additions` TO `staff_salary_additions`');
    }
}
