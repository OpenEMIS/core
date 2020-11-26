<?php

use Phinx\Migration\AbstractMigration;

class POCOR1161 extends AbstractMigration
{
    public function up()
    {
        // Create tables
        $this->execute("CREATE TABLE `budget_types` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, `order` int(3) NOT NULL, `visible` int(1) NOT NULL DEFAULT '1', `editable` int(1) NOT NULL DEFAULT '1', `default` int(1) NOT NULL DEFAULT '0', `international_code` varchar(50) DEFAULT NULL, `national_code` varchar(50) DEFAULT NULL, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`), KEY `modified_user_id` (`modified_user_id`), KEY `created_user_id` (`created_user_id`) )");
        $this->execute("CREATE TABLE `income_sources` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, `order` int(3) NOT NULL, `visible` int(1) NOT NULL DEFAULT '1', `editable` int(1) NOT NULL DEFAULT '1', `default` int(1) NOT NULL DEFAULT '0', `international_code` varchar(50) DEFAULT NULL, `national_code` varchar(50) DEFAULT NULL, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`), KEY `modified_user_id` (`modified_user_id`), KEY `created_user_id` (`created_user_id`) )");
        $this->execute("CREATE TABLE `income_types` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, `order` int(3) NOT NULL, `visible` int(1) NOT NULL DEFAULT '1', `editable` int(1) NOT NULL DEFAULT '1', `default` int(1) NOT NULL DEFAULT '0', `international_code` varchar(50) DEFAULT NULL, `national_code` varchar(50) DEFAULT NULL, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`), KEY `modified_user_id` (`modified_user_id`), KEY `created_user_id` (`created_user_id`) )");
        $this->execute("CREATE TABLE `expenditure_types` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, `order` int(3) NOT NULL, `visible` int(1) NOT NULL DEFAULT '1', `editable` int(1) NOT NULL DEFAULT '1', `default` int(1) NOT NULL DEFAULT '0', `international_code` varchar(50) DEFAULT NULL, `national_code` varchar(50) DEFAULT NULL, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`), KEY `modified_user_id` (`modified_user_id`), KEY `created_user_id` (`created_user_id`) )");
        $this->execute("CREATE TABLE `institution_budgets` ( `id` int(11) NOT NULL AUTO_INCREMENT, `academic_period_id` int(11) NOT NULL, `budget_type_id` int(11) NOT NULL, `amount` int(11) NOT NULL, `attachment` longblob, `description` text, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`) )");
        $this->execute("CREATE TABLE `institution_incomes` ( `id` int(11) NOT NULL AUTO_INCREMENT, `academic_period_id` int(11) NOT NULL, `date` date NOT NULL, `income_source_id` int(11) NOT NULL, `income_type_id` int(11) NOT NULL, `amount` int(11) DEFAULT NULL, `attachment` longblob, `description` text, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`) )");
        $this->execute("CREATE TABLE `institution_expenditures` ( `id` int(11) NOT NULL AUTO_INCREMENT, `academic_period_id` int(11) NOT NULL, `date` date NOT NULL, `budget_type_id` int(11) NOT NULL, `expenditure_type_id` int(11) NOT NULL, `amount` int(11) DEFAULT NULL, `attachment` longblob, `description` text, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`) )");
    }

    public function down()
    {
        // For tables
        $this->execute('DROP TABLE IF EXISTS `budget_types`');
        $this->execute('DROP TABLE IF EXISTS `income_sources`');
        $this->execute('DROP TABLE IF EXISTS `income_types`');
        $this->execute('DROP TABLE IF EXISTS `expenditure_types`');
        $this->execute('DROP TABLE IF EXISTS `institution_budgets`');
        $this->execute('DROP TABLE IF EXISTS `institution_incomes`');
        $this->execute('DROP TABLE IF EXISTS `institution_expenditures`');
    }
}
