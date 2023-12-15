<?php

use Phinx\Migration\AbstractMigration;

class POCOR7905 extends AbstractMigration

{
    // commit
    public function up()
    {
        // Backup table
//        try {
            $this->execute('CREATE TABLE IF NOT EXISTS `z_7905_security_functions` LIKE `security_functions`');

            $this->execute('INSERT IGNORE INTO `z_7905_security_functions` SELECT * FROM `security_functions`');

            $this->execute("UPDATE security_functions SET `_add` = 'ScholarshipsDirectory.index|ScholarshipApplications.add' 
                                WHERE `name` = 'Scholarship' 
                                  AND `controller` = 'Profiles' 
                                  AND `module` = 'Personal' 
                                  AND `category` = 'Scholarships' ");

//        } catch (\Exception $e) {
//
//        }
    }

// rollback
    public
    function down()
    {
//        try {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_7905_security_functions` TO `security_functions`');
//        } catch (\Exception $e) {
//
//        }
    }
}
