<?php
use Migrations\AbstractMigration;

class POCOR5178 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
		// Backup table
		$this->execute('CREATE TABLE `zz_5178_security_functions` LIKE `security_functions`');
		$this->execute('INSERT INTO `zz_5178_security_functions` SELECT * FROM `security_functions`');		// Backup table
		
		$this->execute('CREATE TABLE `zz_5178_email_templates` LIKE `email_templates`');
		$this->execute('INSERT INTO `zz_5178_email_templates` SELECT * FROM `email_templates`');
		
		$this->insert('email_templates', [
                'model_alias' => 'ReportCard.StudentReportCardEmail',
				'model_reference' => '2',
				'subject' => 'Student Profile of ${student.openemis_no} for ${academic_period.name}',
				'message' => 'Dear ${student.first_name},

Attached is your student Profile for ${academic_period.name}.

Thank you.

[This is an auto-generated email. Please do not reply directly to this email.]',
				'modified_user_id' => NULL,
				'modified' => NULL,
				'created_user_id' => '1',
				'created' => date('Y-m-d H:i:s')
            ]);	
				
		$this->insert('security_functions', [
                'name' => 'Students',
				'controller' => 'ProfileTemplates',
				'module' => 'Administration',
				'category' => 'Profiles',
				'parent_id' => 5000,
				'_view' => 'Students.index|Students.view|StudentProfiles.view|StudentProfiles.view',
				'_edit' => 'Students.edit',
				'_add' => 'Students.add',
				'_delete' => 'Students.remove',
				'_execute' => 'StudentProfiles.generate|StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.downloadAllPdf|StudentProfiles.generateAll|StudentProfiles.emailAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll',
				'order' => 79,
				'visible' => 1,
				'created_user_id' => '1',
				'created' => date('Y-m-d H:i:s')
            ]);
		
        //student_report_card_processes
        $this->table('student_report_card_processes', [
			'id' => false,
            'collation' => 'utf8mb4_unicode_ci',
			'primary_key' => ['student_profile_template_id', 'student_id'],
        ])
        ->addColumn('student_profile_template_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to student_profile_templates.id'
        ])
        ->addColumn('student_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to security_users.id'
        ])
        ->addColumn('status', 'integer', [
            'limit' => 2,
            'null' => false,
            'comment' => '1 => New 2 => Running 3 => Completed -1 => Error'
        ])
		->addColumn('institution_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
			'comment' => 'links to institutions.id'
		])
		->addColumn('academic_period_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
        ])
		->addColumn('education_grade_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to education_grades.id'
        ])
		->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false
		])
		->addIndex('academic_period_id')
		->addIndex('institution_id')
		->addIndex('student_id')
		->addIndex('education_grade_id')
		->addIndex('student_profile_template_id')
        ->save();
		
        //student_report_card_email_processes
        $this->table('student_report_card_email_processes', [
			'id' => false,
            'collation' => 'utf8mb4_unicode_ci',
			'primary_key' => ['student_profile_template_id', 'student_id'],
        ])
        ->addColumn('student_profile_template_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to student_profile_templates.id'
        ])
        ->addColumn('student_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to security_users.id'
        ])
        ->addColumn('status', 'integer', [
            'limit' => 2,
            'null' => false,
            'comment' => '1 => Sending 2 => Sent -1 => Error'
        ])
        ->addColumn('error_message', 'text', [
            'default' => null,
            'null' => true,
        ])
		->addColumn('institution_id', 'integer', [
			'limit' => 11,
			'null' => false,
			'comment' => 'links to institutions.id'
		])
		->addColumn('academic_period_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
        ])
		->addColumn('education_grade_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to education_grades.id'
        ])
		->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false
		])
		->addIndex('academic_period_id')
		->addIndex('institution_id')
		->addIndex('student_id')
		->addIndex('education_grade_id')
		->addIndex('student_profile_template_id')
        ->save();
		
		//student_report_cards
        $this->table('student_report_cards', [
			'id' => false,
            'primary_key' => ['student_profile_template_id', 'institution_id', 'student_id', 'academic_period_id'],
            'collation' => 'utf8mb4_unicode_ci'
        ])
		->addColumn('id', 'char', [
            'limit' => 64,
            'null' => false,
        ])
		->addColumn('status', 'integer', [
            'limit' => 1,
            'null' => false,
            'comment' => '1 -> New, 2 -> In Progress, 3 -> Generated, 4 -> Published'
        ])
        ->addColumn('file_name', 'string', [
            'limit' => 250,
			'default' => null,
            'null' => true
        ])
		->addColumn('file_content', 'blob', [
			'limit' => '4294967295',
			'default' => null,
			'null' => true
		])
		->addColumn('file_content_pdf', 'blob', [
			'limit' => '4294967295',
			'default' => null,
			'null' => true
		])
		->addColumn('started_on', 'datetime', [
            'default' => null,
            'null' => true
        ])
		->addColumn('completed_on', 'datetime', [
            'default' => null,
            'null' => true
        ])
        ->addColumn('student_profile_template_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to student_profile_templates.id'
        ])
        ->addColumn('student_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to security_users.id'
        ])
        ->addColumn('institution_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to institutions.id'
        ])
        ->addColumn('academic_period_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
        ])
        ->addColumn('education_grade_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to education_grades.id'
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
			'limit' => 11,
			'null' => false
		])
		->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false
		])
		->addIndex('student_profile_template_id')
		->addIndex('student_id')
		->addIndex('institution_id')
		->addIndex('academic_period_id')
		->addIndex('education_grade_id')
		->addIndex('modified_user_id')
		->addIndex('created_user_id')
        ->save();
		
		//student_profile_templates
        $this->table('student_profile_templates', [
            'collation' => 'utf8mb4_unicode_ci',
			'primary_key' => 'id',
            'id' => true //Auto increment id and primary key
        ])
        ->addColumn('code', 'string', [
            'limit' => 50,
            'null' => false
        ])
        ->addColumn('name', 'string', [
            'limit' => 150,
            'null' => false
        ])
		->addColumn('description', 'text', [
			'default' => null,
            'null' => false
        ])
		->addColumn('generate_start_date', 'datetime', [
            'default' => null,
            'null' => false
        ])
		->addColumn('generate_end_date', 'datetime', [
            'default' => null,
            'null' => false
        ])
		->addColumn('excel_template_name', 'string', [
            'limit' => 250,
			'default' => null,
            'null' => false
        ])
		->addColumn('excel_template', 'blob', [
			'limit' => '4294967295',
			'default' => null,
			'null' => false
		])
        ->addColumn('academic_period_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
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
			'limit' => 11,
			'null' => false
		])
		->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false
		])
		->addIndex('academic_period_id')
		->addIndex('modified_user_id')
		->addIndex('created_user_id')
        ->save();
    }

    // rollback
    public function down()
    {
		// rollback of security_functions
		$this->execute('DROP TABLE IF EXISTS `security_functions`');
		$this->execute('RENAME TABLE `zz_5178_security_functions` TO `security_functions`');
		
		// rollback of email_templates
		$this->execute('DROP TABLE IF EXISTS `email_templates`');
		$this->execute('RENAME TABLE `zz_5178_email_templates` TO `email_templates`');
		
        //rollback of student_profile_templates,student_report_card_processes,student_report_cards,student_report_card_email_processes
        $this->execute('DROP TABLE IF EXISTS `student_profile_templates`');
        $this->execute('DROP TABLE IF EXISTS `student_report_card_processes`');
        $this->execute('DROP TABLE IF EXISTS `student_report_cards`');
        $this->execute('DROP TABLE IF EXISTS `student_report_card_email_processes`');
    }
}
