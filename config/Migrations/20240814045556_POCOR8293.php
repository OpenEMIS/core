<?php
declare(strict_types=1);
use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class POCOR8293 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `zz_8293_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_8293_security_functions` SELECT * FROM `security_functions`');
        $SecurityFunctionsTable = TableRegistry::get('Security.SecurityFunctions');
        $securityFunctions = $SecurityFunctionsTable
                          ->find()
                          ->where(['controller' => 'Students', 'module' => 'Institutions', 'category' => 'Students - Health'])
                          ->all();
        foreach ($securityFunctions as $data) {
            $this->execute("
                INSERT INTO `security_functions` 
                (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
                VALUES (
                    '" . $data->name . "', 
                    'GuardianNavs', 
                    'Guardian', 
                    '" . $data->category . "', 
                    '" . $data->parent_id . "', 
                    '" . $data->_view . "', 
                    '" . $data->_edit . "', 
                    '" . $data->_add . "', 
                    '" . $data->_delete . "', 
                    '" . $data->_execute . "', 
                    '" . $data->order . "', 
                    '" . $data->visible . "', 
                    '" . $data->description . "', 
                    '', 
                    '', 
                    '1', 
                    NOW()
                )
            ");
        }
        
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_8293_security_functions` TO `security_functions`');
    }
}
