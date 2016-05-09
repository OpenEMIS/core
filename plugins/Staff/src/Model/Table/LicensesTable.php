<?php
namespace Staff\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Database\ValueBinder;
use App\Model\Table\ControllerActionTable;

class LicensesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_licenses');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('HighChart', [
			'institution_staff_licenses' => [
				'_function' => 'getNumberOfStaffByLicenses'
			]
		]);
	}

	public function beforeAction() {
		$this->fields['license_type_id']['type'] = 'select';
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		
		return $validator->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			->add('expiry_date', [
			])
		;
	}

	// Use for Mini dashboard (Institution Staff)
	public function getNumberOfStaffByLicenses($params=[]){
		$query = $params['query'];
		$table = $params['table'];

		$StaffTableQuery = clone $query;
		$staffTableInnerJoinQuery = $StaffTableQuery->select([$table->aliasField('staff_id')]);
		$staffTable = TableRegistry::get('Institution.Staff');
		$innerJoinArray = [
			'StaffUser.Staff__staff_id = '. $this->aliasField('staff_id'),
			];
		$licenseRecord = $this->find();
		$licenseCount = $licenseRecord
			->contain(['Users', 'LicenseTypes'])
			->select([
				'license' => 'LicenseTypes.name',
				'count' => $licenseRecord->func()->count($this->aliasField('staff_id'))
			])
			->join([
				'StaffUser' => [
					'table' => $staffTableInnerJoinQuery,
					'type' => 'INNER',
					'conditions' => $innerJoinArray
				]
			])
			->group('license')
			->toArray()
			;
		$dataSet = [];
		foreach ($licenseCount as $value) {
            //Compile the dataset
			$dataSet[] = [$value['license'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['license_type_id', 'license_number', 'issue_date', 'expiry_date', 'issuer']);
		$this->setupTabElements();
	}
}