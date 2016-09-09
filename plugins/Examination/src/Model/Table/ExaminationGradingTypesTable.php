<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class ExaminationGradingTypesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        parent::initialize($config);

        // $this->hasMany('GradingOptions', ['className' => 'Assessment.AssessmentGradingOptions', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'examination_items_grading_types',
            'foreignKey' => 'examination_grading_type_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Examination.ExaminationItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('Examinations', [
            'className' => 'Examination.Examinations',
            'joinTable' => 'examination_items_grading_types',
            'foreignKey' => 'examination_grading_type_id',
            'targetForeignKey' => 'assessment_id',
            'through' => 'Examination.ExaminationItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
    }
}
