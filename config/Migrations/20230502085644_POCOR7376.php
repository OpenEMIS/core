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
        
        $data=[
                 [
                    'id'=>1,
                    'name' =>'Education',
                    'order' =>1,
                    'visible' => 1,
                    'editable' => 1,
                    'default' => 1,
                    'international_code' => NULL,
                    'national_code'  =>NULL,
                    'modified_user_id' => NULL,
                    'modified' =>NULL,
                    'created_user_id' =>1,
                    'created'  =>date('Y-m-d H:i:s')
                 ],
                 [
                    'id'=>2,
                    'name' =>'Information Technology',
                    'order' =>2,
                    'visible' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'international_code' => NULL,
                    'national_code'  =>NULL,
                    'modified_user_id' => NULL,
                    'modified' =>NULL,
                    'created_user_id' =>1,
                    'created'  =>date('Y-m-d H:i:s')
                 ]
             ];
        
        $this->insert('industries',$data);

        //Backup Table
        $this->execute('CREATE TABLE `zz_7376_user_employments` LIKE `user_employments`');
        $this->execute('INSERT INTO `zz_7376_user_employments` SELECT * FROM `user_employments`');
        $this->execute('ALTER TABLE `user_employments` ADD COLUMN industry_id INT(11) NOT NULL');
       

         
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('ALTER TABLE `user_employments` ADD FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`)');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        //for field option
        $this->execute('CREATE TABLE `zz_7376_field_options` LIKE `field_options`');
        $this->execute('INSERT INTO `zz_7376_field_options` SELECT * FROM `field_options`');
        $this->execute("INSERT INTO `field_options` (`id`, `name`, `category`, `table_name`, `order`, `modified_by`, `modified`, `created_by`, `created`) VALUES
        (135, 'Industries', 'Others', 'industries', 134, NULL, NULL, 1, '2023-05-30 12:00:00')");
       

        

    
   }

   public function down()
    {
       //Backup Table
       $this->execute('DROP TABLE IF EXISTS `user_employments`');
       $this->execute('RENAME TABLE `zz_7376_user_employments` TO `user_employments`');
       $this->execute('DROP TABLE IF EXISTS `field_options`');
       $this->execute('RENAME TABLE `zz_7376_field_options` TO `field_options`');
       $this->execute('DROP TABLE IF EXISTS `industries`');
        
    }

}

