<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionSubjectsFixture extends TestFixture
{
    public $import = ['table' => 'institution_subjects'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Personal/Social/Emotional Development',
            'no_of_seats' => null,
            'institution_id' => '1',
            'education_subject_id' => '74',
            'academic_period_id' => '25',
            'modified_user_id' => '8',
            'modified' => '2016-06-23 02:58:36',
            'created_user_id' => '2',
            'created' => '2016-06-23 02:41:57'
        ], [
            'id' => '2',
            'name' => 'Creative Development',
            'no_of_seats' => null,
            'institution_id' => '1',
            'education_subject_id' => '75',
            'academic_period_id' => '25',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-06-23 02:41:57'
        ], [
            'id' => '3',
            'name' => 'Physical Development',
            'no_of_seats' => null,
            'institution_id' => '1',
            'education_subject_id' => '76',
            'academic_period_id' => '25',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-06-23 02:41:57'
        ], [
            'id' => '4',
            'name' => 'Communication, Language and Literacy',
            'no_of_seats' => null,
            'institution_id' => '1',
            'education_subject_id' => '87',
            'academic_period_id' => '25',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-06-23 02:41:57'
        ], [
            'id' => '5',
            'name' => 'Knowledge and Understanding of the World',
            'no_of_seats' => null,
            'institution_id' => '1',
            'education_subject_id' => '89',
            'academic_period_id' => '25',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-06-23 02:41:57'
        ], [
            'id' => '7',
            'name' => 'Language Arts',
            'no_of_seats' => null,
            'institution_id' => '1',
            'education_subject_id' => '94',
            'academic_period_id' => '25',
            'modified_user_id' => '2',
            'modified' => '2016-06-24 02:47:13',
            'created_user_id' => '2',
            'created' => '2016-06-24 02:45:16'
        ]
    ];
}