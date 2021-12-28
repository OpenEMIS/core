<?php
use Migrations\AbstractMigration;

class POCOR6310 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6310_security_functions`');

        // backup table
        $this->execute('CREATE TABLE `zz_6310_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6310_security_functions` SELECT * FROM `security_functions`');

        // Remove Achievements from Institution
        $this->execute("DELETE FROM `security_functions` WHERE `name` = 'Achievements' AND `controller` = 'Staff' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");

        // Change the name and ordering in permissions page (institutions tab)
        $this->execute("UPDATE `security_functions` SET `order` = '218' WHERE `name` = 'Needs' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        $this->execute("UPDATE `security_functions` SET `order` = '219' WHERE `name` = 'Applications' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        $this->execute("UPDATE `security_functions` SET `order` = '220' WHERE `name` = 'Results' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        $this->execute("UPDATE `security_functions` SET `order` = '221' WHERE `name` = 'Courses' AND `controller` = 'Staff' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");

        // Add Applications permission to Personal tab
        $this->insert('security_functions', [
            'name' => 'Applications',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Staff - Training',
            'parent_id' => 9030,
            '_view' => 'StaffTrainingApplications.index|StaffTrainingApplications.view',
            '_add' => 'StaffTrainingApplications.add|CourseCatalogue.index|CourseCatalogue.view',
            'order' => 491,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        /** Change permission ordering for personal tab */
        // Update Needs of Personal module
        $this->execute("UPDATE `security_functions` SET `order` = '490', `name` = 'Needs' WHERE (`name` = 'Training Needs' OR `name` = 'Needs') AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Staff - Training'");

        // Update Application of Personal module
        $this->execute("UPDATE `security_functions` SET `order` = '491'WHERE `name` = 'Applications' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Staff - Training'");

        // Update Results of Personal module
        $this->execute("UPDATE `security_functions` SET `order` = '492', `name` = 'Results' WHERE (`name` = 'Training Results' OR `name` = 'Results') AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Staff - Training'");

        // Update Courses of Personal module
        $this->execute("UPDATE `security_functions` SET `order` = '493', `_add` = 'Courses.add' WHERE `name` = 'Courses' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Staff - Training'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6310_security_functions` TO `security_functions`');
    }
}
