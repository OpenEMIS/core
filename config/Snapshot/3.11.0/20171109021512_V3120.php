<?php
use Migrations\AbstractMigration;

class V3120 extends AbstractMigration
{

    public $autoId = false;

    public function up()
    {

        $this->table('absence_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('academic_period_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('level', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'editable',
                ]
            )
            ->create();

        $this->table('academic_periods')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('school_days', 'integer', [
                'default' => '0',
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('current', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('academic_period_level_id', 'integer', [
                'comment' => 'links to academic_period_levels.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'current',
                ]
            )
            ->addIndex(
                [
                    'visible',
                ]
            )
            ->addIndex(
                [
                    'editable',
                ]
            )
            ->addIndex(
                [
                    'parent_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_level_id',
                ]
            )
            ->create();

        $this->table('alert_logs')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('feature', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('method', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('destination', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'comment' => '-1 -> Failed, 0 -> Pending, 1 -> Success',
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('subject', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('checksum', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('processed_date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->addIndex(
                [
                    'method',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('alert_rules')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('feature', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('threshold', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('enabled', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('method', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('subject', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('alerts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('process_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('process_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('alerts_roles')
            ->addColumn('alert_rule_id', 'integer', [
                'comment' => 'links to alert_rules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['alert_rule_id', 'security_role_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'alert_rule_id',
                ]
            )
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->addIndex(
                [
                    'alert_rule_id',
                ]
            )
            ->create();

        $this->table('api_authorizations')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => false,
            ])
            ->addColumn('security_token', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_token',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('api_credentials')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('client_id', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('public_key', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('scope', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('area_administrative_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('level', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('area_administrative_id', 'integer', [
                'comment' => 'links to area_administratives.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'area_administrative_id',
                ]
            )
            ->create();

        $this->table('area_administratives')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_main_country', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('area_administrative_level_id', 'integer', [
                'comment' => 'links to area_administrative_levels.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'area_administrative_level_id',
                ]
            )
            ->addIndex(
                [
                    'parent_id',
                ]
            )
            ->create();

        $this->table('area_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('level', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('areas')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('area_level_id', 'integer', [
                'comment' => 'links to area_levels.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'area_level_id',
                ]
            )
            ->addIndex(
                [
                    'parent_id',
                ]
            )
            ->create();

        $this->table('assessment_grading_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('min', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('max', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('assessment_grading_type_id', 'integer', [
                'comment' => 'links to assessment_grading_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'assessment_grading_type_id',
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
            ->create();

        $this->table('assessment_grading_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => false,
            ])
            ->addColumn('pass_mark', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('max', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('result_type', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
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
            ->create();

        $this->table('assessment_item_results')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assessment_id', 'integer', [
                'comment' => 'links to assessments.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assessment_period_id', 'integer', [
                'comment' => 'links to assessment_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'assessment_id', 'education_subject_id', 'education_grade_id', 'academic_period_id', 'assessment_period_id'])
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
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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

        $this->table('assessment_items')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('weight', 'decimal', [
                'default' => '0.00',
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('classification', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('assessment_id', 'integer', [
                'comment' => 'links to assessments.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
                    'modified_user_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('assessment_items_grading_types')
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assessment_grading_type_id', 'integer', [
                'comment' => 'links to assessment_grading_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assessment_id', 'integer', [
                'comment' => 'links to assessments.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assessment_period_id', 'integer', [
                'comment' => 'links to assessment_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['education_subject_id', 'assessment_grading_type_id', 'assessment_id', 'assessment_period_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
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
                    'assessment_grading_type_id',
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
                    'assessment_period_id',
                ]
            )
            ->create();

        $this->table('assessment_periods')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_enabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_disabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('weight', 'decimal', [
                'default' => '0.00',
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('academic_term', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('assessment_id', 'integer', [
                'comment' => 'links to assessments.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'assessment_id',
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
            ->create();

        $this->table('assessments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('excel_template_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('excel_template', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('type', 'integer', [
                'comment' => '1 -> Non-official, 2 -> Official',
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
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
            ->create();

        $this->table('authentication_types')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->create();

        $this->table('bank_branches')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('bank_id', 'integer', [
                'comment' => 'links to banks.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'bank_id',
                ]
            )
            ->create();

        $this->table('banks')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('behaviour_classifications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('building_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('infrastructure_custom_field_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_building_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'infrastructure_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'institution_building_id',
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
            ->create();

        $this->table('building_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('bus_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('comment_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('competencies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 55,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('min', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('max', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('competency_criterias')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_item_id', 'integer', [
                'comment' => 'links to competency_items.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'academic_period_id', 'competency_item_id', 'competency_template_id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => false,
            ])
            ->addColumn('competency_grading_type_id', 'integer', [
                'comment' => 'links to competency_grading_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_item_id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
                ]
            )
            ->addIndex(
                [
                    'competency_grading_type_id',
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
            ->create();

        $this->table('competency_grading_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('competency_grading_type_id', 'integer', [
                'comment' => 'links to competency_grading_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'competency_grading_type_id',
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
            ->create();

        $this->table('competency_grading_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
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
            ->create();

        $this->table('competency_items')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'academic_period_id', 'competency_template_id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
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
            ->create();

        $this->table('competency_items_periods')
            ->addColumn('competency_item_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_period_id', 'integer', [
                'comment' => 'links to competency_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['competency_item_id', 'competency_period_id', 'academic_period_id', 'competency_template_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'competency_item_id',
                ]
            )
            ->addIndex(
                [
                    'competency_period_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
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
            ->create();

        $this->table('competency_periods')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'academic_period_id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_enabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_disabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
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
            ->create();

        $this->table('competency_sets')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('competency_sets_competencies')
            ->addColumn('competency_id', 'integer', [
                'comment' => 'links to competencies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_set_id', 'integer', [
                'comment' => 'links to competency_sets.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['competency_id', 'competency_set_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'competency_id',
                ]
            )
            ->addIndex(
                [
                    'competency_set_id',
                ]
            )
            ->create();

        $this->table('competency_templates')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'academic_period_id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
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
            ->create();

        $this->table('config_attachments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('active', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('config_item_options')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('option_type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('option', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 3,
                'null' => false,
            ])
            ->create();

        $this->table('config_items')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('label', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => false,
            ])
            ->addColumn('default_value', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('option_type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('config_product_lists')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('url', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('contact_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('contact_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('contact_option_id', 'integer', [
                'comment' => 'links to contact_options.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('validation_pattern', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'contact_option_id',
                ]
            )
            ->create();

        $this->table('countries')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('custom_field_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('custom_field_id', 'integer', [
                'comment' => 'links to custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_field_id',
                ]
            )
            ->create();

        $this->table('custom_field_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('format', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->create();

        $this->table('custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('custom_field_id', 'integer', [
                'comment' => 'links to custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('custom_record_id', 'integer', [
                'comment' => 'links to custom_records.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'custom_record_id',
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
            ->create();

        $this->table('custom_fields')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('custom_forms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('custom_module_id', 'integer', [
                'comment' => 'links to custom_modules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_module_id',
                ]
            )
            ->create();

        $this->table('custom_forms_fields')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('custom_form_id', 'integer', [
                'comment' => 'links to custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('custom_field_id', 'integer', [
                'comment' => 'links to custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'custom_form_id',
                ]
            )
            ->create();

        $this->table('custom_forms_filters')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('custom_form_id', 'integer', [
                'comment' => 'links to custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('custom_filter_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_filter_id',
                ]
            )
            ->addIndex(
                [
                    'custom_form_id',
                ]
            )
            ->create();

        $this->table('custom_modules')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'parent_id',
                ]
            )
            ->create();

        $this->table('custom_records')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('custom_form_id', 'integer', [
                'comment' => 'links to custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_form_id',
                ]
            )
            ->create();

        $this->table('custom_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('custom_field_id', 'integer', [
                'comment' => 'links to custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('custom_table_column_id', 'integer', [
                'comment' => 'links to custom_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('custom_table_row_id', 'integer', [
                'comment' => 'links to custom_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('custom_record_id', 'integer', [
                'comment' => 'links to custom_records.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'custom_record_id',
                ]
            )
            ->addIndex(
                [
                    'custom_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'custom_table_row_id',
                ]
            )
            ->create();

        $this->table('custom_table_columns')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('custom_field_id', 'integer', [
                'comment' => 'links to custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_field_id',
                ]
            )
            ->create();

        $this->table('custom_table_rows')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('custom_field_id', 'integer', [
                'comment' => 'links to custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_field_id',
                ]
            )
            ->create();

        $this->table('deleted_records')
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('deleted_date', 'integer', [
                'default' => null,
                'limit' => 8,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'deleted_date'])
            ->addColumn('reference_table', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('reference_key', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('data', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'reference_table',
                ]
            )
            ->addIndex(
                [
                    'deleted_date',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('education_certifications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('education_cycles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('admission_age', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('education_level_id', 'integer', [
                'comment' => 'links to education_levels.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_level_id',
                ]
            )
            ->create();

        $this->table('education_field_of_studies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('education_programme_orientation_id', 'integer', [
                'comment' => 'links to education_programme_orientations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_programme_orientation_id',
                ]
            )
            ->create();

        $this->table('education_grades')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('admission_age', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('education_stage_id', 'integer', [
                'comment' => 'links to education_stages.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_programme_id', 'integer', [
                'comment' => 'links to education_programmes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_stage_id',
                ]
            )
            ->addIndex(
                [
                    'education_programme_id',
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
            ->create();

        $this->table('education_grades_subjects')
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['education_grade_id', 'education_subject_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('hours_required', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('auto_allocation', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
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
            ->create();

        $this->table('education_level_isced')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('isced_level', 'integer', [
                'default' => null,
                'limit' => 2,
                'null' => false,
            ])
            ->addColumn('isced_version', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('education_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('education_system_id', 'integer', [
                'comment' => 'links to education_systems.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_level_isced_id', 'integer', [
                'comment' => 'links to education_level_isced.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_level_isced_id',
                ]
            )
            ->addIndex(
                [
                    'education_system_id',
                ]
            )
            ->create();

        $this->table('education_programme_orientations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('education_programmes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('duration', 'integer', [
                'comment' => 'No of years',
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'comment' => 'links to education_field_of_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_cycle_id', 'integer', [
                'comment' => 'links to education_cycles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_certification_id', 'integer', [
                'comment' => 'links to education_certifications.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_certification_id',
                ]
            )
            ->addIndex(
                [
                    'education_cycle_id',
                ]
            )
            ->addIndex(
                [
                    'education_field_of_study_id',
                ]
            )
            ->create();

        $this->table('education_programmes_next_programmes')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('education_programme_id', 'integer', [
                'comment' => 'links to education_programmes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('next_programme_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_programme_id',
                ]
            )
            ->addIndex(
                [
                    'next_programme_id',
                ]
            )
            ->create();

        $this->table('education_stages')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('education_subjects')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('education_subjects_field_of_studies')
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'comment' => 'links to education_field_of_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['education_subject_id', 'education_field_of_study_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'education_field_of_study_id',
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
            ->create();

        $this->table('education_systems')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('employment_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('examination_centre_rooms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('size', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('number_of_seats', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
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
            ->create();

        $this->table('examination_centre_rooms_examinations')
            ->addColumn('examination_centre_room_id', 'integer', [
                'comment' => 'links to examination_centre_rooms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_room_id', 'examination_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_room_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centre_rooms_examinations_invigilators')
            ->addColumn('examination_centre_room_id', 'integer', [
                'comment' => 'links to examination_centre_rooms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('invigilator_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_room_id', 'examination_id', 'invigilator_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_room_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'invigilator_id',
                ]
            )
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centre_rooms_examinations_students')
            ->addColumn('examination_centre_room_id', 'integer', [
                'comment' => 'links to examination_centre_rooms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examination.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_room_id', 'examination_id', 'student_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_room_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centre_special_needs')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('special_need_type_id', 'integer', [
                'comment' => 'links to special_need_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'special_need_type_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'special_need_type_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centres')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => true,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('address', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('postal_code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('contact_person', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('telephone', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => false,
            ])
            ->addColumn('fax', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => false,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('website', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('area_id', 'integer', [
                'comment' => 'links to areas.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'area_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
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
            ->create();

        $this->table('examination_centres_examinations')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'examination_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('total_registered', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centres_examinations_institutions')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'examination_id', 'institution_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centres_examinations_invigilators')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('invigilator_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'examination_id', 'invigilator_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'invigilator_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centres_examinations_students')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examination.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'examination_id', 'student_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('registration_number', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
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
            ->create();

        $this->table('examination_centres_examinations_subjects')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_item_id', 'integer', [
                'comment' => 'links to `examination_items.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'examination_item_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'examination_item_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_centres_examinations_subjects_students')
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_item_id', 'integer', [
                'comment' => 'links to `examination_items.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_centre_id', 'examination_item_id', 'student_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('total_mark', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to `education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'examination_item_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('examination_grading_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('min', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('max', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('examination_grading_type_id', 'integer', [
                'comment' => 'links to examination_grading_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_grading_type_id',
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
            ->create();

        $this->table('examination_grading_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 80,
                'null' => false,
            ])
            ->addColumn('pass_mark', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('max', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('result_type', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
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
            ->create();

        $this->table('examination_item_results')
            ->addColumn('examination_item_id', 'integer', [
                'comment' => 'links to `examination_items.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['examination_item_id', 'student_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('marks', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_centre_id', 'integer', [
                'comment' => 'links to examination_centres.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to `education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_grading_option_id', 'integer', [
                'comment' => 'links to examination_grading_options.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_item_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'examination_centre_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'examination_grading_option_id',
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
            ->create();

        $this->table('examination_items')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('weight', 'decimal', [
                'default' => '0.00',
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('examination_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('examination_id', 'integer', [
                'comment' => 'links to examinations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('examination_grading_type_id', 'integer', [
                'comment' => 'links to examination_grading_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'examination_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'examination_grading_type_id',
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
            ->create();

        $this->table('examinations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('registration_start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('registration_end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
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
            ->create();

        $this->table('external_data_source_attributes')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('external_data_source_type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('attribute_field', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('attribute_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->create();

        $this->table('extracurricular_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('fee_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('floor_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('infrastructure_custom_field_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_floor_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'infrastructure_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'institution_floor_id',
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
            ->create();

        $this->table('floor_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('genders')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('guardian_relations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('guidance_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('health_allergy_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('health_conditions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('health_consultation_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('health_immunization_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('health_relationships')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('health_test_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('identity_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('validation_pattern', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('idp_google')
            ->addColumn('system_authentication_id', 'integer', [
                'comment' => 'links to system_authenticatons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['system_authentication_id'])
            ->addColumn('client_id', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('client_secret', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('redirect_uri', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('hd', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->create();

        $this->table('idp_oauth')
            ->addColumn('system_authentication_id', 'integer', [
                'comment' => 'links to system_authenticatons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['system_authentication_id'])
            ->addColumn('client_id', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('client_secret', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('redirect_uri', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('well_known_uri', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('authorization_endpoint', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('token_endpoint', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('userinfo_endpoint', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('issuer', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('jwks_uri', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->create();

        $this->table('idp_saml')
            ->addColumn('system_authentication_id', 'integer', [
                'comment' => 'links to system_authenticatons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['system_authentication_id'])
            ->addColumn('idp_entity_id', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('idp_sso', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('idp_sso_binding', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('idp_slo', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('idp_slo_binding', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('idp_x509cert', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('idp_cert_fingerprint', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('idp_cert_fingerprint_algorithm', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('sp_entity_id', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('sp_acs', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('sp_slo', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('sp_name_id_format', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('sp_private_key', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('sp_metadata', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('import_mapping')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('column_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('is_optional', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('foreign_key', 'integer', [
                'comment' => '0: not foreign key, 1: field options, 2: direct table, 3: non-table list, 4: custom',
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('lookup_plugin', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('lookup_model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('lookup_column', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->create();

        $this->table('indexes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
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
            ->create();

        $this->table('indexes_criterias')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('criteria', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('operator', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('threshold', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('index_value', 'integer', [
                'default' => null,
                'limit' => 2,
                'null' => false,
            ])
            ->addColumn('index_id', 'integer', [
                'comment' => 'links to indexes.id',
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'index_id',
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
            ->create();

        $this->table('infrastructure_conditions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_custom_field_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('infrastructure_custom_field_id', 'integer', [
                'comment' => 'links to infrastructure_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'infrastructure_custom_field_id',
                ]
            )
            ->create();

        $this->table('infrastructure_custom_fields')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('infrastructure_custom_forms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('custom_module_id', 'integer', [
                'comment' => 'links to custom_modules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_module_id',
                ]
            )
            ->create();

        $this->table('infrastructure_custom_forms_fields')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('infrastructure_custom_form_id', 'integer', [
                'comment' => 'links to infrastructure_custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_custom_field_id', 'integer', [
                'comment' => 'links to infrastructure_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('section', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addIndex(
                [
                    'infrastructure_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_custom_form_id',
                ]
            )
            ->create();

        $this->table('infrastructure_custom_forms_filters')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('infrastructure_custom_form_id', 'integer', [
                'comment' => 'links to infrastructure_custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_custom_filter_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'infrastructure_custom_filter_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_custom_form_id',
                ]
            )
            ->create();

        $this->table('infrastructure_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'parent_id',
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
            ->create();

        $this->table('infrastructure_need_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_needs')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_determined', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_started', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_completed', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('infrastructure_need_type_id', 'integer', [
                'comment' => 'links to infrastructure_need_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('priority', 'integer', [
                'comment' => '1 => High, 2 => Medium, 3 => Low',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'priority',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_need_type_id',
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
            ->create();

        $this->table('infrastructure_ownerships')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_project_funding_sources')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_projects')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('funding_source_description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contract_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contract_amount', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 50,
                'scale' => 2,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '1 => Active, 2 => Inactive',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('date_started', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_completed', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('infrastructure_project_funding_source_id', 'integer', [
                'comment' => 'links to infrastructure_project_funding_sources.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'status',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_project_funding_source_id',
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
            ->create();

        $this->table('infrastructure_projects_needs')
            ->addColumn('infrastructure_project_id', 'integer', [
                'comment' => 'links to infrastructure_projects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_need_id', 'integer', [
                'comment' => 'links to infrastructure_needs.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['infrastructure_project_id', 'infrastructure_need_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_project_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_need_id',
                ]
            )
            ->create();

        $this->table('infrastructure_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('infrastructure_utility_electricities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('utility_electricity_type_id', 'integer', [
                'comment' => 'links to utility_electricity_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('utility_electricity_condition_id', 'integer', [
                'comment' => 'links to utility_electricity_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'utility_electricity_type_id',
                ]
            )
            ->addIndex(
                [
                    'utility_electricity_condition_id',
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
            ->create();

        $this->table('infrastructure_utility_internets')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('internet_purpose', 'integer', [
                'comment' => '1 => Teaching, 2 => Non-Teaching',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('utility_internet_type_id', 'integer', [
                'comment' => 'links to utility_internet_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('utility_internet_condition_id', 'integer', [
                'comment' => 'links to utility_internet_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'utility_internet_type_id',
                ]
            )
            ->addIndex(
                [
                    'utility_internet_condition_id',
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
            ->create();

        $this->table('infrastructure_utility_telephones')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('utility_telephone_type_id', 'integer', [
                'comment' => 'links to utility_telephone_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('utility_telephone_condition_id', 'integer', [
                'comment' => 'links to utility_telephone_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'utility_telephone_type_id',
                ]
            )
            ->addIndex(
                [
                    'utility_telephone_condition_id',
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
            ->create();

        $this->table('infrastructure_wash_water_accessibilities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_wash_water_functionalities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_wash_water_proximities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_wash_water_qualities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_wash_water_quantities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_wash_water_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('infrastructure_wash_waters')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_wash_water_type_id', 'integer', [
                'comment' => 'links to infrastructure_wash_water_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_wash_water_functionality_id', 'integer', [
                'comment' => 'links to infrastructure_wash_water_functionalities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_wash_water_proximity_id', 'integer', [
                'comment' => 'links to infrastructure_wash_water_proximities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_wash_water_quantity_id', 'integer', [
                'comment' => 'links to infrastructure_wash_water_quantities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_wash_water_quality_id', 'integer', [
                'comment' => 'links to infrastructure_wash_water_qualities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_wash_water_accessibility_id', 'integer', [
                'comment' => 'links to infrastructure_wash_water_accessibilities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_wash_water_type_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_wash_water_functionality_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_wash_water_proximity_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_wash_water_quantity_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_wash_water_quality_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_wash_water_accessibility_id',
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
            ->create();

        $this->table('institution_activities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('model_reference', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('field', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => false,
            ])
            ->addColumn('old_value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('new_value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('operation', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'model_reference',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('institution_attachments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => false,
            ])
            ->addColumn('date_on_file', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_bank_accounts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('account_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('account_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('active', 'integer', [
                'comment' => '1 is active, 0 is inactive',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('bank_branch_id', 'integer', [
                'comment' => 'links to bank_branches.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('remarks', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'bank_branch_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_buildings')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('year_acquired', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('year_disposed', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('area', 'float', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('institution_land_id', 'integer', [
                'comment' => 'links to institution_lands.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('building_type_id', 'integer', [
                'comment' => 'links to building_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('building_status_id', 'integer', [
                'comment' => 'infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_ownership_id', 'integer', [
                'comment' => 'links to infrastructure_ownerships.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('previous_institution_building_id', 'integer', [
                'comment' => 'links to institution_buildings.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
                ]
            )
            ->addIndex(
                [
                    'institution_land_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'building_type_id',
                ]
            )
            ->addIndex(
                [
                    'building_status_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_ownership_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_condition_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_building_id',
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
            ->create();

        $this->table('institution_buses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('plate_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('capacity', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('institution_transport_provider_id', 'integer', [
                'comment' => 'links to institution_transport_providers.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('bus_type_id', 'integer', [
                'comment' => 'links to bus_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('transport_status_id', 'integer', [
                'comment' => 'links to transport_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_transport_provider_id',
                ]
            )
            ->addIndex(
                [
                    'bus_type_id',
                ]
            )
            ->addIndex(
                [
                    'transport_status_id',
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
            ->create();

        $this->table('institution_buses_transport_features')
            ->addColumn('institution_bus_id', 'integer', [
                'comment' => 'links to institution_buses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('transport_feature_id', 'integer', [
                'comment' => 'links to transport_features.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['institution_bus_id', 'transport_feature_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_bus_id',
                ]
            )
            ->addIndex(
                [
                    'transport_feature_id',
                ]
            )
            ->create();

        $this->table('institution_case_records')
            ->addColumn('institution_case_id', 'integer', [
                'comment' => 'links to institution_cases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('record_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('feature', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addPrimaryKey(['institution_case_id', 'record_id', 'feature'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'institution_case_id',
                ]
            )
            ->addIndex(
                [
                    'record_id',
                ]
            )
            ->addIndex(
                [
                    'feature',
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
            ->create();

        $this->table('institution_cases')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('case_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
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
            ->create();

        $this->table('institution_class_grades')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_class_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->create();

        $this->table('institution_class_students')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'institution_class_id', 'education_grade_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_status_id', 'integer', [
                'comment' => 'links to student_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'student_status_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_class_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->create();

        $this->table('institution_class_subjects')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_subject_id', 'integer', [
                'comment' => 'links to institution_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_class_id',
                ]
            )
            ->addIndex(
                [
                    'institution_subject_id',
                ]
            )
            ->create();

        $this->table('institution_classes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('class_number', 'integer', [
                'comment' => 'This column is being used to determine whether this class is a multi-grade or single-grade.',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('total_male_students', 'integer', [
                'default' => '0',
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('total_female_students', 'integer', [
                'default' => '0',
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('secondary_staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_shift_id', 'integer', [
                'comment' => 'links to institution_shifts.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_shift_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'secondary_staff_id',
                ]
            )
            ->addIndex(
                [
                    'total_male_students',
                ]
            )
            ->addIndex(
                [
                    'total_female_students',
                ]
            )
            ->create();

        $this->table('institution_competency_item_comments')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_period_id', 'integer', [
                'comment' => 'links to competency_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_item_id', 'integer', [
                'comment' => 'links to competency_items.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'competency_template_id', 'competency_period_id', 'competency_item_id', 'institution_id', 'academic_period_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
                ]
            )
            ->addIndex(
                [
                    'competency_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_item_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
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
            ->create();

        $this->table('institution_competency_period_comments')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_period_id', 'integer', [
                'comment' => 'links to competency_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'competency_template_id', 'competency_period_id', 'institution_id', 'academic_period_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
                ]
            )
            ->addIndex(
                [
                    'competency_period_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
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
            ->create();

        $this->table('institution_competency_results')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_template_id', 'integer', [
                'comment' => 'links to competency_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_item_id', 'integer', [
                'comment' => 'links to competency_items.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_criteria_id', 'integer', [
                'comment' => 'links to competency_criterias.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_period_id', 'integer', [
                'comment' => 'links to competency_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'competency_template_id', 'competency_item_id', 'competency_criteria_id', 'competency_period_id', 'institution_id', 'academic_period_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('competency_grading_option_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'competency_template_id',
                ]
            )
            ->addIndex(
                [
                    'competency_item_id',
                ]
            )
            ->addIndex(
                [
                    'competency_criteria_id',
                ]
            )
            ->addIndex(
                [
                    'competency_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_grading_option_id',
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
            ->create();

        $this->table('institution_counsellings')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('intervention', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('counselor_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('guidance_type_id', 'integer', [
                'comment' => 'links to guidance_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'counselor_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'guidance_type_id',
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
            ->create();

        $this->table('institution_custom_field_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'comment' => 'links to institution_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_custom_field_id',
                ]
            )
            ->create();

        $this->table('institution_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'comment' => 'links to institution_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'institution_custom_field_id',
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
            ->create();

        $this->table('institution_custom_fields')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_custom_forms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('custom_module_id', 'integer', [
                'comment' => 'links to custom_modules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_module_id',
                ]
            )
            ->create();

        $this->table('institution_custom_forms_fields')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('institution_custom_form_id', 'integer', [
                'comment' => 'links to institution_custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'comment' => 'links to institution_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('section', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'institution_custom_form_id',
                ]
            )
            ->create();

        $this->table('institution_custom_forms_filters')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('institution_custom_form_id', 'integer', [
                'comment' => 'links to institution_custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_custom_filter_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_custom_filter_id',
                ]
            )
            ->addIndex(
                [
                    'institution_custom_form_id',
                ]
            )
            ->create();

        $this->table('institution_custom_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'comment' => 'links to institution_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_custom_table_column_id', 'integer', [
                'comment' => 'links to institution_custom_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_custom_table_row_id', 'integer', [
                'comment' => 'links to institution_custom_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'institution_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'institution_custom_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'institution_custom_table_row_id',
                ]
            )
            ->create();

        $this->table('institution_custom_table_columns')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'comment' => 'links to institution_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_custom_field_id',
                ]
            )
            ->create();

        $this->table('institution_custom_table_rows')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'comment' => 'links to institution_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_custom_field_id',
                ]
            )
            ->create();

        $this->table('institution_fee_types')
            ->addColumn('institution_fee_id', 'integer', [
                'comment' => 'links to institution_fees.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('fee_type_id', 'integer', [
                'comment' => 'links to fee_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['institution_fee_id', 'fee_type_id'])
            ->addColumn('id', 'uuid', [
                'comment' => 'To be compatible with CakePHP cascade delete',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_fees')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('total', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 50,
                'scale' => 2,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_floors')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('area', 'float', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('institution_building_id', 'integer', [
                'comment' => 'links to institution_buildings.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('floor_type_id', 'integer', [
                'comment' => 'links to floor_types.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('floor_status_id', 'integer', [
                'comment' => 'infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('previous_institution_floor_id', 'integer', [
                'comment' => 'links to institution_floors.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
                ]
            )
            ->addIndex(
                [
                    'institution_building_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'floor_type_id',
                ]
            )
            ->addIndex(
                [
                    'floor_status_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_condition_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_floor_id',
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
            ->create();

        $this->table('institution_genders')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_grades')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->create();

        $this->table('institution_indexes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status', 'integer', [
                'comment' => '1 => Not Generated 2 => Processing 3 => Completed 4 => Not Completed',
                'default' => '1',
                'limit' => 2,
                'null' => false,
            ])
            ->addColumn('pid', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('generated_on', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('generated_by', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('index_id', 'integer', [
                'comment' => 'links to indexes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'index_id',
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
            ->create();

        $this->table('institution_lands')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('year_acquired', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('year_disposed', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('area', 'float', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('land_type_id', 'integer', [
                'comment' => 'links to land_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('land_status_id', 'integer', [
                'comment' => 'infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_ownership_id', 'integer', [
                'comment' => 'links to infrastructure_ownerships.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('previous_institution_land_id', 'integer', [
                'comment' => 'links to institution_lands.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'land_type_id',
                ]
            )
            ->addIndex(
                [
                    'land_status_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_ownership_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_condition_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_land_id',
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
            ->create();

        $this->table('institution_localities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_network_connectivities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_ownerships')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_positions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('position_no', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => false,
            ])
            ->addColumn('staff_position_title_id', 'integer', [
                'comment' => 'links to staff_position_titles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_position_grade_id', 'integer', [
                'comment' => 'links to staff_position_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('is_homeroom', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'staff_position_title_id',
                ]
            )
            ->addIndex(
                [
                    'staff_position_grade_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
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
            ->create();

        $this->table('institution_providers')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_sector_id', 'integer', [
                'comment' => 'links to institution_sectors.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_quality_rubric_answers')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('institution_quality_rubric_id', 'integer', [
                'comment' => 'links to institution_quality_rubrics.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rubric_section_id', 'integer', [
                'comment' => 'links to rubric_sections.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rubric_criteria_id', 'integer', [
                'comment' => 'links to rubric_criterias.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rubric_criteria_option_id', 'integer', [
                'comment' => 'links to rubric_criteria_options.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_section_id',
                ]
            )
            ->addIndex(
                [
                    'rubric_criteria_id',
                ]
            )
            ->addIndex(
                [
                    'rubric_criteria_option_id',
                ]
            )
            ->addIndex(
                [
                    'institution_quality_rubric_id',
                ]
            )
            ->create();

        $this->table('institution_quality_rubrics')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status', 'integer', [
                'comment' => '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('rubric_template_id', 'integer', [
                'comment' => 'links to rubric_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_subject_id', 'integer', [
                'comment' => 'links to institution_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_template_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'institution_class_id',
                ]
            )
            ->addIndex(
                [
                    'institution_subject_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_quality_visits')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('quality_visit_type_id', 'integer', [
                'comment' => 'links to quality_visit_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_subject_id', 'integer', [
                'comment' => 'links to institution_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'quality_visit_type_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_subject_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_repeater_survey_answers')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_repeater_survey_id', 'integer', [
                'comment' => 'links to institution_repeater_surveys.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->addIndex(
                [
                    'institution_repeater_survey_id',
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
            ->create();

        $this->table('institution_repeater_survey_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_table_column_id', 'integer', [
                'comment' => 'links to survey_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_table_row_id', 'integer', [
                'comment' => 'links to survey_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_repeater_survey_id', 'integer', [
                'comment' => 'links to institution_repeater_surveys.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->addIndex(
                [
                    'survey_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'survey_table_row_id',
                ]
            )
            ->addIndex(
                [
                    'institution_repeater_survey_id',
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
            ->create();

        $this->table('institution_repeater_surveys')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('repeater_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_form_id', 'integer', [
                'comment' => 'links to survey_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('parent_form_id', 'integer', [
                'comment' => 'links to institution_surveys.survey_form_id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'survey_form_id',
                ]
            )
            ->addIndex(
                [
                    'parent_form_id',
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
            ->create();

        $this->table('institution_rooms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('room_status_id', 'integer', [
                'comment' => 'links to infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_floor_id', 'integer', [
                'comment' => 'links to institution_floors.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('room_type_id', 'integer', [
                'comment' => 'links to room_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('previous_institution_room_id', 'integer', [
                'comment' => 'links to institution_rooms.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
                ]
            )
            ->addIndex(
                [
                    'room_status_id',
                ]
            )
            ->addIndex(
                [
                    'institution_floor_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'room_type_id',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_condition_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_room_id',
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
            ->create();

        $this->table('institution_sectors')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_shifts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('location_institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('shift_option_id', 'integer', [
                'comment' => 'links to shift_options.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('previous_shift_id', 'integer', [
                'comment' => 'links to institution_shifts.id',
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'location_institution_id',
                ]
            )
            ->create();

        $this->table('institution_staff')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('FTE', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_type_id', 'integer', [
                'comment' => 'links to staff_types.id',
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('staff_status_id', 'integer', [
                'comment' => 'links to staff_statuses.id',
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_position_id', 'integer', [
                'comment' => 'links to institution_positions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_group_user_id', 'uuid', [
                'comment' => 'links to security_group_users.id',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_type_id',
                ]
            )
            ->addIndex(
                [
                    'staff_status_id',
                ]
            )
            ->addIndex(
                [
                    'institution_position_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'security_group_user_id',
                ]
            )
            ->addIndex(
                [
                    'start_date',
                ]
            )
            ->addIndex(
                [
                    'end_date',
                ]
            )
            ->addIndex(
                [
                    'start_date',
                ]
            )
            ->addIndex(
                [
                    'end_date',
                ]
            )
            ->create();

        $this->table('institution_staff_absences')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('full_day', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('absence_type_id', 'integer', [
                'comment' => 'links to absence_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_absence_reason_id', 'integer', [
                'comment' => 'links to staff_absence_reasons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_absence_reason_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'absence_type_id',
                ]
            )
            ->create();

        $this->table('institution_staff_assignments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '0 -> New, 1 -> Approved, 2 -> Rejected, 3 -> Closed (For fixed workflow)',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_type_id', 'integer', [
                'comment' => 'links to staff_types.id',
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_position_id', 'integer', [
                'comment' => 'links to institution_positions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('FTE', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('type', 'integer', [
                'comment' => '1 -> Staff Assignment, 2 -> Staff Transfer',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('update', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'institution_position_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_id',
                ]
            )
            ->addIndex(
                [
                    'staff_type_id',
                ]
            )
            ->create();

        $this->table('institution_staff_leave')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date_from', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_to', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_leave_type_id', 'integer', [
                'comment' => 'links to staff_leave_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('number_of_days', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'staff_leave_type_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
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
            ->create();

        $this->table('institution_staff_position_profiles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('institution_staff_id', 'integer', [
                'comment' => 'links to institution_staff.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_change_type_id', 'integer', [
                'comment' => 'links to staff_change_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('FTE', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_type_id', 'integer', [
                'comment' => 'links to staff_types.id',
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_position_id', 'integer', [
                'comment' => 'links to institution_positions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_staff_id',
                ]
            )
            ->addIndex(
                [
                    'staff_change_type_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'staff_type_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'institution_position_id',
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
            ->create();

        $this->table('institution_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('institution_student_absences')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('full_day', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('absence_type_id', 'integer', [
                'comment' => 'links to absence_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_absence_reason_id', 'integer', [
                'comment' => 'links to student_absence_reasons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_absence_reason_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'absence_type_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_student_admission')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('requested_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('new_education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_transfer_reason_id', 'integer', [
                'comment' => 'links to student_transfer_reasons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('type', 'integer', [
                'comment' => '1 -> Admission, 2 -> Transfer',
                'default' => '2',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
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
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'new_education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'institution_class_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_id',
                ]
            )
            ->addIndex(
                [
                    'student_transfer_reason_id',
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
            ->create();

        $this->table('institution_student_indexes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('average_index', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 4,
                'scale' => 2,
            ])
            ->addColumn('total_index', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('index_id', 'integer', [
                'comment' => 'links to indexes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'index_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
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
            ->create();

        $this->table('institution_student_survey_answers')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_student_survey_id', 'integer', [
                'comment' => 'links to institution_student_surveys.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->addIndex(
                [
                    'institution_student_survey_id',
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
            ->create();

        $this->table('institution_student_survey_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_table_column_id', 'integer', [
                'comment' => 'links to survey_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_table_row_id', 'integer', [
                'comment' => 'links to survey_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_student_survey_id', 'integer', [
                'comment' => 'links to institution_student_surveys.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->addIndex(
                [
                    'survey_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'survey_table_row_id',
                ]
            )
            ->addIndex(
                [
                    'institution_student_survey_id',
                ]
            )
            ->create();

        $this->table('institution_student_surveys')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_form_id', 'integer', [
                'comment' => 'links to survey_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('parent_form_id', 'integer', [
                'comment' => 'links to institution_surveys.survey_form_id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'survey_form_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'parent_form_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->create();

        $this->table('institution_student_withdraw')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('effective_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_withdraw_reason_id', 'integer', [
                'comment' => 'links to student_withdraw_reasons.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'student_withdraw_reason_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->create();

        $this->table('institution_students')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('student_status_id', 'integer', [
                'comment' => 'links to student_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('previous_institution_student_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
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
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'student_status_id',
                ]
            )
            ->addIndex(
                [
                    'previous_institution_student_id',
                ]
            )
            ->create();

        $this->table('institution_students_report_cards')
            ->addColumn('report_card_id', 'integer', [
                'comment' => 'links to report_cards.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['report_card_id', 'student_id', 'institution_id', 'academic_period_id', 'education_grade_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '1 -> New, 2 -> In Progress, 3 -> Generated, 4 -> Published',
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('principal_comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('homeroom_teacher_comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'report_card_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'institution_class_id',
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
            ->create();

        $this->table('institution_students_report_cards_comments')
            ->addColumn('report_card_id', 'integer', [
                'comment' => 'links to report_cards.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['report_card_id', 'student_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'education_subject_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('report_card_comment_code_id', 'integer', [
                'comment' => 'links to report_card_comment_codes.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'report_card_comment_code_id',
                ]
            )
            ->addIndex(
                [
                    'report_card_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
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
            ->create();

        $this->table('institution_students_tmp')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
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
                ]
            )
            ->create();

        $this->table('institution_subject_staff')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_subject_id', 'integer', [
                'comment' => 'links to institution_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_subject_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('institution_subject_students')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'institution_class_id', 'institution_id', 'academic_period_id', 'education_subject_id', 'education_grade_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('total_mark', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('institution_subject_id', 'integer', [
                'comment' => 'links to institution_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_status_id', 'integer', [
                'comment' => 'links to student_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
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
                ]
            )
            ->addIndex(
                [
                    'institution_subject_id',
                ]
            )
            ->addIndex(
                [
                    'institution_class_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
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
                    'student_status_id',
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

        $this->table('institution_subjects')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('no_of_seats', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->create();

        $this->table('institution_subjects_rooms')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('institution_subject_id', 'integer', [
                'comment' => 'links to institution_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_room_id', 'integer', [
                'comment' => 'links to institution_rooms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_subject_id',
                ]
            )
            ->addIndex(
                [
                    'institution_room_id',
                ]
            )
            ->create();

        $this->table('institution_survey_answers')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_survey_id', 'integer', [
                'comment' => 'links to institution_surveys.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->addIndex(
                [
                    'institution_survey_id',
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
            ->create();

        $this->table('institution_survey_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_table_column_id', 'integer', [
                'comment' => 'links to survey_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_table_row_id', 'integer', [
                'comment' => 'links to survey_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_survey_id', 'integer', [
                'comment' => 'links to institution_surveys.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_survey_id',
                ]
            )
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->addIndex(
                [
                    'survey_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'survey_table_row_id',
                ]
            )
            ->create();

        $this->table('institution_surveys')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_form_id', 'integer', [
                'comment' => 'links to survey_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'survey_form_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
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
            ->create();

        $this->table('institution_textbooks')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_period.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'academic_period_id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('textbook_status_id', 'integer', [
                'comment' => 'links to textbook_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('textbook_condition_id', 'integer', [
                'comment' => 'links to textbook_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('textbook_id', 'integer', [
                'comment' => 'links to textbooks.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'textbook_id',
                ]
            )
            ->addIndex(
                [
                    'textbook_status_id',
                ]
            )
            ->addIndex(
                [
                    'textbook_condition_id',
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
                    'education_grade_id',
                ]
            )
            ->create();

        $this->table('institution_transport_providers')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('address', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('contact_number', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => false,
            ])
            ->addColumn('registration_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('institution_trip_days')
            ->addColumn('institution_trip_id', 'integer', [
                'comment' => 'links to institution_trips.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('day', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addPrimaryKey(['institution_trip_id', 'day'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_trip_id',
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
            ->create();

        $this->table('institution_trip_passengers')
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_trip_id', 'integer', [
                'comment' => 'links to institution_trips.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['student_id', 'education_grade_id', 'academic_period_id', 'institution_id', 'institution_trip_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
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
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'institution_trip_id',
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
            ->create();

        $this->table('institution_trips')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('repeat', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('trip_type_id', 'integer', [
                'comment' => 'links to trip_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_transport_provider_id', 'integer', [
                'comment' => 'links to institution_transport_providers.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_bus_id', 'integer', [
                'comment' => 'links to institution_buses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'trip_type_id',
                ]
            )
            ->addIndex(
                [
                    'institution_transport_provider_id',
                ]
            )
            ->addIndex(
                [
                    'institution_bus_id',
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
            ->create();

        $this->table('institution_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('institution_visit_requests')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date_of_visit', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('quality_visit_type_id', 'integer', [
                'comment' => 'links to quality_visit_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'quality_visit_type_id',
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
            ->create();

        $this->table('institutions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('alternative_name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => true,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('address', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('postal_code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('contact_person', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('telephone', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true,
            ])
            ->addColumn('fax', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('website', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('date_opened', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('year_opened', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('date_closed', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('year_closed', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('longitude', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('latitude', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('logo_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('logo_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('shift_type', 'integer', [
                'comment' => '1=Single Shift Owner, 2=Single Shift Occupier, 3=Multiple Shift Owner, 4=Multiple Shift Occupier',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('classification', 'integer', [
                'comment' => '1 -> Academic Institution, 2 -> Non-academic institution',
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('area_id', 'integer', [
                'comment' => 'links to areas.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('area_administrative_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('institution_locality_id', 'integer', [
                'comment' => 'links to institution_localities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_type_id', 'integer', [
                'comment' => 'links to institution_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_ownership_id', 'integer', [
                'comment' => 'links to institution_ownerships.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_status_id', 'integer', [
                'comment' => 'links to institution_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_sector_id', 'integer', [
                'comment' => 'links to institution_sectors.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_provider_id', 'integer', [
                'comment' => 'links to institution_providers.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_gender_id', 'integer', [
                'comment' => 'links to institution_genders.id',
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('institution_network_connectivity_id', 'integer', [
                'comment' => 'links to institution_network_connectivities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_group_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'area_id',
                ]
            )
            ->addIndex(
                [
                    'security_group_id',
                ]
            )
            ->addIndex(
                [
                    'institution_locality_id',
                ]
            )
            ->addIndex(
                [
                    'institution_type_id',
                ]
            )
            ->addIndex(
                [
                    'institution_ownership_id',
                ]
            )
            ->addIndex(
                [
                    'institution_status_id',
                ]
            )
            ->addIndex(
                [
                    'institution_sector_id',
                ]
            )
            ->addIndex(
                [
                    'institution_provider_id',
                ]
            )
            ->addIndex(
                [
                    'institution_gender_id',
                ]
            )
            ->addIndex(
                [
                    'institution_network_connectivity_id',
                ]
            )
            ->addIndex(
                [
                    'area_administrative_id',
                ]
            )
            ->create();

        $this->table('labels')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('module', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('field', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('module_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('field_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'code',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('land_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('infrastructure_custom_field_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_land_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'infrastructure_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'institution_land_id',
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
            ->create();

        $this->table('land_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('languages')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('license_classifications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('license_type_id', 'integer', [
                'comment' => 'links to license_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'license_type_id',
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
            ->create();

        $this->table('license_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('nationalities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('identity_type_id', 'integer', [
                'comment' => 'links to identity_types.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'identity_type_id',
                ]
            )
            ->create();

        $this->table('notices')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('qualification_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('qualification_specialisations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'comment' => 'links to education_field_of_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_field_of_study_id',
                ]
            )
            ->create();

        $this->table('qualification_titles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('qualification_level_id', 'integer', [
                'comment' => 'links to qualification_levels.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'qualification_level_id',
                ]
            )
            ->create();

        $this->table('quality_visit_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('report_card_comment_codes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('report_card_subjects')
            ->addColumn('report_card_id', 'integer', [
                'comment' => 'links to report_cards.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['report_card_id', 'education_subject_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'report_card_id',
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
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('report_cards')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('principal_comments_required', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('homeroom_teacher_comments_required', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('teacher_comments_required', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('excel_template_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('excel_template', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
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
            ->create();

        $this->table('report_progress')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('module', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('sql', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('expiry_date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_path', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('current_records', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('total_records', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('pid', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('error_message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'pid',
                ]
            )
            ->create();

        $this->table('reports')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('query', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('filter', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('excel_template_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('excel_template', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('format', 'integer', [
                'comment' => '1 -> CSV, 2 -> XLSX',
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('room_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('infrastructure_custom_field_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_room_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'infrastructure_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'institution_room_id',
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
            ->create();

        $this->table('room_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('classification', 'integer', [
                'comment' => '0 -> Non-Classroom, 1 -> Classroom',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('rubric_criteria_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('rubric_template_option_id', 'integer', [
                'comment' => 'links to rubric_template_options.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rubric_criteria_id', 'integer', [
                'comment' => 'links to rubric_criterias.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_template_option_id',
                ]
            )
            ->addIndex(
                [
                    'rubric_criteria_id',
                ]
            )
            ->create();

        $this->table('rubric_criterias')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('type', 'integer', [
                'comment' => '1 -> Section Break, 2 -> Dropdown',
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('rubric_section_id', 'integer', [
                'comment' => 'links to rubric_sections.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_section_id',
                ]
            )
            ->create();

        $this->table('rubric_sections')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('rubric_template_id', 'integer', [
                'comment' => 'links to rubric_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_template_id',
                ]
            )
            ->create();

        $this->table('rubric_status_periods')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rubric_status_id', 'integer', [
                'comment' => 'links to rubric_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'rubric_status_id',
                ]
            )
            ->create();

        $this->table('rubric_status_programmes')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('education_programme_id', 'integer', [
                'comment' => 'links to education_programmes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rubric_status_id', 'integer', [
                'comment' => 'links to rubric_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_programme_id',
                ]
            )
            ->addIndex(
                [
                    'rubric_status_id',
                ]
            )
            ->create();

        $this->table('rubric_status_roles')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('rubric_status_id', 'integer', [
                'comment' => 'links to rubric_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_status_id',
                ]
            )
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->create();

        $this->table('rubric_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date_enabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_disabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('rubric_template_id', 'integer', [
                'comment' => 'links to rubric_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_template_id',
                ]
            )
            ->create();

        $this->table('rubric_template_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('weighting', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('color', 'string', [
                'default' => '#ffffff',
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('rubric_template_id', 'integer', [
                'comment' => 'links to rubric_templates.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'rubric_template_id',
                ]
            )
            ->create();

        $this->table('rubric_templates')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('weighting_type', 'integer', [
                'comment' => '1 -> point, 2 -> percent',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('pass_mark', 'integer', [
                'default' => '0',
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('salary_addition_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('salary_deduction_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('security_functions')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('controller', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('module', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('category', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('_view', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('_edit', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('_add', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('_delete', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('_execute', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'parent_id',
                ]
            )
            ->create();

        $this->table('security_group_areas')
            ->addColumn('security_group_id', 'integer', [
                'comment' => 'links to security_groups.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('area_id', 'integer', [
                'comment' => 'links to areas.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['security_group_id', 'area_id'])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('security_group_institutions')
            ->addColumn('security_group_id', 'integer', [
                'comment' => 'links to security_groups.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['security_group_id', 'institution_id'])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->create();

        $this->table('security_group_users')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('security_group_id', 'integer', [
                'comment' => 'links to security_groups.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_group_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->create();

        $this->table('security_groups')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('security_rest_sessions')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('access_token', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('refresh_token', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('expiry_date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'access_token',
                ]
            )
            ->addIndex(
                [
                    'refresh_token',
                ]
            )
            ->addIndex(
                [
                    'expiry_date',
                ]
            )
            ->create();

        $this->table('security_role_functions')
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_function_id', 'integer', [
                'comment' => 'links to security_functions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['security_role_id', 'security_function_id'])
            ->addColumn('_view', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('_edit', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('_add', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('_delete', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('_execute', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_function_id',
                ]
            )
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->create();

        $this->table('security_roles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('security_group_id', 'integer', [
                'comment' => 'links to security_groups.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_group_id',
                ]
            )
            ->addIndex(
                [
                    'code',
                ]
            )
            ->create();

        $this->table('security_user_logins')
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('login_period', 'integer', [
                'default' => null,
                'limit' => 6,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'login_period'])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('login_date_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('session_id', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => true,
            ])
            ->addColumn('ip_address', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => true,
            ])
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->addIndex(
                [
                    'login_date_time',
                ]
            )
            ->addIndex(
                [
                    'login_period',
                ]
            )
            ->create();

        $this->table('security_user_sessions')
            ->addColumn('id', 'string', [
                'default' => '',
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('username', 'string', [
                'default' => '',
                'limit' => 50,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'username'])
            ->create();

        $this->table('security_users')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('username', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'default' => '',
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('openemis_no', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('first_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('middle_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('third_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('last_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('preferred_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('address', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('postal_code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('address_area_id', 'integer', [
                'comment' => 'links to area_administratives.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('birthplace_area_id', 'integer', [
                'comment' => 'links to area_administratives.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('gender_id', 'integer', [
                'comment' => 'links to genders.id',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('date_of_birth', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_of_death', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('nationality_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('identity_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('identity_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('external_reference', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('super_admin', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '0 -> Inactive, 1 -> Active',
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('last_login', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('photo_name', 'string', [
                'default' => '',
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('photo_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('preferred_language', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('is_student', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_staff', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_guardian', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'openemis_no',
                ]
            )
            ->addIndex(
                [
                    'first_name',
                ]
            )
            ->addIndex(
                [
                    'last_name',
                ]
            )
            ->addIndex(
                [
                    'gender_id',
                ]
            )
            ->addIndex(
                [
                    'address_area_id',
                ]
            )
            ->addIndex(
                [
                    'birthplace_area_id',
                ]
            )
            ->addIndex(
                [
                    'status',
                ]
            )
            ->addIndex(
                [
                    'middle_name',
                ]
            )
            ->addIndex(
                [
                    'third_name',
                ]
            )
            ->addIndex(
                [
                    'is_student',
                ]
            )
            ->addIndex(
                [
                    'is_staff',
                ]
            )
            ->addIndex(
                [
                    'is_guardian',
                ]
            )
            ->addIndex(
                [
                    'super_admin',
                ]
            )
            ->addIndex(
                [
                    'identity_number',
                ]
            )
            ->addIndex(
                [
                    'username',
                ]
            )
            ->addIndex(
                [
                    'is_student',
                    'first_name',
                    'last_name',
                    'gender_id',
                    'date_of_birth',
                ]
            )
            ->create();

        $this->table('shift_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('single_logout')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('username', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('session_id', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addIndex(
                [
                    'username',
                ]
            )
            ->addIndex(
                [
                    'session_id',
                ]
            )
            ->create();

        $this->table('special_need_difficulties')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('special_need_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('staff_absence_reasons')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('staff_appraisal_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('staff_appraisals')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('from', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('to', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('final_rating', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 7,
                'scale' => 2,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('competency_set_id', 'integer', [
                'comment' => 'links to competency_sets.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_appraisal_type_id', 'integer', [
                'comment' => 'links to staff_appraisal_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'competency_set_id',
                ]
            )
            ->addIndex(
                [
                    'staff_appraisal_type_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->create();

        $this->table('staff_appraisals_competencies')
            ->addColumn('competency_id', 'integer', [
                'comment' => 'links to competencies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_appraisal_id', 'integer', [
                'comment' => 'links to staff_appraisals.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['competency_id', 'staff_appraisal_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('rating', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'competency_id',
                ]
            )
            ->addIndex(
                [
                    'staff_appraisal_id',
                ]
            )
            ->create();

        $this->table('staff_behaviour_categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('staff_behaviours')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_of_behaviour', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('time_of_behaviour', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_behaviour_category_id', 'integer', [
                'comment' => 'links to staff_behaviour_categories.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('behaviour_classification_id', 'integer', [
                'comment' => 'links to behaviour_classifications.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'staff_behaviour_category_id',
                ]
            )
            ->addIndex(
                [
                    'behaviour_classification_id',
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
            ->create();

        $this->table('staff_change_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->create();

        $this->table('staff_custom_field_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'comment' => 'links to staff_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_custom_field_id',
                ]
            )
            ->create();

        $this->table('staff_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'comment' => 'links to staff_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'staff_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
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
            ->create();

        $this->table('staff_custom_fields')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('staff_custom_forms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('custom_module_id', 'integer', [
                'comment' => 'links to custom_modules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_module_id',
                ]
            )
            ->create();

        $this->table('staff_custom_forms_fields')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('staff_custom_form_id', 'integer', [
                'comment' => 'links to staff_custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'comment' => 'links to staff_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('section', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'staff_custom_form_id',
                ]
            )
            ->create();

        $this->table('staff_custom_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'comment' => 'links to staff_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_custom_table_column_id', 'integer', [
                'comment' => 'links to staff_custom_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_custom_table_row_id', 'integer', [
                'comment' => 'links to staff_custom_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'staff_custom_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'staff_custom_table_row_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->create();

        $this->table('staff_custom_table_columns')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'comment' => 'links to staff_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_custom_field_id',
                ]
            )
            ->create();

        $this->table('staff_custom_table_rows')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'comment' => 'links to staff_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_custom_field_id',
                ]
            )
            ->create();

        $this->table('staff_employments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('employment_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('employment_type_id', 'integer', [
                'comment' => 'links to employment_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'employment_type_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->create();

        $this->table('staff_extracurriculars')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('hours', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('points', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('location', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('extracurricular_type_id', 'integer', [
                'comment' => 'links to extracurricular_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'extracurricular_type_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->create();

        $this->table('staff_leave_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('staff_licenses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('license_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('issue_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('expiry_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('issuer', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('license_type_id', 'integer', [
                'comment' => 'links to license_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'license_type_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
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
            ->create();

        $this->table('staff_licenses_classifications')
            ->addColumn('staff_license_id', 'integer', [
                'comment' => 'links to staff_licenses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('license_classification_id', 'integer', [
                'comment' => 'links to license_classifications.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['staff_license_id', 'license_classification_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_license_id',
                ]
            )
            ->addIndex(
                [
                    'license_classification_id',
                ]
            )
            ->create();

        $this->table('staff_memberships')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('issue_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('membership', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('expiry_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->create();

        $this->table('staff_position_grades')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('staff_position_titles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('type', 'integer', [
                'comment' => '0-Non-Teaching / 1-Teaching',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->create();

        $this->table('staff_qualifications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('document_no', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('graduate_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('qualification_institution', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('gpa', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'comment' => 'links to education_field_of_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('qualification_title_id', 'integer', [
                'comment' => 'links to qualification_titles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('qualification_country_id', 'integer', [
                'comment' => 'links to countries.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'education_field_of_study_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'qualification_title_id',
                ]
            )
            ->addIndex(
                [
                    'qualification_country_id',
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
            ->create();

        $this->table('staff_qualifications_specialisations')
            ->addColumn('staff_qualification_id', 'integer', [
                'comment' => 'links to staff_qualifications.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('qualification_specialisation_id', 'integer', [
                'comment' => 'links to qualification_specialisations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['staff_qualification_id', 'qualification_specialisation_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_qualification_id',
                ]
            )
            ->addIndex(
                [
                    'qualification_specialisation_id',
                ]
            )
            ->create();

        $this->table('staff_qualifications_subjects')
            ->addColumn('staff_qualification_id', 'integer', [
                'comment' => 'links to staff_qualifications.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['staff_qualification_id', 'education_subject_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_qualification_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->create();

        $this->table('staff_salaries')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('salary_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('gross_salary', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 20,
                'scale' => 2,
            ])
            ->addColumn('additions', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 20,
                'scale' => 2,
            ])
            ->addColumn('deductions', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 20,
                'scale' => 2,
            ])
            ->addColumn('net_salary', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 20,
                'scale' => 2,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->create();

        $this->table('staff_salary_additions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 11,
                'scale' => 2,
            ])
            ->addColumn('salary_addition_type_id', 'integer', [
                'comment' => 'links to salary_addition_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_salary_id', 'integer', [
                'comment' => 'links to staff_salaries.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_salary_id',
                ]
            )
            ->addIndex(
                [
                    'salary_addition_type_id',
                ]
            )
            ->create();

        $this->table('staff_salary_deductions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 11,
                'scale' => 2,
            ])
            ->addColumn('salary_deduction_type_id', 'integer', [
                'comment' => 'links to salary_deduction_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_salary_id', 'integer', [
                'comment' => 'links to staff_salaries.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_salary_id',
                ]
            )
            ->addIndex(
                [
                    'salary_deduction_type_id',
                ]
            )
            ->create();

        $this->table('staff_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('staff_training_applications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_session_id', 'integer', [
                'comment' => 'links to training_sessions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'training_session_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
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
            ->create();

        $this->table('staff_training_categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('staff_training_needs')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('reason', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_need_category_id', 'integer', [
                'comment' => 'links to training_need_categories.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_need_competency_id', 'integer', [
                'comment' => 'links to training_need_competencies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_need_sub_standard_id', 'integer', [
                'comment' => 'links to training_need_sub_standards.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_priority_id', 'integer', [
                'comment' => 'links to training_priorities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'training_need_category_id',
                ]
            )
            ->addIndex(
                [
                    'training_priority_id',
                ]
            )
            ->addIndex(
                [
                    'training_need_competency_id',
                ]
            )
            ->addIndex(
                [
                    'training_need_sub_standard_id',
                ]
            )
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
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
            ->create();

        $this->table('staff_training_self_studies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_achievement_type_id', 'integer', [
                'comment' => 'links to training_achievement_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('objective', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('location', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('training_provider', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('hours', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('credit_hours', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_status_id', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_status_id',
                ]
            )
            ->addIndex(
                [
                    'training_achievement_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('staff_training_self_study_attachments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('staff_training_self_study_id', 'integer', [
                'comment' => 'links to staff_training_self_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_training_self_study_id',
                ]
            )
            ->create();

        $this->table('staff_training_self_study_results')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('staff_training_self_study_id', 'integer', [
                'comment' => 'links to staff_training_self_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('pass', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('result', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('training_status_id', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_training_self_study_id',
                ]
            )
            ->addIndex(
                [
                    'training_status_id',
                ]
            )
            ->create();

        $this->table('staff_trainings')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('credit_hours', 'integer', [
                'default' => '0',
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('completed_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_training_category_id', 'integer', [
                'comment' => 'links to staff_training_categories.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_field_of_study_id', 'integer', [
                'comment' => 'links to training_field_of_studies.id',
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'staff_id',
                ]
            )
            ->addIndex(
                [
                    'staff_training_category_id',
                ]
            )
            ->addIndex(
                [
                    'training_field_of_study_id',
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
            ->create();

        $this->table('staff_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('student_absence_reasons')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('student_behaviour_categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('behaviour_classification_id', 'integer', [
                'comment' => 'links to behaviour_classifications.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'behaviour_classification_id',
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
            ->create();

        $this->table('student_behaviours')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('action', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_of_behaviour', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('time_of_behaviour', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_behaviour_category_id', 'integer', [
                'comment' => 'links to student_behaviour_categories.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_behaviour_category_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->addIndex(
                [
                    'institution_id',
                ]
            )
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->create();

        $this->table('student_custom_field_options')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'comment' => 'links to student_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_custom_field_id',
                ]
            )
            ->create();

        $this->table('student_custom_field_values')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('number_value', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('decimal_value', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('textarea_value', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('date_value', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_value', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'comment' => 'links to student_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'number_value',
                ]
            )
            ->addIndex(
                [
                    'student_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
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
            ->create();

        $this->table('student_custom_fields')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('student_custom_forms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('custom_module_id', 'integer', [
                'comment' => 'links to custom_modules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_module_id',
                ]
            )
            ->create();

        $this->table('student_custom_forms_fields')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('student_custom_form_id', 'integer', [
                'comment' => 'links to student_custom_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'comment' => 'links to student_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('section', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'student_custom_form_id',
                ]
            )
            ->create();

        $this->table('student_custom_table_cells')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('text_value', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'comment' => 'links to student_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_custom_table_column_id', 'integer', [
                'comment' => 'links to student_custom_table_columns.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_custom_table_row_id', 'integer', [
                'comment' => 'links to student_custom_table_rows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_custom_field_id',
                ]
            )
            ->addIndex(
                [
                    'student_custom_table_column_id',
                ]
            )
            ->addIndex(
                [
                    'student_custom_table_row_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->create();

        $this->table('student_custom_table_columns')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'comment' => 'links to student_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_custom_field_id',
                ]
            )
            ->create();

        $this->table('student_custom_table_rows')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'comment' => 'links to student_custom_fields.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'student_custom_field_id',
                ]
            )
            ->create();

        $this->table('student_extracurriculars')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('hours', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('points', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('location', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('extracurricular_type_id', 'integer', [
                'comment' => 'links to extracurricular_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'extracurricular_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('student_fees')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 50,
                'scale' => 2,
            ])
            ->addColumn('payment_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_fee_id', 'integer', [
                'comment' => 'links to institution_fees.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_fee_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->create();

        $this->table('student_guardians')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('guardian_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('guardian_relation_id', 'integer', [
                'comment' => 'links to guardian_relations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'guardian_id',
                ]
            )
            ->addIndex(
                [
                    'guardian_relation_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                    'guardian_id',
                ]
            )
            ->addIndex(
                [
                    'student_id',
                ]
            )
            ->create();

        $this->table('student_indexes_criterias')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('institution_student_index_id', 'integer', [
                'comment' => 'links to institution_student_indexes.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('indexes_criteria_id', 'integer', [
                'comment' => 'links to indexes_criterias.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'institution_student_index_id',
                ]
            )
            ->addIndex(
                [
                    'indexes_criteria_id',
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
            ->create();

        $this->table('student_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('student_transfer_reasons')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('student_withdraw_reasons')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('survey_forms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('custom_module_id', 'integer', [
                'comment' => 'links to custom_modules.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'custom_module_id',
                ]
            )
            ->create();

        $this->table('survey_forms_questions')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('survey_form_id', 'integer', [
                'comment' => 'links to survey_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('section', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_form_id',
                ]
            )
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->create();

        $this->table('survey_question_choices')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('is_default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->create();

        $this->table('survey_questions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_unique', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('survey_responses')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('response', 'text', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('survey_rules')
            ->addColumn('survey_form_id', 'integer', [
                'comment' => 'links to survey_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['survey_form_id', 'survey_question_id'])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('dependent_question_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('show_options', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('enabled', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->create();

        $this->table('survey_status_periods')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('survey_status_id', 'integer', [
                'comment' => 'links to survey_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'survey_status_id',
                ]
            )
            ->create();

        $this->table('survey_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date_enabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_disabled', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('survey_form_id', 'integer', [
                'comment' => 'links to survey_forms.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_form_id',
                ]
            )
            ->create();

        $this->table('survey_table_columns')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->create();

        $this->table('survey_table_rows')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('survey_question_id', 'integer', [
                'comment' => 'links to survey_questions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'survey_question_id',
                ]
            )
            ->create();

        $this->table('system_authentications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('authentication_type_id', 'integer', [
                'comment' => 'links to authentication_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('allow_create_user', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('mapped_username', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('mapped_first_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('mapped_last_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('mapped_date_of_birth', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('mapped_gender', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('mapped_role', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addIndex(
                [
                    'authentication_type_id',
                ]
            )
            ->create();

        $this->table('system_errors')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('error_message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('request_method', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('request_url', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('referrer_url', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('client_ip', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('client_browser', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('triggered_from', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stack_trace', 'text', [
                'default' => null,
                'limit' => 4294967295,
                'null' => false,
            ])
            ->addColumn('server_info', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('system_patches')
            ->addColumn('issue', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => false,
            ])
            ->addPrimaryKey(['issue'])
            ->addColumn('version', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('system_processes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('process_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('callable_event', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '1 => New
2 => Running
3 => Completed
-1 => Abort
-2 => Error',
                'default' => null,
                'limit' => 2,
                'null' => false,
            ])
            ->addColumn('executed_count', 'integer', [
                'default' => '0',
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('start_date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'process_id',
                ]
            )
            ->create();

        $this->table('system_updates')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('version', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('date_released', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_approved', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('approved_by', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '1 -> Pending, 2 -> Approved',
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'version',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('textbook_conditions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('textbook_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('textbooks')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_period.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'academic_period_id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('author', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('publisher', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('year_published', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('ISBN', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('expiry_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_subject_id', 'integer', [
                'comment' => 'links to education_subjects.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'education_grade_id',
                ]
            )
            ->addIndex(
                [
                    'education_subject_id',
                ]
            )
            ->create();

        $this->table('training_achievement_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_course_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_courses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('objective', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('credit_hours', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('duration', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('number_of_months', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('training_field_of_study_id', 'integer', [
                'comment' => 'links to training_field_of_studies.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_course_type_id', 'integer', [
                'comment' => 'links to training_course_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_mode_of_delivery_id', 'integer', [
                'comment' => 'links to training_mode_deliveries.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_requirement_id', 'integer', [
                'comment' => 'links to training_requirements.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_level_id', 'integer', [
                'comment' => 'links to training_levels.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_field_of_study_id',
                ]
            )
            ->addIndex(
                [
                    'training_course_type_id',
                ]
            )
            ->addIndex(
                [
                    'training_mode_of_delivery_id',
                ]
            )
            ->addIndex(
                [
                    'training_requirement_id',
                ]
            )
            ->addIndex(
                [
                    'training_level_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
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
            ->create();

        $this->table('training_courses_prerequisites')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('prerequisite_training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'prerequisite_training_course_id',
                ]
            )
            ->create();

        $this->table('training_courses_providers')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_provider_id', 'integer', [
                'comment' => 'links to training_providers.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'training_provider_id',
                ]
            )
            ->create();

        $this->table('training_courses_result_types')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_result_type_id', 'integer', [
                'comment' => 'links to training_result_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'training_result_type_id',
                ]
            )
            ->create();

        $this->table('training_courses_specialisations')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_specialisation_id', 'integer', [
                'comment' => 'links to training_specialisations.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'training_specialisation_id',
                ]
            )
            ->create();

        $this->table('training_courses_target_populations')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('target_population_id', 'integer', [
                'comment' => 'links to staff_position_titles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'target_population_id',
                ]
            )
            ->create();

        $this->table('training_field_of_studies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_levels')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_mode_deliveries')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_need_categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_need_competencies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_need_standards')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_need_sub_standards')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('training_need_standard_id', 'integer', [
                'comment' => 'links to training_need_standards.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_priorities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_providers')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_requirements')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_result_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('training_session_results')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('training_session_id', 'integer', [
                'comment' => 'links to training_sessions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_session_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
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
            ->create();

        $this->table('training_session_trainee_results')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('result', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('training_result_type_id', 'integer', [
                'comment' => 'links to training_result_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('trainee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_session_id', 'integer', [
                'comment' => 'links to training_sessions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_result_type_id',
                ]
            )
            ->addIndex(
                [
                    'trainee_id',
                ]
            )
            ->addIndex(
                [
                    'training_session_id',
                ]
            )
            ->create();

        $this->table('training_session_trainers')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('training_session_id', 'integer', [
                'comment' => 'links to training_sessions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('trainer_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_session_id',
                ]
            )
            ->addIndex(
                [
                    'trainer_id',
                ]
            )
            ->create();

        $this->table('training_sessions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('training_course_id', 'integer', [
                'comment' => 'links to training_courses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('training_provider_id', 'integer', [
                'comment' => 'links to training_providers.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('area_id', 'integer', [
                'comment' => 'links to areas.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'training_course_id',
                ]
            )
            ->addIndex(
                [
                    'training_provider_id',
                ]
            )
            ->addIndex(
                [
                    'assignee_id',
                ]
            )
            ->addIndex(
                [
                    'status_id',
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
            ->create();

        $this->table('training_sessions_trainees')
            ->addColumn('training_session_id', 'integer', [
                'comment' => 'links to training_sessions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('trainee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['training_session_id', 'trainee_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '1 -> Active, 2 -> Withdrawn',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'training_session_id',
                ]
            )
            ->addIndex(
                [
                    'trainee_id',
                ]
            )
            ->create();

        $this->table('training_specialisations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('translations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('en', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('ar', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('zh', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('es', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('fr', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('ru', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('transport_features')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('transport_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->create();

        $this->table('trip_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('user_activities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('model_reference', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('field', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => false,
            ])
            ->addColumn('old_value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('new_value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('operation', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'model_reference',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('user_attachments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => false,
            ])
            ->addColumn('date_on_file', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_attachments_roles')
            ->addColumn('user_attachment_id', 'integer', [
                'comment' => 'links to user_attachments.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['user_attachment_id', 'security_role_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'id',
                ]
            )
            ->addIndex(
                [
                    'user_attachment_id',
                ]
            )
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->create();

        $this->table('user_awards')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('issue_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('award', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('issuer', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_bank_accounts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('account_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('account_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('active', 'integer', [
                'comment' => '1 is active, 0 is inactive',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('bank_branch_id', 'integer', [
                'comment' => 'links to bank_branches.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('remarks', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'bank_branch_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_body_masses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('height', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('weight', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('body_mass_index', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'academic_period_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_comments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_contacts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('contact_type_id', 'integer', [
                'comment' => 'links to contact_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('preferred', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'contact_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_health_allergies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('severe', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('health_allergy_type_id', 'integer', [
                'comment' => 'links to health_allergy_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'health_allergy_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_health_consultations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('treatment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('health_consultation_type_id', 'integer', [
                'comment' => 'links to health_consultation_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'health_consultation_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_health_families')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('current', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('health_relationship_id', 'integer', [
                'comment' => 'links to health_relationships.id',
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('health_condition_id', 'integer', [
                'comment' => 'links to health_conditions.id',
                'default' => null,
                'limit' => 6,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'health_relationship_id',
                ]
            )
            ->addIndex(
                [
                    'health_condition_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_health_histories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('current', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('health_condition_id', 'integer', [
                'comment' => 'links to health_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'health_condition_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_health_immunizations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('dosage', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('health_immunization_type_id', 'integer', [
                'comment' => 'links to health_immunization_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'health_immunization_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_health_medications')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('dosage', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_health_tests')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('result', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('health_test_type_id', 'integer', [
                'comment' => 'links to health_test_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'health_test_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_healths')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('blood_type', 'string', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('doctor_name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('doctor_contact', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('medical_facility', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('health_insurance', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_user_id',
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
            ->create();

        $this->table('user_identities')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('identity_type_id', 'integer', [
                'comment' => 'links to identity_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('issue_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('expiry_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('issue_location', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'identity_type_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->addIndex(
                [
                    'number',
                ]
            )
            ->addIndex(
                [
                    'number',
                ]
            )
            ->create();

        $this->table('user_languages')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('evaluation_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('language_id', 'integer', [
                'comment' => 'links to languages.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('listening', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('speaking', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('reading', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('writing', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'language_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_nationalities')
            ->addColumn('nationality_id', 'integer', [
                'comment' => 'links to nationalities.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['nationality_id', 'security_user_id'])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('preferred', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'nationality_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('user_special_needs')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('special_need_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('special_need_type_id', 'integer', [
                'comment' => 'links to special_need_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('special_need_difficulty_id', 'integer', [
                'comment' => 'links to special_need_difficulties.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'special_need_type_id',
                ]
            )
            ->addIndex(
                [
                    'special_need_difficulty_id',
                ]
            )
            ->addIndex(
                [
                    'security_user_id',
                ]
            )
            ->create();

        $this->table('utility_electricity_conditions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('utility_electricity_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('utility_internet_conditions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('utility_internet_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('utility_telephone_conditions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('utility_telephone_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
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
            ->create();

        $this->table('webhook_events')
            ->addColumn('webhook_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('event_key', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addPrimaryKey(['webhook_id', 'event_key'])
            ->create();

        $this->table('webhooks')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'comment' => '0 -> Inactive, 1 -> Active',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('method', 'string', [
                'comment' => 'POST -> HTTP Post Method, GET -> HTTP Get Method, PUT -> HTTP Put Method, DELETE -> HTTP Delete Method, PATCH -> HTTP Patch Method',
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('workflow_actions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('action', 'integer', [
                'comment' => '0 -> Approve, 1 -> Reject',
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comment_required', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('allow_by_assignee', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('event_key', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('workflow_step_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('next_workflow_step_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'allow_by_assignee',
                ]
            )
            ->addIndex(
                [
                    'next_workflow_step_id',
                ]
            )
            ->addIndex(
                [
                    'workflow_step_id',
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
            ->create();

        $this->table('workflow_comments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('workflow_record_id', 'integer', [
                'comment' => 'links to workflow_records.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'workflow_record_id',
                ]
            )
            ->create();

        $this->table('workflow_models')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('filter', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('is_school_based', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('workflow_rules')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('rule', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('feature', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('workflow_id', 'integer', [
                'comment' => 'links to workflows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'workflow_id',
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
            ->create();

        $this->table('workflow_statuses')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('is_editable', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_removable', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('workflow_model_id', 'integer', [
                'comment' => 'links to workflow_models.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'workflow_model_id',
                ]
            )
            ->create();

        $this->table('workflow_statuses_steps')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('workflow_status_id', 'integer', [
                'comment' => 'links to workflow_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('workflow_step_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'workflow_status_id',
                ]
            )
            ->addIndex(
                [
                    'workflow_step_id',
                ]
            )
            ->create();

        $this->table('workflow_steps')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('category', 'integer', [
                'comment' => '1 -> TO DO, 2 -> IN PROGRESS, 3 -> DONE',
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_editable', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_removable', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('is_system_defined', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('workflow_id', 'integer', [
                'comment' => 'links to workflows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'category',
                ]
            )
            ->addIndex(
                [
                    'workflow_id',
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
            ->create();

        $this->table('workflow_steps_roles')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('workflow_step_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('security_role_id', 'integer', [
                'comment' => 'links to security_roles.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'security_role_id',
                ]
            )
            ->addIndex(
                [
                    'workflow_step_id',
                ]
            )
            ->create();

        $this->table('workflow_transitions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('comment', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('prev_workflow_step_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('workflow_step_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('workflow_action_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('workflow_model_id', 'integer', [
                'comment' => 'links to workflow_models.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('model_reference', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'workflow_model_id',
                ]
            )
            ->addIndex(
                [
                    'model_reference',
                ]
            )
            ->addIndex(
                [
                    'created_user_id',
                ]
            )
            ->create();

        $this->table('workflows')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 60,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('workflow_model_id', 'integer', [
                'comment' => 'links to workflow_models.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'workflow_model_id',
                ]
            )
            ->create();

        $this->table('workflows_filters')
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('workflow_id', 'integer', [
                'comment' => 'links to workflows.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('filter_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'filter_id',
                ]
            )
            ->addIndex(
                [
                    'workflow_id',
                ]
            )
            ->create();

        $this->table('locale_contents')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('en', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();



        $table = $this->table('themes')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('default_value', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('default_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
             ->create();
    }

    public function down()
    {
        $this->dropTable('absence_types');
        $this->dropTable('academic_period_levels');
        $this->dropTable('academic_periods');
        $this->dropTable('alert_logs');
        $this->dropTable('alert_rules');
        $this->dropTable('alerts');
        $this->dropTable('alerts_roles');
        $this->dropTable('api_authorizations');
        $this->dropTable('api_credentials');
        $this->dropTable('area_administrative_levels');
        $this->dropTable('area_administratives');
        $this->dropTable('area_levels');
        $this->dropTable('areas');
        $this->dropTable('assessment_grading_options');
        $this->dropTable('assessment_grading_types');
        $this->dropTable('assessment_item_results');
        $this->dropTable('assessment_items');
        $this->dropTable('assessment_items_grading_types');
        $this->dropTable('assessment_periods');
        $this->dropTable('assessments');
        $this->dropTable('authentication_types');
        $this->dropTable('bank_branches');
        $this->dropTable('banks');
        $this->dropTable('behaviour_classifications');
        $this->dropTable('building_custom_field_values');
        $this->dropTable('building_types');
        $this->dropTable('bus_types');
        $this->dropTable('comment_types');
        $this->dropTable('competencies');
        $this->dropTable('competency_criterias');
        $this->dropTable('competency_grading_options');
        $this->dropTable('competency_grading_types');
        $this->dropTable('competency_items');
        $this->dropTable('competency_items_periods');
        $this->dropTable('competency_periods');
        $this->dropTable('competency_sets');
        $this->dropTable('competency_sets_competencies');
        $this->dropTable('competency_templates');
        $this->dropTable('config_attachments');
        $this->dropTable('config_item_options');
        $this->dropTable('config_items');
        $this->dropTable('config_product_lists');
        $this->dropTable('contact_options');
        $this->dropTable('contact_types');
        $this->dropTable('countries');
        $this->dropTable('custom_field_options');
        $this->dropTable('custom_field_types');
        $this->dropTable('custom_field_values');
        $this->dropTable('custom_fields');
        $this->dropTable('custom_forms');
        $this->dropTable('custom_forms_fields');
        $this->dropTable('custom_forms_filters');
        $this->dropTable('custom_modules');
        $this->dropTable('custom_records');
        $this->dropTable('custom_table_cells');
        $this->dropTable('custom_table_columns');
        $this->dropTable('custom_table_rows');
        $this->dropTable('deleted_records');
        $this->dropTable('education_certifications');
        $this->dropTable('education_cycles');
        $this->dropTable('education_field_of_studies');
        $this->dropTable('education_grades');
        $this->dropTable('education_grades_subjects');
        $this->dropTable('education_level_isced');
        $this->dropTable('education_levels');
        $this->dropTable('education_programme_orientations');
        $this->dropTable('education_programmes');
        $this->dropTable('education_programmes_next_programmes');
        $this->dropTable('education_stages');
        $this->dropTable('education_subjects');
        $this->dropTable('education_subjects_field_of_studies');
        $this->dropTable('education_systems');
        $this->dropTable('employment_types');
        $this->dropTable('examination_centre_rooms');
        $this->dropTable('examination_centre_rooms_examinations');
        $this->dropTable('examination_centre_rooms_examinations_invigilators');
        $this->dropTable('examination_centre_rooms_examinations_students');
        $this->dropTable('examination_centre_special_needs');
        $this->dropTable('examination_centres');
        $this->dropTable('examination_centres_examinations');
        $this->dropTable('examination_centres_examinations_institutions');
        $this->dropTable('examination_centres_examinations_invigilators');
        $this->dropTable('examination_centres_examinations_students');
        $this->dropTable('examination_centres_examinations_subjects');
        $this->dropTable('examination_centres_examinations_subjects_students');
        $this->dropTable('examination_grading_options');
        $this->dropTable('examination_grading_types');
        $this->dropTable('examination_item_results');
        $this->dropTable('examination_items');
        $this->dropTable('examinations');
        $this->dropTable('external_data_source_attributes');
        $this->dropTable('extracurricular_types');
        $this->dropTable('fee_types');
        $this->dropTable('floor_custom_field_values');
        $this->dropTable('floor_types');
        $this->dropTable('genders');
        $this->dropTable('guardian_relations');
        $this->dropTable('guidance_types');
        $this->dropTable('health_allergy_types');
        $this->dropTable('health_conditions');
        $this->dropTable('health_consultation_types');
        $this->dropTable('health_immunization_types');
        $this->dropTable('health_relationships');
        $this->dropTable('health_test_types');
        $this->dropTable('identity_types');
        $this->dropTable('idp_google');
        $this->dropTable('idp_oauth');
        $this->dropTable('idp_saml');
        $this->dropTable('import_mapping');
        $this->dropTable('indexes');
        $this->dropTable('indexes_criterias');
        $this->dropTable('infrastructure_conditions');
        $this->dropTable('infrastructure_custom_field_options');
        $this->dropTable('infrastructure_custom_fields');
        $this->dropTable('infrastructure_custom_forms');
        $this->dropTable('infrastructure_custom_forms_fields');
        $this->dropTable('infrastructure_custom_forms_filters');
        $this->dropTable('infrastructure_levels');
        $this->dropTable('infrastructure_need_types');
        $this->dropTable('infrastructure_needs');
        $this->dropTable('infrastructure_ownerships');
        $this->dropTable('infrastructure_project_funding_sources');
        $this->dropTable('infrastructure_projects');
        $this->dropTable('infrastructure_projects_needs');
        $this->dropTable('infrastructure_statuses');
        $this->dropTable('infrastructure_utility_electricities');
        $this->dropTable('infrastructure_utility_internets');
        $this->dropTable('infrastructure_utility_telephones');
        $this->dropTable('infrastructure_wash_water_accessibilities');
        $this->dropTable('infrastructure_wash_water_functionalities');
        $this->dropTable('infrastructure_wash_water_proximities');
        $this->dropTable('infrastructure_wash_water_qualities');
        $this->dropTable('infrastructure_wash_water_quantities');
        $this->dropTable('infrastructure_wash_water_types');
        $this->dropTable('infrastructure_wash_waters');
        $this->dropTable('institution_activities');
        $this->dropTable('institution_attachments');
        $this->dropTable('institution_bank_accounts');
        $this->dropTable('institution_buildings');
        $this->dropTable('institution_buses');
        $this->dropTable('institution_buses_transport_features');
        $this->dropTable('institution_case_records');
        $this->dropTable('institution_cases');
        $this->dropTable('institution_class_grades');
        $this->dropTable('institution_class_students');
        $this->dropTable('institution_class_subjects');
        $this->dropTable('institution_classes');
        $this->dropTable('institution_competency_item_comments');
        $this->dropTable('institution_competency_period_comments');
        $this->dropTable('institution_competency_results');
        $this->dropTable('institution_counsellings');
        $this->dropTable('institution_custom_field_options');
        $this->dropTable('institution_custom_field_values');
        $this->dropTable('institution_custom_fields');
        $this->dropTable('institution_custom_forms');
        $this->dropTable('institution_custom_forms_fields');
        $this->dropTable('institution_custom_forms_filters');
        $this->dropTable('institution_custom_table_cells');
        $this->dropTable('institution_custom_table_columns');
        $this->dropTable('institution_custom_table_rows');
        $this->dropTable('institution_fee_types');
        $this->dropTable('institution_fees');
        $this->dropTable('institution_floors');
        $this->dropTable('institution_genders');
        $this->dropTable('institution_grades');
        $this->dropTable('institution_indexes');
        $this->dropTable('institution_lands');
        $this->dropTable('institution_localities');
        $this->dropTable('institution_network_connectivities');
        $this->dropTable('institution_ownerships');
        $this->dropTable('institution_positions');
        $this->dropTable('institution_providers');
        $this->dropTable('institution_quality_rubric_answers');
        $this->dropTable('institution_quality_rubrics');
        $this->dropTable('institution_quality_visits');
        $this->dropTable('institution_repeater_survey_answers');
        $this->dropTable('institution_repeater_survey_table_cells');
        $this->dropTable('institution_repeater_surveys');
        $this->dropTable('institution_rooms');
        $this->dropTable('institution_sectors');
        $this->dropTable('institution_shifts');
        $this->dropTable('institution_staff');
        $this->dropTable('institution_staff_absences');
        $this->dropTable('institution_staff_assignments');
        $this->dropTable('institution_staff_leave');
        $this->dropTable('institution_staff_position_profiles');
        $this->dropTable('institution_statuses');
        $this->dropTable('institution_student_absences');
        $this->dropTable('institution_student_admission');
        $this->dropTable('institution_student_indexes');
        $this->dropTable('institution_student_survey_answers');
        $this->dropTable('institution_student_survey_table_cells');
        $this->dropTable('institution_student_surveys');
        $this->dropTable('institution_student_withdraw');
        $this->dropTable('institution_students');
        $this->dropTable('institution_students_report_cards');
        $this->dropTable('institution_students_report_cards_comments');
        $this->dropTable('institution_students_tmp');
        $this->dropTable('institution_subject_staff');
        $this->dropTable('institution_subject_students');
        $this->dropTable('institution_subjects');
        $this->dropTable('institution_subjects_rooms');
        $this->dropTable('institution_survey_answers');
        $this->dropTable('institution_survey_table_cells');
        $this->dropTable('institution_surveys');
        $this->dropTable('institution_textbooks');
        $this->dropTable('institution_transport_providers');
        $this->dropTable('institution_trip_days');
        $this->dropTable('institution_trip_passengers');
        $this->dropTable('institution_trips');
        $this->dropTable('institution_types');
        $this->dropTable('institution_visit_requests');
        $this->dropTable('institutions');
        $this->dropTable('labels');
        $this->dropTable('land_custom_field_values');
        $this->dropTable('land_types');
        $this->dropTable('languages');
        $this->dropTable('license_classifications');
        $this->dropTable('license_types');
        $this->dropTable('nationalities');
        $this->dropTable('notices');
        $this->dropTable('qualification_levels');
        $this->dropTable('qualification_specialisations');
        $this->dropTable('qualification_titles');
        $this->dropTable('quality_visit_types');
        $this->dropTable('report_card_comment_codes');
        $this->dropTable('report_card_subjects');
        $this->dropTable('report_cards');
        $this->dropTable('report_progress');
        $this->dropTable('reports');
        $this->dropTable('room_custom_field_values');
        $this->dropTable('room_types');
        $this->dropTable('rubric_criteria_options');
        $this->dropTable('rubric_criterias');
        $this->dropTable('rubric_sections');
        $this->dropTable('rubric_status_periods');
        $this->dropTable('rubric_status_programmes');
        $this->dropTable('rubric_status_roles');
        $this->dropTable('rubric_statuses');
        $this->dropTable('rubric_template_options');
        $this->dropTable('rubric_templates');
        $this->dropTable('salary_addition_types');
        $this->dropTable('salary_deduction_types');
        $this->dropTable('security_functions');
        $this->dropTable('security_group_areas');
        $this->dropTable('security_group_institutions');
        $this->dropTable('security_group_users');
        $this->dropTable('security_groups');
        $this->dropTable('security_rest_sessions');
        $this->dropTable('security_role_functions');
        $this->dropTable('security_roles');
        $this->dropTable('security_user_logins');
        $this->dropTable('security_user_sessions');
        $this->dropTable('security_users');
        $this->dropTable('shift_options');
        $this->dropTable('single_logout');
        $this->dropTable('special_need_difficulties');
        $this->dropTable('special_need_types');
        $this->dropTable('staff_absence_reasons');
        $this->dropTable('staff_appraisal_types');
        $this->dropTable('staff_appraisals');
        $this->dropTable('staff_appraisals_competencies');
        $this->dropTable('staff_behaviour_categories');
        $this->dropTable('staff_behaviours');
        $this->dropTable('staff_change_types');
        $this->dropTable('staff_custom_field_options');
        $this->dropTable('staff_custom_field_values');
        $this->dropTable('staff_custom_fields');
        $this->dropTable('staff_custom_forms');
        $this->dropTable('staff_custom_forms_fields');
        $this->dropTable('staff_custom_table_cells');
        $this->dropTable('staff_custom_table_columns');
        $this->dropTable('staff_custom_table_rows');
        $this->dropTable('staff_employments');
        $this->dropTable('staff_extracurriculars');
        $this->dropTable('staff_leave_types');
        $this->dropTable('staff_licenses');
        $this->dropTable('staff_licenses_classifications');
        $this->dropTable('staff_memberships');
        $this->dropTable('staff_position_grades');
        $this->dropTable('staff_position_titles');
        $this->dropTable('staff_qualifications');
        $this->dropTable('staff_qualifications_specialisations');
        $this->dropTable('staff_qualifications_subjects');
        $this->dropTable('staff_salaries');
        $this->dropTable('staff_salary_additions');
        $this->dropTable('staff_salary_deductions');
        $this->dropTable('staff_statuses');
        $this->dropTable('staff_training_applications');
        $this->dropTable('staff_training_categories');
        $this->dropTable('staff_training_needs');
        $this->dropTable('staff_training_self_studies');
        $this->dropTable('staff_training_self_study_attachments');
        $this->dropTable('staff_training_self_study_results');
        $this->dropTable('staff_trainings');
        $this->dropTable('staff_types');
        $this->dropTable('student_absence_reasons');
        $this->dropTable('student_behaviour_categories');
        $this->dropTable('student_behaviours');
        $this->dropTable('student_custom_field_options');
        $this->dropTable('student_custom_field_values');
        $this->dropTable('student_custom_fields');
        $this->dropTable('student_custom_forms');
        $this->dropTable('student_custom_forms_fields');
        $this->dropTable('student_custom_table_cells');
        $this->dropTable('student_custom_table_columns');
        $this->dropTable('student_custom_table_rows');
        $this->dropTable('student_extracurriculars');
        $this->dropTable('student_fees');
        $this->dropTable('student_guardians');
        $this->dropTable('student_indexes_criterias');
        $this->dropTable('student_statuses');
        $this->dropTable('student_transfer_reasons');
        $this->dropTable('student_withdraw_reasons');
        $this->dropTable('survey_forms');
        $this->dropTable('survey_forms_questions');
        $this->dropTable('survey_question_choices');
        $this->dropTable('survey_questions');
        $this->dropTable('survey_responses');
        $this->dropTable('survey_rules');
        $this->dropTable('survey_status_periods');
        $this->dropTable('survey_statuses');
        $this->dropTable('survey_table_columns');
        $this->dropTable('survey_table_rows');
        $this->dropTable('system_authentications');
        $this->dropTable('system_errors');
        $this->dropTable('system_patches');
        $this->dropTable('system_processes');
        $this->dropTable('system_updates');
        $this->dropTable('textbook_conditions');
        $this->dropTable('textbook_statuses');
        $this->dropTable('textbooks');
        $this->dropTable('training_achievement_types');
        $this->dropTable('training_course_types');
        $this->dropTable('training_courses');
        $this->dropTable('training_courses_prerequisites');
        $this->dropTable('training_courses_providers');
        $this->dropTable('training_courses_result_types');
        $this->dropTable('training_courses_specialisations');
        $this->dropTable('training_courses_target_populations');
        $this->dropTable('training_field_of_studies');
        $this->dropTable('training_levels');
        $this->dropTable('training_mode_deliveries');
        $this->dropTable('training_need_categories');
        $this->dropTable('training_need_competencies');
        $this->dropTable('training_need_standards');
        $this->dropTable('training_need_sub_standards');
        $this->dropTable('training_priorities');
        $this->dropTable('training_providers');
        $this->dropTable('training_requirements');
        $this->dropTable('training_result_types');
        $this->dropTable('training_session_results');
        $this->dropTable('training_session_trainee_results');
        $this->dropTable('training_session_trainers');
        $this->dropTable('training_sessions');
        $this->dropTable('training_sessions_trainees');
        $this->dropTable('training_specialisations');
        $this->dropTable('translations');
        $this->dropTable('transport_features');
        $this->dropTable('transport_statuses');
        $this->dropTable('trip_types');
        $this->dropTable('user_activities');
        $this->dropTable('user_attachments');
        $this->dropTable('user_attachments_roles');
        $this->dropTable('user_awards');
        $this->dropTable('user_bank_accounts');
        $this->dropTable('user_body_masses');
        $this->dropTable('user_comments');
        $this->dropTable('user_contacts');
        $this->dropTable('user_health_allergies');
        $this->dropTable('user_health_consultations');
        $this->dropTable('user_health_families');
        $this->dropTable('user_health_histories');
        $this->dropTable('user_health_immunizations');
        $this->dropTable('user_health_medications');
        $this->dropTable('user_health_tests');
        $this->dropTable('user_healths');
        $this->dropTable('user_identities');
        $this->dropTable('user_languages');
        $this->dropTable('user_nationalities');
        $this->dropTable('user_special_needs');
        $this->dropTable('utility_electricity_conditions');
        $this->dropTable('utility_electricity_types');
        $this->dropTable('utility_internet_conditions');
        $this->dropTable('utility_internet_types');
        $this->dropTable('utility_telephone_conditions');
        $this->dropTable('utility_telephone_types');
        $this->dropTable('webhook_events');
        $this->dropTable('webhooks');
        $this->dropTable('workflow_actions');
        $this->dropTable('workflow_comments');
        $this->dropTable('workflow_models');
        $this->dropTable('workflow_rules');
        $this->dropTable('workflow_statuses');
        $this->dropTable('workflow_statuses_steps');
        $this->dropTable('workflow_steps');
        $this->dropTable('workflow_steps_roles');
        $this->dropTable('workflow_transitions');
        $this->dropTable('workflows');
        $this->dropTable('workflows_filters');
    }
}
