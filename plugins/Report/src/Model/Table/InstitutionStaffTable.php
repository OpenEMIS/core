<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;

class InstitutionStaffTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_staff');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Positions',		['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffTypes',		['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses',	['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);

		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year', 'FTE', 'security_group_user_id'], 
			'pages' => false
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

	public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
		// Setting request data and modifying fetch condition
		$requestData = json_decode($settings['process']['params']);
		$statusId = $requestData->status;
		$typeId = $requestData->type;

		if ($statusId!=0) {
			$query->where([
				$this->aliasField('staff_status_id') => $statusId
			]);
		}

		if ($typeId!=0) {
			$query->where([
				$this->aliasField('staff_type_id') => $typeId
			]);
		}

		$query->leftJoin(
			['Identities' => 'user_identities'],
			[
				'Identities.security_user_id = '.$this->aliasField('staff_id'),
				'Identities.identity_type_id' => $settings['identity']->id
			]
		);

		$query->contain(['Users.Genders', 'Institutions.Areas', 'Positions.StaffPositionTitles'])->select([
			'openemis_no' => 'Users.openemis_no', 
			'number' => 'Identities.number', 
			'code' => 'Institutions.code', 
			'gender_id' => 'Genders.name', 
			'area_name' => 'Areas.name', 
			'area_code' => 'Areas.code',
			'position_title_teaching' => 'StaffPositionTitles.type'
		]);
	}

	public function onExcelGetFTE(Event $event, Entity $entity) {
		return $entity->FTE*100;
	}

	public function onExcelGetPositionTitleTeaching(Event $event, Entity $entity) {
		$yesno = $this->getSelectOptions('general.yesno');
		return (array_key_exists($entity->position_title_teaching, $yesno))? $yesno[$entity->position_title_teaching]: '';
	}

	public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
		$age = '';
		if ($entity->has('user')) {
			if ($entity->user->has('date_of_birth')) {
				if (!empty($entity->user->date_of_birth)) {
					$yearOfBirth = $entity->user->date_of_birth->format('Y');
					$age = date("Y")-$yearOfBirth;
				}
			}
		}
		return $age;
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
		$identity = $IdentityType->getDefaultEntity();

		$settings['identity'] = $identity;

		// To update to this code when upgrade server to PHP 5.5 and above
		// unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

		foreach ($fields as $key => $field) {
			if ($field['field'] == 'institution_id') {
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

		$extraField[] = [
			'key' => 'Staff.FTE',
			'field' => 'FTE',
			'type' => 'integer',
			'label' => 'FTE (%)',
		];

		$extraField[] = [
			'key' => 'Age',
			'field' => 'Age',
			'type' => 'Age',
			'label' => __('Age'),
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());

		$newFields[] = [
			'key' => 'Positions.position_title_teaching',
			'field' => 'position_title_teaching',
			'type' => 'string',
			'label' => __('Teaching')
		];

		$fields->exchangeArray($newFields);
	}
}