<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR5033p extends AbstractMigration
{
    public function up()
    {
    	// backup the table
        $this->execute('CREATE TABLE `z_5033_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5033_security_functions` SELECT * FROM `security_functions`');

        $this->execute('CREATE TABLE `z_5033_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT INTO `z_5033_security_role_functions` SELECT * FROM `security_role_functions`');

        //Insert data into security functions
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, 
		`_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
		VALUES('9012','Import Competency Templates','Competencies','Administration','Competencies','5000',NULL,NULL,NULL,NULL,'ImportTextbooks.add|ImportTextbooks.template|ImportTextbooks.results|ImportTextbooks.downloadFailed|ImportTextbooks.downloadPassed','9012','1',NULL,'2','2018-03-29 23:55:35','1',NOW())");

		$this->execute("INSERT INTO `security_role_functions` (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) values('0','0','0','0','0','2','9012',NULL,NULL,'2',NOW())");

        $this->execute("INSERT INTO `security_role_functions` (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) values('1','1','1','1','0','10','9012',NULL,NULL,'2',NOW())");
	}

	public function down() {
		//Restore backups
        $this->execute('DROP TABLE security_functions');
        $this->table('z_5033_security_functions')->rename('security_functions');
        $this->execute('DROP TABLE security_role_functions');
        $this->table('z_5033_security_role_functions')->rename('security_role_functions');
    }
}