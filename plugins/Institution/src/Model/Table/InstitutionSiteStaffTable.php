<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStaffTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', 		 ['className' => 'User.Users', 							'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Positions', 	 ['className' => 'Institution.InstitutionSitePositions','foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('StaffTypes', 	 ['className' => 'Institution.StaffTypes', 				'foreignKey' => 'staff_type_id']);
		$this->belongsTo('StaffStatuses',['className' => 'Institution.StaffStatuses', 			'foreignKey' => 'staff_status_id']);

	}

	public function findByPosition(Query $query, array $options) {
		if (array_key_exists('InstitutionSitePositions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_position_id') => $options['InstitutionSitePositions.id']]);
		} else {
			return $query;
		}
	}

	public function findByInstitution(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_id') => $options['Institutions.id']]);
		} else {
			return $query;
		}
	}

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {

		$this->fields['security_user_id']['order'] = 0;
		$this->fields['institution_site_position_id']['order'] = 1;		
		$this->fields['FTE']['order'] = 2;
		$this->fields['start_date']['order'] = 3;
		$this->fields['end_date']['order'] = 4;
		$this->fields['staff_type_id']['order'] = 5;
		$this->fields['staff_status_id']['order'] = 6;

	}

	public function editBeforeAction($event) {

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;

		$this->fields['institution_site_position_id']['type'] = 'select';
		$rawData = $this->Positions->find('all')->select(['id', 'position_no']);
		$options = [];
		foreach ($rawData as $rd) {
			$options[$rd['id']] = $rd['position_no'];
		}
		$this->fields['institution_site_position_id']['options'] = $options;

		$this->fields['staff_type_id']['type'] = 'select';
		$this->fields['staff_status_id']['type'] = 'select';

	}

	public function addBeforeAction($event) {

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;

		$this->fields['institution_site_position_id']['type'] = 'select';
		$rawData = $this->Positions->find('all')->select(['id', 'position_no']);
		$options = [];
		foreach ($rawData as $rd) {
			$options[$rd['id']] = $rd['position_no'];
		}
		$this->fields['institution_site_position_id']['options'] = $options;

		$this->fields['staff_type_id']['type'] = 'select';
		$this->fields['staff_status_id']['type'] = 'select';

	}

	public function editBeforeQuery(Event $event, Query $query, $contain) {
		$contain = ['Users', 'Positions', 'StaffTypes', 'StaffStatuses'];
		return compact('query', 'contain');
	}

	public function editOnInitialize($event, $entity) {

			$this->fields['security_user_id']['type'] = 'readonly';
			$this->fields['security_user_id']['attr']['value'] = $entity->user->name;

			$this->fields['institution_site_position_id']['type'] = 'readonly';

			$this->fields['FTE']['type'] = 'readonly';

			$this->fields['start_date']['type'] = 'readonly';
			$this->fields['start_date']['attr']['value'] = $this->formatDateTime($entity->start_date);

	}

	// public function editAfterAction($event) {
	// 	pr($this->fields['staff_type_id']);
	// 	pr($this->fields['staff_status_id']);
	// }
}
