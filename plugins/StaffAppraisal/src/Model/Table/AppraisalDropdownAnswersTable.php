<?php
namespace StaffAppraisal\Model\Table;

use App\Model\Table\AppTable;

class AppraisalDropdownAnswersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias', 'foreignKey' => 'appraisal_criteria_id']);
        $this->belongsTo('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id', 'joinType' => 'INNER']);
        $this->belongsTo('AppraisalDropdownOptions', ['className' => 'StaffAppraisal.AppraisalDropdownOptions', 'foreignKey' => 'appraisal_dropdown_option_id']);
    }
}
