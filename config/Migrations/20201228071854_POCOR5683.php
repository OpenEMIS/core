<?php
use Migrations\AbstractMigration;

class POCOR5683 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5683_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5683_security_functions` SELECT * FROM `security_functions`');

         $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 47');
        //insert record
        $records = [
            [ 
                'name' => 'Institution Status', 
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'General',
                'parent_id' => 8,
                '_edit' => 'InstitutionStatus.edit',
                'order' => 48,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('security_functions', $records);
    }


    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_5683_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 47');
    }
}
