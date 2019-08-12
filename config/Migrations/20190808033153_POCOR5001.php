<?php
use Phinx\Migration\AbstractMigration;

class POCOR5001 extends AbstractMigration
{
    // commit
    public function up()
    {
        
        // institution_program_grade_subjects
		
        $table = $this->table('institution_program_grade_subjects', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains details of program grade subjects'
            ]);
			
        $table
			->addColumn('institution_grade_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('education_grade_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
			->addColumn('education_grade_subject_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
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
            ->addIndex('education_grade_id')
			->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
			
        // end institution_program_grade_subjects

    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE institution_program_grade_subjects');
    }
}
