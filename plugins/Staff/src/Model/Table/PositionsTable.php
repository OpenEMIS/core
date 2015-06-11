<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class PositionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_staff');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses', ['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('InstitutionSitePositions', ['className' => 'Institution.InstitutionSitePositions']);
		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}
}
