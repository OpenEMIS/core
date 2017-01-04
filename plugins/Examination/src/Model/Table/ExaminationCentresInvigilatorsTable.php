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
        $this->hasOne('ExaminationCentreRoomsInvigilators', ['className' => 'Examination.ExaminationCentreRoomsInvigilators', 'foreignKey' => ['examination_centre_id', 'invigilator_id'], 'dependent' => true, 'cascadeCallBack' => true]);

        $this->addBehavior('CompositeKey');
	}
}
