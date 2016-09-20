<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class ExaminationCentreSubjectsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('examination_centre_subjects');
        parent::initialize($config);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
    }

    public function addEditBeforeAction(Event $event) {

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {

    }
}
