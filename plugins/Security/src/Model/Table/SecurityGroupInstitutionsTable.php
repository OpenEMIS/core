<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityGroupInstitutionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_group_institutions');
		parent::initialize($config);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
	}
}
