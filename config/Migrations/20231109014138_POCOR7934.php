<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR7934 extends AbstractMigration
{
    public function up()
    {
        //backup the table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_7934_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_7934_security_functions` 
                        SELECT * FROM `security_functions`');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityFunctionIDs = $securityFunctions->find()
                ->where([$securityFunctions->aliasField('_add') => 'Demographic.add'])
                ->extract('id')
                ->toArray(); //get all security functions that can add demographic
        if(empty($securityFunctionIDs)){
            $securityFunctionIDs = -1;
        }
        $securityFunctionIDs = implode(',', $securityFunctionIDs);
        $this->execute("UPDATE `security_functions` SET `_delete` = 'Demographic.remove'
                        WHERE `id` IN ($securityFunctionIDs)");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_7934_security_functions` TO `security_functions`');
    }
}
