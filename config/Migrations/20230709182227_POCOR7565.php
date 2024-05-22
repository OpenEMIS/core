<?php
use Migrations\AbstractMigration;

class POCOR7565 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7565_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7565_security_functions` SELECT * FROM `security_functions`');
        

         $data = [
            [ 
                'name' => 'Profiles', 
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Profiles',
                'parent_id' => 2000,
                '_view' => 'Profiles.index|Profiles.view',
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
       
        $this->execute('DELETE FROM security_functions WHERE name = Profiles AND controller = Students');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7565_security_functions` TO `security_functions`');
    }
}
