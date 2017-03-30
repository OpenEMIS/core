<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ExaminationCentreRoomsExaminationsTable extends ControllerActionTable {
    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', ['className' => 'Examination.ExaminationCentreRoomsExaminationsInvigilators', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', ['className' => 'Examination.ExaminationCentreRoomsExaminationsStudents', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('CompositeKey');
    }
}
