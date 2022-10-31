<?php
use Migrations\AbstractMigration;

class POCOR5899 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5899_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5899_security_functions` SELECT * FROM `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 176');

         $data = [
            [ 
                'name' => 'Meal', 
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Meal',
                'parent_id' => 2000,
                '_view' => 'Meals.index|Meals.view',
                'order' => 177,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
                  
            ]
        ];
        
        $this->insert('security_functions', $data);
    }

    // rollback
    public function down()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 176');
        $this->execute('DELETE FROM security_functions WHERE name = Meal AND controller = Students');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5899_security_functions` TO `security_functions`');
    }
}
