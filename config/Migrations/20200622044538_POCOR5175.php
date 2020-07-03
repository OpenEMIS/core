<?php
use Migrations\AbstractMigration;

class POCOR5175 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_5175_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5175_import_mapping` SELECT * FROM `import_mapping`');
        // End
		
		$importMapping = [

            [
                'model' => 'Student.Extracurriculars',
				'column_name' => 'academic_period_id',  
				'description' =>'',
                'order' => 1,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'name',  
				'description' =>'',
                'order' => 2,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'extracurricular_type_id',  
				'description' =>'',
                'order' => 3,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'openemis_no',  
				'description' =>'',
                'order' => 4,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'security_user_id',  
				'description' =>'',
                'order' => 5,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'start_date',  
				'description' =>' ( DD/MM/YYYY )',
                'order' => 6,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'end_date',  
				'description' =>' ( DD/MM/YYYY )',
                'order' => 7,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'hours',  
				'description' =>'',
                'order' => 8,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'points',  
				'description' =>'',
                'order' => 9,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'location',  
				'description' =>'',
                'order' => 10,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'position',  
				'description' =>'',
                'order' => 11,
            ],
			
			[
                'model' => 'Student.Extracurriculars',
				'column_name' => 'comment',  
				'description' =>'',
                'order' => 12,
            ],
			
		];
		
        $this->insert('import_mapping', $importMapping);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5175_import_mapping` TO `import_mapping`');
    }
}
