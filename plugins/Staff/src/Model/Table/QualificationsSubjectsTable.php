<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;
use ArrayObject;

class QualificationsSubjectsTable extends AppTable {
	public function initialize(array $config) 
	{
		$this->table('staff_qualifications_subjects');
		parent::initialize($config);
		$this->belongsTo('StaffQualifications', ['className' => 'Staff.Qualifications']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

		$this->addBehavior('CompositeKey');
	}
}