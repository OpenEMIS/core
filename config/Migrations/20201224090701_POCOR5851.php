<?php
use Migrations\AbstractMigration;

class POCOR5851 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_5851_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5851_security_functions` SELECT * FROM `security_functions`');
        // End

        $stmt2 = $this->query("SELECT * FROM security_functions WHERE controller ='Institutions' AND module ='Institutions' AND category='Students' AND parent_id=8 ORDER BY `order` ASC LIMIT 1");
        $rows2 = $stmt2->fetchAll();
        $ordervalue = $rows2[0]['order'];

        

        $stmt3 = $this->query("SELECT * FROM security_roles WHERE `name` ='Student'");
        $rows3 = $stmt3->fetchAll();
        $security_roles_id = $rows3[0]['id'];
        
        // security_functions

        $this->insert('security_functions', [
            'name' => 'Student Attendance Archive',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => $security_roles_id,
            '_view' => 'StudentArchive.add',
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            '_execute' => null,
            'order' => $ordervalue -2,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->insert('security_functions', [
            'name' => 'Student Assessment Archive',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => $security_roles_id,
            '_view' => 'AssessmentsArchive.index',
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            '_execute' => null,
            'order' => $ordervalue -1,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `zz_5851_security_roles` TO `security_roles`');
    }
}
