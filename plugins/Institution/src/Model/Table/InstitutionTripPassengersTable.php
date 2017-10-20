<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Utility\Text;
use App\Model\Table\AppTable;

class InstitutionTripPassengersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('Students', ['className' => 'User.Users']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('InstitutionTrips', ['className' => 'Institution.InstitutionTrips']);
    }

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
    	if ($entity->isNew()) {
			$entity->id = Text::uuid();
    	}
    }
}
