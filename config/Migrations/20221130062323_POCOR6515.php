<?php
use Migrations\AbstractMigration;

class POCOR6515 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6515_security_functions`');
        $this->execute('CREATE TABLE `zz_6515_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6515_security_functions` SELECT * FROM `security_functions`');

        //Roles Profiles

        $data = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` WHERE `name` = 'Report Cards' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Students - Academic' ");

        $this->insert('security_functions', [
            'name' => 'Report Cards (Excel)',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Students - Academic',
            'parent_id' => $data[1],
            '_view' => 'StudentReportCard.index|StudentReportCard.view',
            '_execute' => 'StudentReportCard.download',            
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->execute("UPDATE `security_functions` SET
        `_execute` = 'StudentReportCards.download',
        `name` = 'Report Cards (PDF)'
        WHERE `name` = 'Report Cards' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Students - Academic'");

        //Roles Institutions

        $institutionsData = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` WHERE `name` = 'Report Cards' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Academic' ");

        $this->insert('security_functions', [
            'name' => 'Report Cards (Excel)',
            'controller' => 'Students',
            'module' => 'Institutions',
            'category' => 'Students - Academic',
            'parent_id' => $institutionsData[1],
            '_view' => 'ReportCard.index|ReportCard.view',
            '_execute' => 'ReportCard.download',            
            'order' => $institutionsData[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);


        $this->execute("UPDATE `security_functions` SET
        `name` = 'Report Cards (PDF)'
        WHERE `name` = 'Report Cards' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Academic'");

        //Roles Guardians

        $GuardiansData = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` WHERE `name` = 'Report Cards' AND `controller` = 'GuardianNavs' AND `module` = 'Guardian' AND `category` = 'Students - Academic' ");

        $this->insert('security_functions', [
            'name' => 'Report Cards (Excel)',
            'controller' => 'GuardianNavs',
            'module' => 'Guardian',
            'category' => 'Students - Academic',
            'parent_id' => $GuardiansData[1],
            '_view' => 'ReportCard.index|ReportCard.view',
            '_execute' => 'ReportCard.download',            
            'order' => $GuardiansData[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);


        $this->execute("UPDATE `security_functions` SET
        `name` = 'Report Cards (PDF)'
        WHERE `name` = 'Report Cards' AND `controller` = 'GuardianNavs' AND `module` = 'Guardian' AND `category` = 'Students - Academic'");

        //Roles Directories
        $DirectoriesData = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` WHERE `name` = 'Report Cards' AND `controller` = 'Directories' AND `module` = 'Directory' AND `category` = 'Students - Academic' ");

        $this->insert('security_functions', [
            'name' => 'Report Cards (Excel)',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Students - Academic',
            'parent_id' => $DirectoriesData[1],
            '_view' => 'StudentReportCards.index|StudentReportCards.view',
            '_execute' => 'StudentReportCard.download',            
            'order' => $DirectoriesData[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);


        $this->execute("UPDATE `security_functions` SET
        `name` = 'Report Cards (PDF)'
        WHERE `name` = 'Report Cards' AND `controller` = 'Directories' AND `module` = 'Directory' AND `category` = 'Students - Academic'");
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6515_security_functions` TO `security_functions`');
    }

}
