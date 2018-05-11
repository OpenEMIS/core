<?php

use Phinx\Migration\AbstractMigration;

class POCOR4547 extends AbstractMigration
{
    public function up()
    {
        // field_types
        $fieldTypesData = [
            [
                'code' => 'NUMBER',
                'name' => 'Number'
            ]
        ];
        $this->insert('field_types', $fieldTypesData);

        // appraisal_numbers
        $AppraisalNumbers = $this->table('appraisal_numbers', [
            'id' => false,
            'primary_key' => ['appraisal_criteria_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the number validation set for a specific staff appraisal criteria'
        ]);
        
        $AppraisalNumbers
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criteria.id'
            ])
            ->addColumn('min_inclusive', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('max_inclusive', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('min_exclusive', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('max_exclusive', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('validation_rule', 'string', [
                 'default' => null,
                 'limit' => 50,
                 'null' => true
              ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => null
            ])
            ->addIndex('appraisal_criteria_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // appraisal_number_answers
        $AppraisalNumberAnswers = $this->table('appraisal_number_answers', [
            'id' => false,
            'primary_key' => ['appraisal_form_id', 'appraisal_criteria_id', 'institution_staff_appraisal_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the number answers recorded for a specific institution staff appraisal'
        ]);

        $AppraisalNumberAnswers
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
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => null
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->addIndex('institution_staff_appraisal_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->execute("DELETE FROM `field_types` WHERE `code` = 'NUMBER'");
        $this->dropTable('appraisal_numbers');
        $this->dropTable('appraisal_number_answers');
    }
}
