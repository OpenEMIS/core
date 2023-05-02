<?php
use Migrations\AbstractMigration;

class POCOR7376 extends AbstractMigration
{
    /**
    
 * POCOR-7376
 * creating table industries and adding industries column in POCOR-7376
**/
    
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `industries` (
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

        //Backup Table
        $this->execute('CREATE TABLE `zz_7376_user_employments` LIKE `user_employments`');
        $this->execute('INSERT INTO `zz_7376_user_employments` SELECT * FROM `user_employments`');
        $this->execute('ALTER TABLE `user_employments` ADD COLUMN Industries INT(11) NOT NULL');
       
          
          
        $this->execute('ALTER TABLE `user_employments`
        ADD FOREIGN KEY (`industries`) REFERENCES `industries` (`id`)');
    
   }

   public function down()
    {
        //Backup Table
        $this->execute('DROP TABLE IF EXISTS `user_employments`');
        $this->execute('RENAME TABLE `zz_7376_user_employments` TO `user_employments`');
       
        $this->execute('DROP TABLE IF EXISTS `industries`');
        
    }

}

