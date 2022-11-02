<?php
use Migrations\AbstractMigration;

class POCOR5169 extends AbstractMigration
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
        //institution_report_card_processes
        $this->table('institution_report_card_processes', [
			'id' => false,
            'collation' => 'utf8mb4_unicode_ci',
			'primary_key' => 'report_card_id',
        ])
        ->addColumn('report_card_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to report_cards.id'
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
		->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false
		])
		->addIndex('academic_period_id')
		->addIndex('institution_id')
		->addIndex('report_card_id')
        ->save();
		
		//institution_report_cards
        $this->table('institution_report_cards', [
			'id' => false,
            'primary_key' => ['report_card_id', 'institution_id', 'academic_period_id'],
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
        ->addColumn('report_card_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
        ])
        ->addColumn('institution_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
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
		->addIndex('report_card_id')
		->addIndex('institution_id')
		->addIndex('academic_period_id')
		->addIndex('modified_user_id')
		->addIndex('created_user_id')
        ->save();
		
		//profile_templates
        $this->table('profile_templates', [
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
        //rollback of profile_templates,institution_report_card_processes,institution_report_cards
        $this->execute('DROP TABLE IF EXISTS `profile_templates`');
        $this->execute('DROP TABLE IF EXISTS `institution_report_card_processes`');
        $this->execute('DROP TABLE IF EXISTS `institution_report_cards`');
    }
}
