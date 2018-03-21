<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalDropdownOptionsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
        $this->hasMany('AppraisalDropdownAnswers', [
            'className' => 'StaffAppraisal.AppraisalDropdownAnswers',
            'foreignKey' => 'appraisal_dropdown_option_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }
}
