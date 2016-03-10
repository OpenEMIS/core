<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionStaffOnLeaveTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_staff');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Positions',		['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffTypes',		['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses',	['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
		$this->belongsTo('Leaves',	['className' => 'Staff.Leaves']);


		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year', 'academic_period_id', 'security_group_user_id'], 
			'pages' => false
		]);
	}

	public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
		// Setting request data and modifying fetch condition
		$requestData = json_decode($settings['process']['params']);

		$leaveDate = $requestData->leaveDate;

		if (!$data[$field] instanceof Time) {
			// to handle both d-m-y and d-m-Y because datepicker and cake doesnt validate
			$dateObj = date_create_from_format("d-m-Y",$data[$field]);
			if ($dateObj === false) {
				$dateObj = date_create_from_format("d-m-y",$data[$field]);
			}
			if ($dateObj !== false) {
				$data[$field] = $dateObj->format($format);
			}
		}

				
		// have to check whether his is a legal date
		if ($leaveDate!=0) {
			// need to match the days on leave
			$query->matching(
			    'Leaves', function ($q) use ($leaveDate) {
			        return $q->where([
			        	'Leaves.dateFrom <= ' => $leaveDate,
			        	'Leaves.dateTo >= ' => $leaveDate
			        ]);
		    	}
			);
		}

		$query->leftJoin(
			['Identities' => 'user_identities'],
			[
				'Identities.security_user_id = '.$this->aliasField('staff_id'),
				'Identities.identity_type_id' => $settings['identity']->id
			]
		);

		$query
			->contain(['Users.Genders', 'Institutions.Areas'])
			->select([
				'openemis_no' => 'Users.openemis_no', 
				'number' => 'Identities.number', 
				'code' => 'Institutions.code', 
				'gender_id' => 'Genders.name', 
				'area_name' => 'Areas.name',
				'area_code' => 'Areas.code'
			]);
	}

	public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$sheets[] = [
			'name' => $this->alias(),
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
		$identity = $IdentityType->getDefaultEntity();

		$settings['identity'] = $identity;

		// To update to this code when upgrade server to PHP 5.5 and above
		// unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

		foreach ($fields as $key => $field) {
			if (in_array($field['field'], ['institution_id'])) {
				unset($fields[$key]);
				break;
			}
		}
		
		$extraField[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Staff.institution_id',
			'field' => 'institution_id',
			'type' => 'integer',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Identities.number',
			'field' => 'number',
			'type' => 'string',
			'label' => __($identity->name)
		];

		$extraField[] = [
			'key' => 'Users.gender_id',
			'field' => 'gender_id',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Institutions.area_name',
			'field' => 'area_name',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Institutions.area_code',
			'field' => 'area_code',
			'type' => 'string',
			'label' => ''
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}
}