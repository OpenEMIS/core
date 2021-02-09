<?php
use Migrations\AbstractMigration;

class POCOR5901 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5901_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5901_import_mapping` SELECT * FROM `import_mapping`');

         //import_mapping
        $data = [
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'OpenEMIS_ID',
                'description' => NULL,
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
        ];

        $this->insert('import_mapping', $data); 

        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionMealStudents' AND `column_name` = 'student_id' AND `lookup_plugin` = 'Security' AND `lookup_model` = 'Users' AND `lookup_column` = 'openemis_no'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5901_import_mapping` TO `import_mapping`');
    }}
