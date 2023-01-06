<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class ExaminationCentreRoomsExaminationsInvigilatorsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
		$this->belongsTo('Invigilators', ['className' => 'User.Users']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentreRoomsExaminations', [
            'className' => 'Examination.ExaminationCentreRoomsExaminations',
            'foreignKey' => ['examination_centre_room_id', 'examination_id']
        ]);
        $this->belongsTo('ExaminationCentresExaminationsInvigilators', [
            'className' => 'Examination.ExaminationCentresExaminationsInvigilators',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'invigilator_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'invigilator_id']
        ]);

        $this->addBehavior('CompositeKey');
	}
}
