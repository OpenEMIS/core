<?php
use Migrations\AbstractMigration;

class POCOR6428 extends AbstractMigration
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
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6428_security_functions`');
        $this->execute('CREATE TABLE `zz_6428_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6428_security_functions` SELECT * FROM `security_functions`');

        $data = [
            [ 
                'name' => 'Meals Distribution', 
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Meals',
                'parent_id' => 8,
                '_view' => 'Distributions.view|Distributions.index',
                '_add' => 'Distributions.add',
                'order' => 61,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
                  
            ],
            [ 
                'name' => 'Meals Student', 
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Meals',
                'parent_id' => 8,
                '_view' => 'StudentMeals.view|StudentMeals.index',
                'order' => 62,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
                  
            ]
        ];
        
        $this->insert('security_functions', $data);
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6428_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
