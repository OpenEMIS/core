<?php
namespace StaffAppraisal\Model\Table;

use App\Model\Table\AppTable;


class AppraisalFormsCriteriasScoresLinksTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AppraisalForms', [
            'className' => 'StaffAppraisal.AppraisalForms',
            'foreignKey' => ['appraisal_form_id'],

        ]);
        
        $this->belongsTo('AppraisalFormsCriterias', [
            'className' => 'StaffAppraisal.AppraisalFormsCriterias',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsTo('AppraisalFormsCriteriasLinks', [
            'className' => 'StaffAppraisal.AppraisalFormsCriterias',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_linked_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }
}
