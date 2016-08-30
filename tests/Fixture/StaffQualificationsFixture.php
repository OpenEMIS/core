<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffQualificationsFixture extends TestFixture
{
    // This table has to be created from $fields instead of $import because of the following error
    // "SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes"
    public $fields = [
        'id' => ['type' => 'integer'],
        'document_no' => ['type' => 'string', 'length' => 100],
        'graduate_year' => ['type' => 'integer', 'length' => 4, 'null' => false],
        'staff_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'qualification_level_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'qualification_title' => ['type' => 'string', 'length' => 100, 'null' => false],
        'qualification_specialisation_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'qualification_institution_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'qualification_institution_country' => ['type' => 'string', 'length' => 255],
        'file_name' => ['type' => 'string', 'length' => 250],
        'file_content' => ['type' => 'binary'],
        'gpa' => ['type' => 'string', 'length' => 5],
        'modified_user_id' => ['type' => 'integer', 'length' => 11],
        'modified' => 'datetime',
        'created_user_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'created' => ['type' => 'datetime', 'null' => false],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']]
        ]
    ];
    public $records = [];
}

