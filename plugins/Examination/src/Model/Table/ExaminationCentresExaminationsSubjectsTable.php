<?php
namespace Examination\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\ORM\Entity;

class ExaminationCentresExaminationsSubjectsTable extends AppTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsStudents', [
            'className' => 'Examination.ExaminationCentresExaminationsStudents',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_item_id'],
            'targetForeignKey' => ['examination_centre_id', 'student_id', 'examination_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
    }

    public function getExaminationCentreSubjects($examinationCentreId)
    {
        $subjectList = $this
            ->find('list', [
                'keyField' => 'examination_item_id',
                'valueField' => 'education_subject_id'
            ])
            ->where([$this->aliasField('examination_centre_id') => $examinationCentreId])
            ->toArray();
        return $subjectList;
    }
}
