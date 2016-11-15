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

class ExaminationCentreRoomStudentsTable extends ControllerActionTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Students', ['className' => 'Institution.StudentUser']);
        $this->belongsTo('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms']);
        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_id', ['visible' => false]);
        $this->field('examination_centre_id', ['visible' => false]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->examination_centre_room_id . ',' . $entity->student_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }
}
