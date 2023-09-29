<?php
use Migrations\AbstractMigration;

class POCOR6395 extends AbstractMigration
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
        //creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6395_security_functions`');
        $this->execute('CREATE TABLE `zz_6395_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6395_security_functions` SELECT * FROM `security_functions`');

        //getting parent_id value
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Personal' AND `name` = 'Overview'");
        $parentId = $row['id'];
        //getting max order value
        $data = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");

        //inserting record
        $this->insert('security_functions', [
            'name' => 'User Profile Completeness',
            'controller' => 'Dashboard',
            'module' => 'Personal',
            'category' => 'General',
            'parent_id' => $parentId,
            '_view' => 'Dashboard.index',
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }
    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6395_security_functions` TO `security_functions`');
    }
}
