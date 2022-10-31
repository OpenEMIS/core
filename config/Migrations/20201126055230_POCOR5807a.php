<?php

use Migrations\AbstractMigration;

class POCOR5807a extends AbstractMigration
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
        
        $this->execute("UPDATE `import_mapping` SET `description` = 'OpenEMIS ID' WHERE `model` = 'Student.Extracurriculars' AND `column_name` = 'openemis_no' AND `lookup_plugin` = 'User' AND `lookup_model` = 'Users' AND `lookup_column` = 'openemis_no'"); 
		
    }

    // rollback
    public function down()
    {
        
    }
}
