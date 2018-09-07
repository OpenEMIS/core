<?php

use Phinx\Migration\AbstractMigration;

class POCOR4741 extends AbstractMigration
{
    public function up()
    {
        $fieldData = [
            'id' => 5,
            'code' => 'SCORE',
            'name' => 'Score'
        ];

        $this->table('field_types')->insert($fieldData)->save();

        $this->table('appraisal_forms_criterias_scores', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains all the appraisal forms criterias scores',
                'id' => false, 
                'primary_key' => [
                    'appraisal_form_id', 
                    'appraisal_criteria_id'
                ]
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('final_score', 'integer', [
                'default' => null,
                'limit' => 2,
                'null' => false
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'null' => true
            ])

            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->table('appraisal_forms_criterias_scores_links', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the appraisal forms criterias scores linkage',
            'id' => false, 
            'primary_key' => [
                'appraisal_form_id', 
                'appraisal_criteria_id',
                'appraisal_criteria_linked_id'
            ]
        ])
        ->addColumn('appraisal_form_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
            'comment' => 'links to appraisal_forms.id'
        ])
        ->addColumn('appraisal_criteria_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
            'comment' => 'links to appraisal_criterias.id'
        ])
        ->addColumn('appraisal_criteria_linked_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
            'comment' => 'links to appraisal_criterias.id'
        ])
        ->addIndex('appraisal_form_id')
        ->addIndex('appraisal_criteria_id')
        ->addIndex('appraisal_criteria_linked_id')
        ->save();

        $this->table('appraisal_score_answers', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the appraisal scores answers',
            'id' => false, 
            'primary_key' => [
                'appraisal_form_id', 
                'appraisal_criteria_id',
                'institution_staff_appraisal_id'
            ]
        ])
        ->addColumn('appraisal_form_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
            'comment' => 'links to appraisal_forms.id'
        ])
        ->addColumn('appraisal_criteria_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
            'comment' => 'links to appraisal_criterias.id'
        ])
        ->addColumn('institution_staff_appraisal_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('answer', 'decimal', [
            'default' => null,
            'null' => true,
            'precision' => 10,
            'scale' => 2
        ])

        ->addColumn('modified_user_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => true
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true
        ])
        ->addColumn('created_user_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false
        ])

        ->addIndex('appraisal_form_id')
        ->addIndex('appraisal_criteria_id')
        ->addIndex('institution_staff_appraisal_id')
        ->addIndex('modified_user_id')
        ->addIndex('created_user_id')
        ->save();


        // create backup for security_functions     
        $this->execute('CREATE TABLE `z_4741_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4741_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `_view` = "Forms.index|Forms.view|Scores.index|Scores.view", `_edit` = "Forms.edit|Scores.edit" WHERE `id` = ' . 5086);
    }

    public function down()
    {
        $this->dropTable('appraisal_forms_criterias_scores');
        $this->dropTable('appraisal_forms_criterias_scores_links');
        $this->dropTable('appraisal_score_answers');
        $this->execute('DELETE from `field_types` WHERE code = "SCORE"');

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4741_security_functions` TO `security_functions`');
    }
}
