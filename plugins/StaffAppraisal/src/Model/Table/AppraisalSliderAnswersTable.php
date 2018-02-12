<?php
namespace StaffAppraisal\Model\Table;

use App\Model\Table\AppTable;

class AppraisalSliderAnswersTable extends AppTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionStaffAppraisals', ['className' => 'Institution.InstitutionStaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id']);
        $this->belongsTo('AppraisalFormsCriterias', ['className' => 'StaffAppraisal.AppraisalFormsCriterias', 'foreignKey' => 'appraisal_forms_criteria_id']);
    }
}
