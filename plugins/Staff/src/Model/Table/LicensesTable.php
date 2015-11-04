<?php
namespace Staff\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LicensesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_licenses');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
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
		$institutionId = 0;
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];	
		$staffTable = TableRegistry::get('Institution.Staff');
		$innerJoinArray = [
			'StaffUser.security_user_id = '. $this->aliasField('security_user_id'),
		];
		$innerJoinArraySize = count($innerJoinArray);
		foreach ($conditions as $key => $value) {
			 $_conditions[$innerJoinArraySize++] = 'StaffUser.'.$key.' = '.$value;
		}
		$innerJoinArray = array_merge($innerJoinArray, $_conditions);
		$searchConditions = isset($params['searchConditions']) ? $params['searchConditions'] : [];
		$periodId = isset($params['academicPeriod']) ? $params['academicPeriod'] : [];
		$licenseRecord = $this->find();
		$licenseCount = $licenseRecord
			->contain(['Users', 'LicenseTypes'])
			->select([
				'license' => 'LicenseTypes.name',
				'count' => $licenseRecord->func()->count($this->aliasField('security_user_id'))
			])
			->where($searchConditions)
			->join([

					'StaffUser' => [
						'table' => $staffTable->find('academicPeriod', ['academic_period_id' => $periodId])
							->select([
								'security_user_id' => $staffTable->aliasField('security_user_id'),
								'institution_site_id' => $staffTable->aliasField('institution_site_id')
							]),
						'type' => 'INNER',
						'conditions' => $innerJoinArray
					]
			])
			->group('license')
			->toArray();
		$dataSet = [];
		foreach ($licenseCount as $value) {
            //Compile the dataset
			$dataSet[] = [$value['license'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}
}