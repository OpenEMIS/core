<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\Utility\Security;
use Cake\ORM\Entity;

class ExaminationCentreRoomsExaminationsStudentsTable extends ControllerActionTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentreRoomsExaminations', [
            'className' => 'Examination.ExaminationCentreRoomsExaminations',
            'foreignKey' => ['examination_centre_room_id', 'examination_id']
        ]);
        $this->belongsTo('ExaminationCentresExaminationsStudents', [
            'className' => 'Examination.ExaminationCentresExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id']
        ]);

        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('CompositeKey');
        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_id', ['visible' => false]);
        $this->field('examination_centre_id', ['visible' => false]);
    }
}
