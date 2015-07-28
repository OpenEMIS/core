<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LicensesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_licenses');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);

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

	public function getNumberOfStaffByLicenses($params=[]){
		$institutionId = 0;
		if(!empty ($params['institution_site_id'])){
			$institutionId = $params['institution_site_id'];
		}

		$licenseRecord = $this->find();
		$licenseCount = $licenseRecord
			->contain(['Users', 'LicenseTypes'])
			->select([
				'license' => 'LicenseTypes.name',
				'count' => $licenseRecord->func()->count($this->aliasField('security_user_id'))
			])
			->innerJoin(['InstitutionSiteStaff' => 'institution_site_staff'],
				[
					'InstitutionSiteStaff.security_user_id = ' . $this->aliasField('security_user_id'),
					'InstitutionSiteStaff.institution_site_id = ' . $institutionId
				]
			)
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