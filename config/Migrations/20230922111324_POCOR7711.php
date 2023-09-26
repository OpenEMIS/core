<?php
use Migrations\AbstractMigration;

class POCOR7711 extends AbstractMigration
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
        //Backup import_mapping table
        $this->execute('CREATE TABLE `zz_7711_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_7711_import_mapping` SELECT * FROM `import_mapping`');
        //Update import_mapping
        $this->execute("INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES (NULL, 'Institution.Staff', 'staff_position_grade_id', 'id', '8', '0', '2', 'Institution', 'StaffPositionGrades', 'id');");
        
    }
    public function down()
    {
        // Field Options
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_7711_import_mapping` TO `import_mapping`');
    }
}
