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
        $this->execute('CREATE TABLE `zz_5851_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT INTO `zz_5851_security_role_functions` SELECT * FROM `security_role_functions`');
        // End

        $stmt1 = $this->query("SELECT * FROM security_roles WHERE `name` ='Student'");
        $rows1 = $stmt1->fetchAll();
        $security_roles_id = $rows1[0]['id'];

        $stmt2 = $this->query("SELECT * FROM security_functions WHERE controller ='Institutions' AND module ='Institutions' AND category='Students' AND parent_id=$security_roles_id ORDER BY `order` ASC LIMIT 1");
        $rows2 = $stmt2->fetchAll();
        $ordervalue = $rows2[0]['order'];

        $stmt3 = $this->query("SELECT * FROM security_functions WHERE controller ='Institutions' AND module ='Institutions' AND category='Students' AND `name`='Student Attendance Archive' ORDER BY `order` ASC LIMIT 1");
        $rows3 = $stmt3->fetchAll();

        
        // security_functions
        if(empty($rows3)){
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
        }

        $stmt4 = $this->query("SELECT * FROM security_functions WHERE controller ='Institutions' AND module ='Institutions' AND category='Students' AND `name`='Student Assessment Archive' ORDER BY `order` ASC LIMIT 1");
        $rows4 = $stmt4->fetchAll();
        if(empty($rows4)){
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
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5851_security_functions` TO `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `security_role_functions`');
        $this->execute('RENAME TABLE `zz_5851_security_role_functions` TO `security_role_functions`');
    }
}
