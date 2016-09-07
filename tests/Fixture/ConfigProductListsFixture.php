<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ConfigProductListsFixture extends TestFixture
{
    public $import = ['table' => 'config_product_lists'];
    public $records = [
        [
            'id' => '1',
            'name' => 'OpenEMIS Dashboard',
            'url' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '0',
            'created' => '2016-08-30 09:20:48'
        ],
        [
            'id' => '2',
            'name' => 'OpenEMIS Integrator',
            'url' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '0',
            'created' => '2016-08-30 09:20:48'
        ]
    ];
}


