<?php
use Migrations\AbstractMigration;

class POCOR5175b extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5175b_import_mapping` LIKE `import_mapping`');
		$this->execute('INSERT INTO `z_5175b_import_mapping` SELECT * FROM `import_mapping`');
		$this->execute('UPDATE `import_mapping` SET `description` = "ID", `foreign_key` = 1, `lookup_plugin` = "User", `lookup_model` = "Users", `lookup_column` = "Id" WHERE `model` = "Student.Extracurriculars" and `column_name`="security_user_id"');
		
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5175b_import_mapping` TO `import_mapping`');
    }
}
