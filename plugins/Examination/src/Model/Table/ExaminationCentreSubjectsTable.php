<?php
namespace Examination\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\Validation\Validator;

class ExaminationCentreSubjectsTable extends AppTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
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
