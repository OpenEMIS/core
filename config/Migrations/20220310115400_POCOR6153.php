<?php

use Phinx\Migration\AbstractMigration;

/**
 * POCOR-6153
 * create mgration for excel file 
 */
class POCOR6153 extends AbstractMigration
{
    public function up() {
        
        $this->execute('CREATE TABLE `zz_6153_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6153_security_functions` SELECT * FROM `security_functions`');
        
        $this->execute("UPDATE `security_functions` SET `_execute` = 'Distributions.excel' WHERE `category`='Meals' AND `name` = 'Meals Distribution' AND controller='Institutions' AND module = 'Institutions'");

    }
    
    public function down() {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6153_security_functions` TO `security_functions`');
    }
    
}


