<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class PotentialStudentDuplicatesTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		
		$this->addBehavior('Excel', [
			'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$query
			->join([
				'su' => [
					'table' => 
						"(SELECT first_name, last_name, gender_id, date_of_birth 
						FROM security_users 
						where is_student = 1 
						group by first_name, last_name, gender_id, date_of_birth 
						having count(*) > 1)",
					'type' => 'INNER',
					'conditions' => [
						'su.first_name = '.$this->aliasField('first_name'),
						'su.last_name = '.$this->aliasField('last_name'),
						'su.gender_id = '.$this->aliasField('gender_id'),
						'su.date_of_birth = '.$this->aliasField('date_of_birth')
					],
        		],
			]);
	}
}
