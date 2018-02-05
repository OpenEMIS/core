<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalFormsCriteriasTable extends AppTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalCriterias']);
        $this->removeBehavior('Reorder');
        // if ($this->behaviors()->has('Reorder')) {
        //     $this->behaviors()->get('Reorder')->config([
        //         'filter' => 'appraisal_form_id',
        //     ]);
        // }
    }
}
