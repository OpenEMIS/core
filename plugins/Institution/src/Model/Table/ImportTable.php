<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class ImportTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        // $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'Institutions']);
	    $this->addBehavior('Import.Import');
	}
}
