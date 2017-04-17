<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsSubjectsStudentsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Students', ['className' => 'Institution.StudentUser']);
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentresExaminationsStudents', [
            'className' => 'Examination.ExaminationCentresExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id']
        ]);
        $this->belongsTo('InstitutionExaminationStudents', [
            'className' => 'Institution.InstitutionExaminationStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id']
        ]);
        $this->belongsTo('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'foreignKey' => ['examination_centre_id', 'examination_item_id']
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['add']
        ]);
        $this->addBehavior('CompositeKey');
    }
}
