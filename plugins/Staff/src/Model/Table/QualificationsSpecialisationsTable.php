<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\EventInterface;
use ArrayObject;

class QualificationsSpecialisationsTable extends AppTable {
	public function initialize(array $config) : void
	{
		$this->setTable('staff_qualifications_specialisations');
		parent::initialize($config);
		$this->belongsTo('StaffQualifications', ['className' => 'Staff.Qualifications']);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);

		$this->addBehavior('CompositeKey');
	}
}