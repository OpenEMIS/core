<?php
use Migrations\AbstractMigration;

class POCOR6267 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_6267_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6267_security_functions` SELECT * FROM `security_functions`'); 

        //insert 
        $record = [
            [
                'name' => 'Guardian', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'General', 'parent_id' => -1,'_view' => 'Profiles.index|Profiles.view', '_edit' => 'Profiles.edit', '_add' => 'Profiles.add', '_delete' => 'Profiles.remove', '_execute' => NULL, 'order' => 420, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->insert('security_functions', $record);
        
        $row = $this->fetchRow("SELECT `id` FROM `security_functions` WHERE `controller` = 'Profiles' AND
                `module` = 'Profile'");
        $parentId = $row['id'];
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6267_security_functions` TO `security_functions`'); 
    }
}
