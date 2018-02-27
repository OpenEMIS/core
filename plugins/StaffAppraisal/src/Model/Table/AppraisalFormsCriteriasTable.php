<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

class AppraisalFormsCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);

        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'appraisal_form_id',
            ]);
        }
    }
}
