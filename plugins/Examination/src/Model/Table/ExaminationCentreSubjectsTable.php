<?php
namespace Examination\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\ORM\Entity;

class ExaminationCentreSubjectsTable extends AppTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->examination_centre_id . ',' . $entity->examination_item_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
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
