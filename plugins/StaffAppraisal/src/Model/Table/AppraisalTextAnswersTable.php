<?php
namespace StaffAppraisal\Model\Table;

use App\Model\Table\ControllerActionTable;

class AppraisalTextAnswersTable extends ControllerActionTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionStaffAppraisals', ['className' => 'Institution.InstitutionStaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id', 'joinType' => 'INNER']);
        $this->belongsTo('AppraisalFormsCriterias', ['className' => 'StaffAppraisal.AppraisalFormsCriterias', 'foreignKey' => 'appraisal_forms_criteria_id', 'joinType' => 'INNER']);
    }
}
