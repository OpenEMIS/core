<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class ExaminationCentresInvigilatorsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
		$this->belongsTo('Invigilators', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
	}

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->examination_centre_id . ',' . $entity->invigilator_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }
}
