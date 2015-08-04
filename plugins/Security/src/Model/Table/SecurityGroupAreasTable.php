<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityGroupAreasTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Areas', ['className' => 'Area.Areas']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.SecurityGroups']);
	}
}
