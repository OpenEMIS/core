<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Entity;

class AppraisalFormsCriteriasTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->hasMany('AppraisalTextAnswers', [
            'className' => 'StaffAppraisal.AppraisalTextAnswers',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalSliderAnswers', [
            'className' => 'StaffAppraisal.AppraisalSliderAnswers',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalDropdownAnswers', [
            'className' => 'StaffAppraisal.AppraisalDropdownAnswers',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalNumberAnswers', [
            'className' => 'StaffAppraisal.AppraisalNumberAnswers',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('AppraisalScoreAnswers', [
            'className' => 'StaffAppraisal.AppraisalScoreAnswers',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasOne('AppraisalFormsCriteriasScores', [
            'className' => 'StaffAppraisal.AppraisalFormsCriteriasScores', 
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id']]);

        $this->addBehavior('CompositeKey');
        $this->removeBehavior('Reorder');
    }
}
