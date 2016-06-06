<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffQualificationsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('staff_qualifications');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		$this->belongsTo('QualificationInstitutions', ['className' => 'Staff.QualificationInstitutions']);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);
		
		$this->addBehavior('Excel', [
			'excludes' => [
				'file_name'
			]
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
}
