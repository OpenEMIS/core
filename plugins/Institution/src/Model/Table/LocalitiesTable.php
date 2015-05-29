<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LocalitiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
		parent::initialize($config);
        $this->addBehavior('FieldOptionValues');
		// $this->addBehavior('Timestamp', [
  //           'events' => [
  //               'Model.beforeSave' => [
  //                   'created_at' => 'new',
  //                   'modified_at' => 'always'
  //               ]
  //           ]
  //       ]);
        
		$this->hasMany('Institutions', ['className' => 'Institution.Institutions']);

	}

	// public function test() {
	// 	$this->testing();die;
	// }
}
