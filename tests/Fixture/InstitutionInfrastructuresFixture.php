<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionInfrastructuresFixture extends TestFixture
{
    // This table has to be created from $fields instead of $import because of the following error
    // "SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes"
    // public $fields = [
    //     'id' => ['type' => 'integer'],
    //     'code' => ['type' => 'string', 'length' => 100, 'null' => false],
    //     'name' => ['type' => 'string', 'length' => 250, 'null' => false],
    //     'year_acquired' => ['type' => 'integer', 'length' => 4],
    //     'year_disposed' => ['type' => 'integer', 'length' => 4],
    //     'comment' => 'text',
    //     'size' => ['type' => 'float'],
    //     'parent_id' => ['type' => 'integer', 'length' => 11],
    //     'institution_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
    //     'infrastructure_level_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
    //     'infrastructure_type_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
    //     'infrastructure_ownership_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
    //     'infrastructure_condition_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
    //     'modified_user_id' => ['type' => 'integer', 'length' => 11],
    //     'modified' => 'datetime',
    //     'created_user_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
    //     'created' => ['type' => 'datetime', 'null' => false],
    //     '_constraints' => [
    //         'primary' => ['type' => 'primary', 'columns' => ['id']]
    //     ]
    // ];

    public $import = ['table' => 'institution_infrastructures'];
    public $records = [
        [
            'id' => '1',
            'code' => 'ABS6653801',
            'name' => 'Parcel A',
            'year_acquired' => '2000',
            'year_disposed' => null,
            'comment' => '',
            'size' => '10000',
            'parent_id' => null,
            'institution_id' => '1',
            'infrastructure_level_id' => '1',
            'infrastructure_type_id' => '1',
            'infrastructure_ownership_id' => '4',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 09:34:01'
        ], [
            'id' => '2',
            'code' => 'ABS665380101',
            'name' => 'Block A',
            'year_acquired' => '2002',
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '1',
            'institution_id' => '1',
            'infrastructure_level_id' => '2',
            'infrastructure_type_id' => '2',
            'infrastructure_ownership_id' => '4',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 09:34:24'
        ], [
            'id' => '3',
            'code' => 'ABS66538010101',
            'name' => 'Ground Floor',
            'year_acquired' => '2004',
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '2',
            'institution_id' => '1',
            'infrastructure_level_id' => '3',
            'infrastructure_type_id' => '3',
            'infrastructure_ownership_id' => '4',
            'infrastructure_condition_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 09:34:49'
        ], [
            'id' => '4',
            'code' => 'ASS5026801',
            'name' => 'Land B',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => null,
            'institution_id' => '3',
            'infrastructure_level_id' => '1',
            'infrastructure_type_id' => '1',
            'infrastructure_ownership_id' => '1',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-18 06:06:03'
        ], [
            'id' => '5',
            'code' => 'ASS502680101',
            'name' => 'Building B',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '4',
            'institution_id' => '3',
            'infrastructure_level_id' => '2',
            'infrastructure_type_id' => '2',
            'infrastructure_ownership_id' => '1',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-18 06:06:59'
        ], [
            'id' => '6',
            'code' => 'ASS50268010101',
            'name' => 'Floor 1',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '5',
            'institution_id' => '3',
            'infrastructure_level_id' => '3',
            'infrastructure_type_id' => '3',
            'infrastructure_ownership_id' => '2',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-18 06:07:30'
        ], [
            'id' => '7',
            'code' => 'AS6745701',
            'name' => 'Ambai Land',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => null,
            'institution_id' => '5',
            'infrastructure_level_id' => '1',
            'infrastructure_type_id' => '1',
            'infrastructure_ownership_id' => '1',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-18 07:59:36'
        ], [
            'id' => '8',
            'code' => 'AS674570101',
            'name' => 'Ambai Building',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '7',
            'institution_id' => '5',
            'infrastructure_level_id' => '2',
            'infrastructure_type_id' => '2',
            'infrastructure_ownership_id' => '1',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-19 04:43:00'
        ], [
            'id' => '9',
            'code' => 'AS67457010101',
            'name' => '1st Floor',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '8',
            'institution_id' => '5',
            'infrastructure_level_id' => '3',
            'infrastructure_type_id' => '3',
            'infrastructure_ownership_id' => '1',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-19 04:44:00'
        ], [
            'id' => '10',
            'code' => 'ABS6653802',
            'name' => 'Land A',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => null,
            'institution_id' => '1',
            'infrastructure_level_id' => '1',
            'infrastructure_type_id' => '1',
            'infrastructure_ownership_id' => '3',
            'infrastructure_condition_id' => '3',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-21 06:59:59'
        ], [
            'id' => '11',
            'code' => 'ABS665380201',
            'name' => 'Building A',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '10',
            'institution_id' => '1',
            'infrastructure_level_id' => '2',
            'infrastructure_type_id' => '2',
            'infrastructure_ownership_id' => '3',
            'infrastructure_condition_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-21 07:00:51'
        ], [
            'id' => '12',
            'code' => 'ABS665380202',
            'name' => 'Building B',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '10',
            'institution_id' => '1',
            'infrastructure_level_id' => '2',
            'infrastructure_type_id' => '2',
            'infrastructure_ownership_id' => '2',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-21 07:01:22'
        ], [
            'id' => '13',
            'code' => 'ABS66538020101',
            'name' => '1st floor',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '11',
            'institution_id' => '1',
            'infrastructure_level_id' => '3',
            'infrastructure_type_id' => '3',
            'infrastructure_ownership_id' => '3',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-21 07:02:31'
        ], [
            'id' => '14',
            'code' => 'ABS66538020102',
            'name' => '2nd floor',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => '11',
            'institution_id' => '1',
            'infrastructure_level_id' => '3',
            'infrastructure_type_id' => '3',
            'infrastructure_ownership_id' => '3',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-21 07:03:16'
        ], [
            'id' => '15',
            'code' => 'ABS6653803',
            'name' => 'Land A1',
            'year_acquired' => null,
            'year_disposed' => null,
            'comment' => '',
            'size' => null,
            'parent_id' => null,
            'institution_id' => '1',
            'infrastructure_level_id' => '1',
            'infrastructure_type_id' => '1',
            'infrastructure_ownership_id' => '1',
            'infrastructure_condition_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-21 07:03:16'
        ]
    ];
}
