<?php
namespace Student\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;

class ProgrammesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('Institutions', ['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_site_id']);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';

		$this->ControllerAction->setFieldOrder([
			'institution_site_id', 'education_programme_id', 'start_date', 'end_date', 'student_status_id'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain([], true);
		$query->contain(['StudentStatuses', 'EducationProgrammes', 'Institutions']);
		$query->select([
			$this->aliasField('start_date'),
			$this->aliasField('end_date'),
			'Institutions.name',
			'StudentStatuses.name',
			'EducationProgrammes.name'
		]);
	}
}
