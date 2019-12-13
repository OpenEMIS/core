<?php

use Phinx\Migration\AbstractMigration;

class POCOR4942d extends AbstractMigration
{
    public function up()
    {
       $this->execute('CREATE TABLE `z_4942_security_functions` LIKE `security_functions`');
	   $this->execute('INSERT INTO `z_4942_security_functions` SELECT * FROM `security_functions`');
       $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, 
		`_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, 
		`created_user_id`, `created`) VALUES('Import Staff Qualifications','Staff','Institutions',
		'Staff - Professional','3000',NULL,NULL,NULL,NULL,
		'ImportStaffQualifications.add|ImportStaffQualifications.template|ImportStaffQualifications.results|ImportStaffQualifications.downloadFailed|ImportStaffQualifications.downloadPassed',
		'198','1',NULL,'2','2019-12-13 06:28:21','1','2019-12-13 10:30:18')");
    }

    public function down()
    {
        $this->dropTable('security_functions');
        $this->table('z_4942_security_functions')->rename('security_functions');
    }   
}
