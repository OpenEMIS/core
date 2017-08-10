<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;
use ArrayObject;

class QualificationsSpecialisationsFieldOfStudiesTable extends AppTable {
	public function initialize(array $config) 
	{
		$this->table('qualification_specialisations_field_of_studies');
		parent::initialize($config);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);

		$this->addBehavior('CompositeKey');
	}
}