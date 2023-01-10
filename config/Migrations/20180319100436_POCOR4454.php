<?php

use Phinx\Migration\AbstractMigration;

class POCOR4454 extends AbstractMigration
{
    public function up()
    {
        // field_types
        $fieldTypesData = [
            [
                'code' => 'DROPDOWN',
                'name' => 'Dropdown'
            ]
        ];
        $this->insert('field_types', $fieldTypesData);

        // appraisal_forms_criterias
        $this->table('appraisal_forms_criterias')
            ->addColumn('is_mandatory', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0,
                'after' => 'section'
            ])
            ->save();

        // appraisal_dropdown_options
        $this->table('appraisal_dropdown_options', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the dropdown options set for a specific staff appraisal criteria'
            ])
            ->addColumn('name', 'string', [
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('is_default', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_criteria_id')
            ->save();

        // appraisal_dropdown_answers
        $this->table('appraisal_dropdown_answers', [
                'id' => false,
                'primary_key' => ['appraisal_form_id', 'appraisal_criteria_id', 'institution_staff_appraisal_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the dropdown answers recorded for a specific institution staff appraisal'
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('institution_staff_appraisal_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_staff_appraisals.id'
            ])
            ->addColumn('answer', 'integer', [
                'limit' => 11,
                'null' => true,
                'comment' => 'links to appraisal_dropdown_options.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->addIndex('institution_staff_appraisal_id')
            ->addIndex('answer')
            ->save();

        // appraisal_slider_answers
        $this->table('appraisal_slider_answers')
            ->changeColumn('answer', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => true
            ])
            ->save();

        // appraisal_text_answers
        $this->table('appraisal_text_answers')
            ->changeColumn('answer', 'text', [
                'null' => true
            ])
            ->save();

        // institution_staff_appraisals
        $this->table('institution_staff_appraisals')
            ->addColumn('appraisal_form_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_forms.id',
                'after' => 'staff_id'
            ])
            ->addIndex('appraisal_form_id')
            ->save();
        $this->execute("UPDATE `institution_staff_appraisals`
            SET `appraisal_form_id` = (
                SELECT `appraisal_form_id`
                FROM `appraisal_periods`
                WHERE `appraisal_periods`.`id` = `institution_staff_appraisals`.`appraisal_period_id`
            )");
    }

    public function down()
    {
        $this->execute("DELETE FROM `field_types` WHERE `code` = 'DROPDOWN'");
        $this->table('appraisal_forms_criterias')->removeColumn('is_mandatory');
        $this->dropTable('appraisal_dropdown_options');
        $this->dropTable('appraisal_dropdown_answers');
        $this->table('appraisal_slider_answers')
            ->changeColumn('answer', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->save();
        $this->table('appraisal_text_answers')
            ->changeColumn('answer', 'text', [
                'null' => false
            ])
            ->save();
        $this->table('institution_staff_appraisals')
            ->removeColumn('appraisal_form_id')
            ->save();
    }
}
