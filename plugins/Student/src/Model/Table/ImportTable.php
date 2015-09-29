<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class ImportTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        // $this->addBehavior('Import.Import', ['plugin'=>'Student', 'model'=>'Students']);
	    $this->addBehavior('Import.Import');
	}
}
