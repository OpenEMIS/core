<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Utility\Security;

use App\Model\Table\AppTable;

class ExaminationItemResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationGradingOptions', ['className' => 'Examination.ExaminationGradingOptions']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add']
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->academic_period_id . ',' . $entity->examination_id . ',' . $entity->education_subject_id . ',' . $entity->student_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }
}
