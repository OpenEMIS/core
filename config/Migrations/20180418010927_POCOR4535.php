<?php

use Phinx\Migration\AbstractMigration;

class POCOR4535 extends AbstractMigration
{
    public function up()
    {
        $this->table("custom_table_cells")->rename("z_4535_custom_table_cells");
        $this->table("institution_custom_table_cells")->rename("z_4535_institution_custom_table_cells");
        $this->table("institution_repeater_survey_table_cells")->rename("z_4535_institution_repeater_survey_table_cells");
        $this->table("institution_student_survey_table_cells")->rename("z_4535_institution_student_survey_table_cells");
        $this->table("institution_survey_table_cells")->rename("z_4535_institution_survey_table_cells");
        $this->table("staff_custom_table_cells")->rename("z_4535_staff_custom_table_cells");
        $this->table("student_custom_table_cells")->rename("z_4535_student_custom_table_cells");

        // custom_table_cells
        $this->table('custom_table_cells', [
                'id' => false,
                'primary_key' => [
                    'custom_field_id',
                    'custom_table_column_id',
                    'custom_table_row_id',
                    'custom_record_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('custom_field_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to custom_fields.id'
            ])
            ->addColumn('custom_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to custom_table_columns.id'
            ])
            ->addColumn('custom_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to custom_table_rows.id'
            ])
            ->addColumn('custom_record_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to custom_records.id'
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
            ->addIndex('custom_field_id')
            ->addIndex('custom_table_column_id')
            ->addIndex('custom_table_row_id')
            ->addIndex('custom_record_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_custom_table_cells
        $this->table('institution_custom_table_cells', [
                'id' => false,
                'primary_key' => [
                    'institution_custom_field_id',
                    'institution_custom_table_column_id',
                    'institution_custom_table_row_id',
                    'institution_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('institution_custom_field_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_custom_fields.id'
            ])
            ->addColumn('institution_custom_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_custom_table_columns.id'
            ])
            ->addColumn('institution_custom_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_custom_table_rows.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
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
            ->addIndex('institution_custom_field_id')
            ->addIndex('institution_custom_table_column_id')
            ->addIndex('institution_custom_table_row_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_repeater_survey_table_cells
        $this->table('institution_repeater_survey_table_cells', [
                'id' => false,
                'primary_key' => [
                    'survey_question_id',
                    'survey_table_column_id',
                    'survey_table_row_id',
                    'institution_repeater_survey_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('survey_question_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_questions.id'
            ])
            ->addColumn('survey_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_table_columns.id'
            ])
            ->addColumn('survey_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_table_rows.id'
            ])
            ->addColumn('institution_repeater_survey_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_repeater_surveys.id'
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
            ->addIndex('survey_question_id')
            ->addIndex('survey_table_column_id')
            ->addIndex('survey_table_row_id')
            ->addIndex('institution_repeater_survey_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_student_survey_table_cells
        $this->table('institution_student_survey_table_cells', [
                'id' => false,
                'primary_key' => [
                    'survey_question_id',
                    'survey_table_column_id',
                    'survey_table_row_id',
                    'institution_student_survey_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('survey_question_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_questions.id'
            ])
            ->addColumn('survey_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_table_columns.id'
            ])
            ->addColumn('survey_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_table_rows.id'
            ])
            ->addColumn('institution_student_survey_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_student_surveys.id'
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
            ->addIndex('survey_question_id')
            ->addIndex('survey_table_column_id')
            ->addIndex('survey_table_row_id')
            ->addIndex('institution_student_survey_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_survey_table_cells
        $this->table('institution_survey_table_cells', [
                'id' => false,
                'primary_key' => [
                    'survey_question_id',
                    'survey_table_column_id',
                    'survey_table_row_id',
                    'institution_survey_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('survey_question_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_questions.id'
            ])
            ->addColumn('survey_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_table_columns.id'
            ])
            ->addColumn('survey_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to survey_table_rows.id'
            ])
            ->addColumn('institution_survey_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_surveys.id'
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
            ->addIndex('survey_question_id')
            ->addIndex('survey_table_column_id')
            ->addIndex('survey_table_row_id')
            ->addIndex('institution_survey_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // staff_custom_table_cells
        $this->table('staff_custom_table_cells', [
                'id' => false,
                'primary_key' => [
                    'staff_custom_field_id',
                    'staff_custom_table_column_id',
                    'staff_custom_table_row_id',
                    'staff_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('staff_custom_field_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to staff_custom_fields.id'
            ])
            ->addColumn('staff_custom_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to staff_custom_table_columns.id'
            ])
            ->addColumn('staff_custom_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to staff_custom_table_rows.id'
            ])
            ->addColumn('staff_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
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
            ->addIndex('staff_custom_field_id')
            ->addIndex('staff_custom_table_column_id')
            ->addIndex('staff_custom_table_row_id')
            ->addIndex('staff_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // student_custom_table_cells
        $this->table('student_custom_table_cells', [
                'id' => false,
                'primary_key' => [
                    'student_custom_field_id',
                    'student_custom_table_column_id',
                    'student_custom_table_row_id',
                    'student_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the values of a table-type question in a form'
            ])
            ->addColumn('text_value', 'string', [
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('number_value', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('decimal_value', 'string', [
                'limit' => 25,
                'null' => true
            ])
            ->addColumn('student_custom_field_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_custom_fields.id'
            ])
            ->addColumn('student_custom_table_column_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_custom_table_columns.id'
            ])
            ->addColumn('student_custom_table_row_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_custom_table_rows.id'
            ])
            ->addColumn('student_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
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
            ->addIndex('student_custom_field_id')
            ->addIndex('student_custom_table_column_id')
            ->addIndex('student_custom_table_row_id')
            ->addIndex('student_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT IGNORE INTO `custom_table_cells` (`text_value`, `number_value`, `decimal_value`, `custom_field_id`, `custom_table_column_id`, `custom_table_row_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `custom_field_id`, `custom_table_column_id`, `custom_table_row_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_custom_table_cells`');

        $this->execute('INSERT IGNORE INTO `institution_custom_table_cells` (`text_value`, `number_value`, `decimal_value`, `institution_custom_field_id`, `institution_custom_table_column_id`, `institution_custom_table_row_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `institution_custom_field_id`, `institution_custom_table_column_id`, `institution_custom_table_row_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_institution_custom_table_cells`');

        $this->execute('INSERT IGNORE INTO `institution_repeater_survey_table_cells` (`text_value`, `number_value`, `decimal_value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_repeater_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_repeater_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_institution_repeater_survey_table_cells`');

        $this->execute('INSERT IGNORE INTO `institution_student_survey_table_cells` (`text_value`, `number_value`, `decimal_value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_institution_student_survey_table_cells`');

        $this->execute('INSERT IGNORE INTO `institution_survey_table_cells` (`text_value`, `number_value`, `decimal_value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_institution_survey_table_cells`');

        $this->execute('INSERT IGNORE INTO `staff_custom_table_cells` (`text_value`, `number_value`, `decimal_value`, `staff_custom_field_id`, `staff_custom_table_column_id`, `staff_custom_table_row_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `staff_custom_field_id`, `staff_custom_table_column_id`, `staff_custom_table_row_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_staff_custom_table_cells`');

        $this->execute('INSERT IGNORE INTO `student_custom_table_cells` (`text_value`, `number_value`, `decimal_value`, `student_custom_field_id`, `student_custom_table_column_id`, `student_custom_table_row_id`, `student_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `text_value`, NULL, NULL, `student_custom_field_id`, `student_custom_table_column_id`, `student_custom_table_row_id`, `student_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4535_student_custom_table_cells`');
    }

    public function down()
    {
        $this->dropTable("custom_table_cells");
        $this->dropTable("institution_custom_table_cells");
        $this->dropTable("institution_repeater_survey_table_cells");
        $this->dropTable("institution_student_survey_table_cells");
        $this->dropTable("institution_survey_table_cells");
        $this->dropTable("staff_custom_table_cells");
        $this->dropTable("student_custom_table_cells");

        $this->table("z_4535_custom_table_cells")->rename("custom_table_cells");
        $this->table("z_4535_institution_custom_table_cells")->rename("institution_custom_table_cells");
        $this->table("z_4535_institution_repeater_survey_table_cells")->rename("institution_repeater_survey_table_cells");
        $this->table("z_4535_institution_student_survey_table_cells")->rename("institution_student_survey_table_cells");
        $this->table("z_4535_institution_survey_table_cells")->rename("institution_survey_table_cells");
        $this->table("z_4535_staff_custom_table_cells")->rename("staff_custom_table_cells");
        $this->table("z_4535_student_custom_table_cells")->rename("student_custom_table_cells");
    }
}
