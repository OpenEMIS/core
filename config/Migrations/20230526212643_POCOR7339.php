<?php

use Cake\Database\Schema\Collection;

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class POCOR7339 extends AbstractMigration
{

    public function up()
    {
        if (!$this->hasArchiveTable()) {
            $this->addArchiveTable();
        }

        if ($this->hasArchiveTable()) {
            $this->moveArchiveTableToZ();
            $this->addArchiveTable();
            $this->copyArchiveTableFromZ();
        }
    }

    public function hasArchiveTable()
    {
        $targetTableName = 'assessment_item_results_archived';
        $connection = ConnectionManager::get('default');
        $schemaCollection = new Collection($connection);
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);

        if ($tableExists) {
            return true;
        }
        return false;
    }

    public function addArchiveTable()
    {
        $this->table('assessment_item_results_archived')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('assessment_id', 'integer', [
                'comment' => 'links to assessments.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('assessment_period_id', 'integer', [
                'comment' => 'links to assessment_periods.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('institution_classes_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'assessment_id', 'education_subject_id', 'education_grade_id', 'academic_period_id', 'assessment_period_id', 'institution_classes_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('marks', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('assessment_grading_option_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_id',
                    'assessment_id',
                    'education_subject_id',
                    'education_grade_id',
                    'academic_period_id',
                    'assessment_period_id',
                    'institution_classes_id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'assessment_grading_option_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'assessment_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'assessment_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'modified_user_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->addIndex(
                [
                    'id',
                ]
            )
            ->create();

    }

    public function moveArchiveTableToZ()
    {
        $this->execute('CREATE TABLE `z_7339_assessment_item_results_archived` LIKE `assessment_item_results_archived`');
        $this->execute('INSERT INTO `z_7339_assessment_item_results_archived` SELECT * FROM `assessment_item_results_archived`');
        $this->execute('DROP TABLE `assessment_item_results_archived`');
    }

    public function copyArchiveTableFromZ()
    {
        $this->execute('INSERT INTO `assessment_item_results_archived` SELECT * FROM `z_7339_assessment_item_results_archived`');
    }

}
