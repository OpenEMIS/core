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
    }

    public function down()
    {
        $this->execute("DELETE FROM import_mapping WHERE model = 'Textbook.Textbooks'");
    }
}
