<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class PotentialStaffDuplicatesTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		$this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
		$this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

		$this->addBehavior('Excel', [
			'excludes' => ['is_student', 'is_staff', 'is_guardian', 'external_reference', 'super_admin', 'status', 'last_login', 'photo_name', 'photo_content', 'preferred_language'],
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
			->where([$this->aliasField('is_staff') => 1])
			->group([
				$this->aliasField('first_name'),
				$this->aliasField('last_name'),
				$this->aliasField('gender_id'),
				$this->aliasField('date_of_birth')
			])
			->having(['COUNT(*) > ' => 1]);
	}
}
