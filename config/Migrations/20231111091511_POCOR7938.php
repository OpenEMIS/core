<?php

use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR7938 extends AbstractMigration
{
    public function up()
    {
        //backup the table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_7938_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_7938_security_functions` 
                        SELECT * FROM `security_functions`');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityFunctionIDs = $securityFunctions->find()
            ->where([$securityFunctions->aliasField('name') => 'Associations'])
            ->extract('id')
            ->toArray(); //get all security functions that have Associations to change the name
        if(empty($securityFunctionIDs)){
            $securityFunctionIDs = -1;
        }
        $securityFunctionIDs = implode(',', $securityFunctionIDs);
        $this->execute("UPDATE `security_functions` SET `name` = 'Houses'
                        WHERE `id` IN ($securityFunctionIDs)");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_7938_security_functions` TO `security_functions`');
    }

}
