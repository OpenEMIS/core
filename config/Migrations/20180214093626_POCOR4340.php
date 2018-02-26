<?php
use Migrations\AbstractMigration;

class POCOR4340 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('appraisal_criterias', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of staff appraisal criterias'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('field_type_id', 'integer', [
                'null' => false,
                'comment' => 'links to field_types.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('field_type_id')
            ->save();

        $table = $this->table('appraisal_forms', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of staff appraisal forms'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->save();

        $table = $this->table('appraisal_forms_criterias', [
            'id' => false,
            'primary_key' => ['id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of criterias linked to a specific staff appraisal form'
        ]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('section', 'string', [
                'null' => true,
                'limit' => 250
            ])
            ->addColumn('order', 'integer', [
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->save();

        $table = $this->table('appraisal_periods', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of periods for a specific staff appraisal form'
        ]);
        $table
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('date_enabled', 'date', [
                'null' => false
            ])
            ->addColumn('date_disabled', 'date', [
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('academic_period_id')
            ->save();

        $table = $this->table('appraisal_periods_types', [
            'id' => false,
            'primary_key' => ['appraisal_period_id', 'appraisal_type_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of appraisal types linked to a specific staff appraisal period'
        ]);
        $table
            ->addColumn('appraisal_period_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_periods.id'
            ])
            ->addColumn('appraisal_type_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_types.id'
            ])
            ->addIndex('appraisal_period_id')
            ->addIndex('appraisal_type_id')
            ->save();

        $table = $this->table('appraisal_slider_answers', [
            'id' => false,
            'primary_key' => ['appraisal_forms_criteria_id', 'institution_staff_appraisal_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the slider answers recorded for a specific institution staff appraisal'
        ]);
        $table
            ->addColumn('appraisal_forms_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms_criterias.id'
            ])
            ->addColumn('institution_staff_appraisal_id', 'integer', [
                'null' => false,
                'comment' => 'links to institution_staff_appraisals.id'
            ])
            ->addColumn('answer', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_forms_criteria_id')
            ->addIndex('institution_staff_appraisal_id')
            ->save();

        $table = $this->table('appraisal_sliders', [
            'id' => false,
            'primary_key' => ['appraisal_criteria_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the slider configurations set for a specific staff appraisal criteria'
        ]);
        $table
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('min', 'decimal', [
                'null' => false,
                'precision' => 5,
                'scale' => 2
            ])
            ->addColumn('max', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->addColumn('step', 'decimal', [
                'precision' => 3,
                'scale' => 2,
                'null' => false
            ])
            ->addIndex('appraisal_criteria_id')
            ->save();

        $table = $this->table('appraisal_text_answers', [
            'id' => false,
            'primary_key' => ['appraisal_forms_criteria_id', 'institution_staff_appraisal_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the text answers recorded for a specific institution staff appraisal'
        ]);
        $table
            ->addColumn('appraisal_forms_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms_criterias.id'
            ])
            ->addColumn('institution_staff_appraisal_id', 'integer', [
                'null' => false,
                'comment' => 'links to institution_staff_appraisals.id'
            ])
            ->addColumn('answer', 'text', [
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_forms_criteria_id')
            ->addIndex('institution_staff_appraisal_id')
            ->save();

        $table = $this->table('appraisal_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of staff appraisal types'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->save();

        $table = $this->table('institution_staff_appraisals', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of appraisals for a specific staff'
        ]);
        $table
            ->addColumn('title', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('from', 'date', [
                'null' => false
            ])
            ->addColumn('to', 'date', [
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250
            ])
            ->addColumn('file_content', 'blob', [
                'null' => true,
                'limit' => 4294967295
            ])
            ->addColumn('comment', 'text', [
                'null' => true
            ])
            ->addColumn('institution_id', 'integer', [
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('staff_id', 'integer', [
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('appraisal_type_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_types.id'
            ])
            ->addColumn('appraisal_period_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_periods.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('institution_id')
            ->addIndex('staff_id')
            ->addIndex('appraisal_type_id')
            ->addIndex('appraisal_period_id')
            ->save();

        $table = $this->table('field_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of available custom field types that are used in the system'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->save();

        $table
            ->insert([
                [
                    'code' => 'TEXT',
                    'name' => 'Multi-line Text Input'
                ],
                [
                    'code' => 'SLIDER',
                    'name' => 'Slider Range Input'
                ]
            ])
            ->save();

        $this->table('competency_sets')->rename('z_4340_competency_sets');
        $this->table('competencies')->rename('z_4340_competencies');
        $this->table('competency_sets_competencies')->rename('z_4340_competency_sets_competencies');
        $this->table('staff_appraisals')->rename('z_4340_staff_appraisals');
        $this->table('staff_appraisals_competencies')->rename('z_4340_staff_appraisals_competencies');
        $this->table('staff_appraisal_types')->rename('z_4340_staff_appraisal_types');
    }

    public function down()
    {
        $this->dropTable('appraisal_criterias');
        $this->dropTable('appraisal_forms');
        $this->dropTable('appraisal_forms_criterias');
        $this->dropTable('appraisal_periods');
        $this->dropTable('appraisal_periods_types');
        $this->dropTable('appraisal_slider_answers');
        $this->dropTable('appraisal_text_answers');
        $this->dropTable('appraisal_types');
        $this->dropTable('institution_staff_appraisals');
        $this->dropTable('field_types');
        $this->dropTable('appraisal_sliders');

        $this->table('z_4340_competency_sets')->rename('competency_sets');
        $this->table('z_4340_competencies')->rename('competencies');
        $this->table('z_4340_competency_sets_competencies')->rename('competency_sets_competencies');
        $this->table('z_4340_staff_appraisals')->rename('staff_appraisals');
        $this->table('z_4340_staff_appraisals_competencies')->rename('staff_appraisals_competencies');
        $this->table('z_4340_staff_appraisal_types')->rename('staff_appraisal_types');
    }
}
