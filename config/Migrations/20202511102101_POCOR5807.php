<?php

use Migrations\AbstractMigration;

class POCOR5807 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5807_import_mapping` LIKE `import_mapping`');
		$this->execute('INSERT INTO `z_5807_import_mapping` SELECT * FROM `import_mapping`');	

        $this->execute("UPDATE `import_mapping` SET `lookup_plugin` = 'User', `lookup_model` = 'Users', `lookup_column` = 'openemis_no', `description` = 'OPENEMIS NO', `foreign_key` = 1 WHERE `model` = 'Student.Extracurriculars' AND `column_name` = 'openemis_no'"); 
		
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Student.Extracurriculars' AND `column_name` = 'security_user_id' AND `lookup_plugin` = 'User' AND `lookup_model` = 'Users' AND `lookup_column` = 'Id'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5807_import_mapping` TO `import_mapping`');
    }
}
