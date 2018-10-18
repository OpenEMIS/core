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
                'subject' => 'Student Report Card of ${student.openemis_no} for ${academic_period.name}',
                'message' => 'Dear ${student.first_name},

Attached is your student report card for ${academic_period.name}.

Thank you.

[This is an auto-generated email. Please do not reply directly to this email.]',
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

        // Create backup for security_functions     
        $this->execute('CREATE TABLE `z_4684_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4684_security_functions` SELECT * FROM `security_functions`');

        // Gets the current order for MAP
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 5072');
        $order = $row['order'] + 1;

        //Updates all the order by +1
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > ' . $order);

        //Insert adminisration >> report card >> email template into it
        $this->insert('security_functions', [
            'id' => 7061,
            'name' => 'Email Templates',
            'controller' => 'ReportCards',
            'module' => 'Administration',
            'category' => 'Report Cards',
            'parent_id' => 5000,
            '_view' => 'ReportCardEmail.index|ReportCardEmail.view',
            '_add' => 'ReportCardEmail.edit',
            '_execute' => null,
            'order' => $order,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // Gets the current order for MAP
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 5072');
        $order = $row['order'];

        //Updates all the order by +1
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` >= ' . $order);

        //Insert adminisration >> report card >> email template into it
        $this->insert('security_functions', [
            'id' => 7062,
            'name' => 'Email/Email All',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Report Cards',
            'parent_id' => 1000,
            '_view' => null,
            '_add' => null,
            '_execute' => 'ReportCardStatuses.email|ReportCardStatuses.emailAll',
            'order' => $order,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->dropTable('email_templates');
        $this->dropTable('email_processes');
        $this->dropTable('email_process_attachments');
        $this->dropTable('report_card_email_processes');

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4684_security_functions` TO `security_functions`');
    }
}
