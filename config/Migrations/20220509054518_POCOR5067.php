<?php
use Migrations\AbstractMigration;
use Cake\Datasource\ConnectionManager;

class POCOR5067 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5067_user_attachments` LIKE `user_attachments`');
        $this->execute('INSERT INTO `zz_5067_user_attachments` SELECT * FROM `user_attachments`');

        
        $this->execute("CREATE TABLE IF NOT EXISTS `institution_attachment_types` (
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

        $this->execute("CREATE TABLE IF NOT EXISTS `staff_attachment_types` (
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

        $this->execute("CREATE TABLE IF NOT EXISTS `student_attachment_types` (
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


        $connection = ConnectionManager::get('default');

        $dbConfig = $connection->config();
        $dbname = $dbConfig['database']; 
        $CheckAreaIDExist = "SELECT COUNT(*)
         FROM information_schema.columns 
         WHERE table_name   = 'institution_attachments'
         AND table_schema = '$dbname'
         AND column_name  = 'institution_attachment_type_id'";
         $tableData = $this->fetchAll($CheckAreaIDExist);
         if($tableData[0][0] == 0){
            $this->execute('ALTER TABLE `institution_attachments` ADD `institution_attachment_type_id` INT(11) NULL AFTER `id`');
         }

        $dbConfig = $connection->config();
        $dbname = $dbConfig['database']; 
        $CheckAreaIDExist = "SELECT COUNT(*)
         FROM information_schema.columns 
         WHERE table_name   = 'user_attachments'
         AND table_schema = '$dbname'
         AND column_name  = 'student_attachment_type_id'";
         $tableData = $this->fetchAll($CheckAreaIDExist);
         if($tableData[0][0] == 0){
            $this->execute('ALTER TABLE `user_attachments` ADD `student_attachment_type_id` INT(11) NULL AFTER `id`, ADD `staff_attachment_type_id` INT(11) NULL AFTER `student_attachment_type_id`');
         }
        //END USER ATTACHMENT..

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_attachments`');
        $this->execute('RENAME TABLE `zz_5067_institution_attachments` TO `institution_attachments`');

        $this->execute('DROP TABLE IF EXISTS `user_attachments`');
        $this->execute('RENAME TABLE `zz_5067_user_attachments` TO `user_attachments`');

        $this->execute('DROP TABLE IF EXISTS `staff_attachment_types`');
        $this->execute('RENAME TABLE `zz_5067_staff_attachment_types` TO `staff_attachment_types`');

        $this->execute('DROP TABLE IF EXISTS `student_attachment_types`');
        $this->execute('RENAME TABLE `zz_5067_student_attachment_types` TO `student_attachment_types`');

        $this->execute('DROP TABLE IF EXISTS `institution_attachment_types`');
        $this->execute('RENAME TABLE `zz_5067_institution_attachment_types` TO `institution_attachment_types`');

        //$this->execute('RENAME TABLE `zz_6286_security_functions` TO `security_functions`');
    }
}
