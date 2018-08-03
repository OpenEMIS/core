<?php

use Phinx\Migration\AbstractMigration;

class POCOR4684 extends AbstractMigration
{
    public function up()
    {
        $this->table('email_templates', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains all the email template',
                'id' => false, 
                'primary_key' => [
                	'model_alias', 
                	'model_reference'
                ]
            ])
            ->addColumn('model_alias', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('model_reference', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('subject', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false
            ])
            ->addColumn('message', 'text', [
                'default' => null,
                'null' => false
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
            ->addIndex('model_alias')
            ->addIndex('model_reference')
            ->save();

        $data = [
            [
                'model_alias' => 'ReportCard.ReportCardEmail',
                'model_reference' => 0,
                'subject' => 'Default Template Subject',
                'message' => 'Default Template Message',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('email_templates', $data);

        $this->table('email_processes', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the email progress generation',
            ])
            ->addColumn('recipients', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('subject', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('message', 'text', [
                'default' => null,
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
            ->addIndex('id')
            ->save();

        $this->table('email_process_attachments', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the email progress attachment generation',
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => false
            ])
            ->addColumn('email_process_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to email_processes.id'
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
            ->addIndex('id')
            ->save();

       $this->table('report_card_email_processes', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the report card email progress',
                'id' => false, 
                'primary_key' => [
                	'report_card_id', 
                	'institution_class_id',
                	'student_id'
                ]
            ])
            ->addColumn('report_card_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to report_cards.id'
            ])
            ->addColumn('institution_class_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('status', 'integer', [
                'default' => null,
                'limit' => 2,
                'null' => false,
                'comment' => '1 => Sending 2 => Sent -1 => Error'
            ])
            ->addColumn('error_message', 'text', [
                'default' => null,
                'null' => true,
            ])
			->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
			->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
			->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('report_card_id')
            ->addIndex('institution_class_id')
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('education_grade_id')
            ->addIndex('academic_period_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable('email_templates');
        $this->dropTable('email_processes');
        $this->dropTable('email_process_attachments');
        $this->dropTable('report_card_email_processes');
    }
}
