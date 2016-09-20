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
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
    }

    public function addEditBeforeAction(Event $event) {

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {

    }

    public function getExaminationCentreSubjects($examinationCentreId)
    {
        $subjectList = $this
            ->find('list', [
                    'keyField' => 'subject_id',
                    'valueField' => 'subject_name'
            ])
            ->matching('EducationSubjects')
            ->select([
                'subject_name' => 'EducationSubjects.name',
                'subject_id' => $this->aliasField('education_subject_id')
            ])
            ->where([$this->aliasField('examination_centre_id') => $examinationCentreId])
            ->toArray();
        return $subjectList;
    }
}
