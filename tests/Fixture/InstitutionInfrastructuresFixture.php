<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionInfrastructuresFixture extends TestFixture
{
    // This table has to be created from $fields instead of $import because of the following error
    // "SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes"
    public $fields = [
        'id' => ['type' => 'integer'],
        'code' => ['type' => 'string', 'length' => 100, 'null' => false],
        'name' => ['type' => 'string', 'length' => 250, 'null' => false],
        'year_acquired' => ['type' => 'integer', 'length' => 4],
        'year_disposed' => ['type' => 'integer', 'length' => 4],
        'comment' => 'text',
        'size' => ['type' => 'float'],
        'parent_id' => ['type' => 'integer', 'length' => 11],
        'institution_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'infrastructure_level_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'infrastructure_type_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'infrastructure_ownership_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
        'infrastructure_condition_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
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

