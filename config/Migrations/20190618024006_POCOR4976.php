<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR4976 extends AbstractMigration
{
    public function up()
    {
        // special_need_assessments_types - start
        $SpecialNeedAssessmentType = $this->table('special_need_assessments_types', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains special need assessments types'
        ]);

        $SpecialNeedAssessmentType
            ->addColumn('id', 'integer', [
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'limit' => 50,
                'default' => null,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'default' => null,
                'null' => false
            ])
			->addColumn('visible', 'integer', [
                'limit' => 1,
                'default' => 1,
                'null' => false
            ])
			->addColumn('editable', 'integer', [
                'limit' => 1,
                'default' => 1,
                'null' => false
            ])
			->addColumn('default', 'integer', [
                'limit' => 1,
                'default' => 0,
                'null' => false
            ])
			->addColumn('international_code', 'string', [
                'limit' => 50,
                'default' => null,
                'null' => false
            ])
			->addColumn('national_code', 'string', [
                'limit' => 50,
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
            ->addIndex('id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $specialNeedAssessmentTypeData = [
            [
                'id' => 1,
                'name' => 'WISC',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 1,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:36:56'
            ],
            [
                'id' => 2,
                'name' => 'Mico Diagnostic Test',
                'order' => 2,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:37:13'
            ],
            [
                'id' => 3,
                'name' => 'Phonic Test',
                'order' => 3,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:37:31'
            ],
            [
                'id' => 4,
                'name' => 'TONI',
                'order' => 4,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:37:38'
            ],
            [
                'id' => 5,
                'name' => 'Peabody',
                'order' => 5,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:37:46'
            ],
            [
                'id' => 6,
                'name' => 'Peabody',
                'order' => 6,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:37:53'
            ],
            [
                'id' => 7,
                'name' => 'Test of Written Spelling',
                'order' => 7,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:38:04'
            ],
            [
                'id' => 8,
                'name' => 'NaRCIE Math',
                'order' => 8,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:38:18'
            ],
            [
                'id' => 9,
                'name' => 'Test for children with developmental delay or Seve',
                'order' => 9,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:38:30'
            ],
            [
                'id' => 10,
                'name' => 'Hearing Test',
                'order' => 10,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:38:39'
            ],
            [
                'id' => 11,
                'name' => 'Vision Test',
                'order' => 11,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:38:47'
            ],
            [
                'id' => 12,
                'name' => 'Denver',
                'order' => 12,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:38:56'
            ],
            [
                'id' => 13,
                'name' => 'Informal Test',
                'order' => 13,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:39:05'
            ],
            [
                'id' => 14,
                'name' => 'Mansuiter Auditory and Visual Memory',
                'order' => 14,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 07:39:14'
            ]
        ];

        $SpecialNeedAssessmentType
            ->insert($specialNeedAssessmentTypeData)
            ->save();
        // special_need_assessments_types - end


        
        // institution_student_absences - start
        // backup 
        $this->execute('CREATE TABLE `z_4976_special_need_types` LIKE `special_need_types`');
        $this->execute('INSERT INTO `z_4976_special_need_types` SELECT * FROM `special_need_types`');
		$specialNeedTypeData = [
            [
                'id' => 623,
                'name' => 'Not performing according to benchmarks for chronol',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:35:59'
            ],
            [
                'id' => 624,
                'name' => 'Low grades across subject areas ',
                'order' => 2,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:36:29'
            ],
			[
                'id' => 625,
                'name' => 'Inappropriate behaviour',
                'order' => 3,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:36:45'
            ],
			[
                'id' => 626,
                'name' => 'Visual/hearing problems',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:36:59'
            ],
			[
                'id' => 627,
                'name' => 'Special arrangements',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:37:12'
            ],
			[
                'id' => 628,
                'name' => 'Not coping in school',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:37:25'
            ],
			[
                'id' => 629,
                'name' => 'Causing danger to himself and others',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:37:36'
            ],
			[
                'id' => 630,
                'name' => 'Disruptive behaviour ',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:37:36'
            ],
			[
                'id' => 631,
                'name' => 'Lacks Communication',
                'order' => 1,
				'visible' => 1,
				'editable' => 1,
				'default' => 0,
				'international_code' => '',
				'national_code' => '',
				'modified_user_id' => null,
				'modified' => null,
				'created_user_id' => 2,
				'created' => '2019-06-18 08:37:36'
            ]
			];
			
			$this->insert('special_need_types', $specialNeedTypeData);
			$this->execute('ALTER TABLE `user_special_needs_assessments` CHANGE `special_need_type_id` `special_need_assessment_type_id` INT(11) COMMENT `links to special_need_assessments_types.id`');
    }

    public function down()
    {
    }
}
