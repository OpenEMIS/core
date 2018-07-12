<?php

use Phinx\Migration\AbstractMigration;

class POCOR4722 extends AbstractMigration
{
    public function up(){
        $this->execute('RENAME TABLE `institutions` TO `z_4722_institutions`');    
        $this->execute('CREATE TABLE `institutions` LIKE `z_4722_institutions`');
        $this->execute('ALTER TABLE `institutions` MODIFY COLUMN `longitude` varchar(25) NULL');
        $this->execute('ALTER TABLE `institutions` MODIFY COLUMN `latitude` varchar(25) NULL');
        $this->execute('INSERT INTO `institutions` SELECT * FROM `z_4722_institutions`');                
    }



    public function down(){
        $this->execute('DROP TABLE `institutions`');        
        $this->execute('RENAME TABLE `z_4722_institutions` TO `institutions`');  
    }
}
