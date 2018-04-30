<?php

use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;

class POCOR2098 extends AbstractMigration
{
    public function up()
    {
        $SurveyFormsFilters = $this->table('survey_forms_filters', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains groups of surveys by filter types'
        ]);
        $SurveyFormsFilters
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('survey_form_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_forms.id'
            ])
            ->addColumn('survey_filter_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->save();

        $allFilterTypeId = 0;
        $surveyRows = $this->fetchAll('
            SELECT `survey_forms`.`id` FROM `survey_forms`
            INNER JOIN `custom_modules`
            ON `survey_forms`.`custom_module_id` = `custom_modules`.`id`
            WHERE `custom_modules`.`model` = "Institution.Institutions"
        ');

        $data = [];
        foreach ($surveyRows as $obj) {
            $data[] = [
                'id' => Text::uuid(),
                'survey_form_id' => $obj['id'],
                'survey_filter_id' => $allFilterTypeId
            ];
        }

        if (!empty($data)) {
            $this->insert('survey_forms_filters', $data);
        }
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `survey_forms_filters`');
    }
}

