<?php
use Migrations\AbstractMigration;

class POCOR5875 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5875_institution_budgets` LIKE `institution_budgets`');
        $this->execute('INSERT INTO `zz_5875_institution_budgets` SELECT * FROM `institution_budgets`');
        $this->execute('CREATE TABLE `zz_5875_institution_incomes` LIKE `institution_incomes`');
        $this->execute('INSERT INTO `zz_5875_institution_incomes` SELECT * FROM `institution_incomes`');
        $this->execute('CREATE TABLE `zz_5875_institution_expenditures` LIKE `institution_expenditures`');
        $this->execute('INSERT INTO `zz_5875_institution_expenditures` SELECT * FROM `institution_expenditures`');
        // End
        //budget
        $this->execute('ALTER TABLE `institution_budgets` ADD `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `amount`');
        $this->execute('ALTER TABLE `institution_budgets` ADD `institution_id` INT(11) NULL DEFAULT NULL AFTER `id`');
        $this->execute("ALTER TABLE `institution_budgets` CHANGE `attachment` `file_content` LONGBLOB NULL DEFAULT NULL"); 
        $this->execute("ALTER TABLE `institution_budgets` CHANGE `file_name` `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT ''"); 

        //incomes
        $this->execute('ALTER TABLE `institution_incomes` ADD `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `amount`');
        $this->execute("ALTER TABLE `institution_incomes` CHANGE `attachment` `file_content` LONGBLOB NULL DEFAULT NULL"); 
        $this->execute("ALTER TABLE `institution_incomes` CHANGE `file_name` `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT ''"); 

        //expenditures
        $this->execute('ALTER TABLE `institution_expenditures` ADD `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `amount`');
        $this->execute("ALTER TABLE `institution_expenditures` CHANGE `attachment` `file_content` LONGBLOB NULL DEFAULT NULL"); 
        $this->execute("ALTER TABLE `institution_expenditures` CHANGE `file_name` `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT ''"); 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_budgets`');
        $this->execute('RENAME TABLE `zz_5875_institution_budgets` TO `institution_budgets`');
        $this->execute('DROP TABLE IF EXISTS `institution_incomes`');
        $this->execute('RENAME TABLE `zz_5875_institution_incomes` TO `institution_incomes`');
        $this->execute('DROP TABLE IF EXISTS `institution_expenditures`');
        $this->execute('RENAME TABLE `zz_5875_institution_expenditures` TO `institution_expenditures`');
    }
}
