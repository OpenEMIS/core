<?php

use Phinx\Migration\AbstractMigration;

class POCOR3654 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        //import_mapping
        $data = [
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'academic_period_id',
                'description' => 'Code',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'AcademicPeriod',
                'lookup_model' => 'AcademicPeriods',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'education_grade_id',
                'description' => 'Code',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Education',
                'lookup_model' => 'EducationGrades',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'education_subject_id',
                'description' => 'Code',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Education',
                'lookup_model' => 'EducationSubjects',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'code',
                'description' => NULL,
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'title',
                'description' => NULL,
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'author',
                'description' => NULL,
                'order' => 6,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'publisher',
                'description' => NULL,
                'order' => 7,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'year_published',
                'description' => '( YYYY )',
                'order' => 8,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'ISBN',
                'description' => NULL,
                'order' => 9,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Textbook.Textbooks',
                'column_name' => 'expiry_date',
                'description' => '( DD/MM/YYYY )',
                'order' => 10,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ]
        ];

        $this->insert('import_mapping', $data);
        //import_mapping

         // security_functions
        $this->execute("
        UPDATE `security_functions` 
        SET 
        `_execute` = 'ImportInstitutionTextbooks.add|ImportInstitutionTextbooks.template|ImportInstitutionTextbooks.results|ImportInstitutionTextbooks.downloadFailed|ImportInstitutionTextbooks.downloadPassed',
        `name` = 'Import Institution Textbooks'
        WHERE `name` = 'Import Textbooks'
        AND `id` = '1052'");

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` >= 210');

        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('5079', 'Import Textbooks', 'Textbooks', 'Administration', 'Textbooks', '5000', NULL, NULL, NULL, NULL, 'ImportTextbooks.add|ImportTextbooks.template|ImportTextbooks.results|ImportTextbooks.downloadFailed|ImportTextbooks.downloadPassed', '210', '1', NULL, NULL, NULL, '1', '2017-09-05 00:00:00')");
    }

    public function down()
    {
        $this->execute("DELETE FROM import_mapping WHERE model = 'Textbook.Textbooks'");

        // security_functions
        $this->execute("
        UPDATE `security_functions` 
        SET 
        `_execute` = 'ImportTextbooks.add|ImportTextbooks.template|ImportTextbooks.results|ImportTextbooks.downloadFailed|ImportTextbooks.downloadPassed',
        `name` = 'Import Textbooks'
        WHERE `name` = 'Import Institution Textbooks'
        AND `id` = '1052'");

        $this->execute("DELETE FROM `security_functions` WHERE `id` = 5079");

        $this->execute("UPDATE security_functions SET `order` = `order` - 1 WHERE `order` >= 210");
    }
}
