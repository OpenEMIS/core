<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StudentTransferReasonsFixture extends TestFixture
{
    public $import = ['table' => 'student_transfer_reasons'];
    public $records = [
        [
            'id' => 575,
            'name' => 'Relocation',
            'order' => 1,
            'visible' => 1,
            'editable' => 1,
            'default' => 1,
            'international_code' => NULL,
            'national_code' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-08-12 00:00:00'
        ]
    ];
}

