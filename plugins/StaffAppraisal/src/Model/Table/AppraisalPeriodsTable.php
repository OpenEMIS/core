<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class AppraisalPeriodsTable extends ControllerActionTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsToMany('AppraisalTypes', [
            'className' => 'StaffAppraisal.AppraisalTypes',
            'joinTable' => 'appraisal_periods_types',
            'foreignKey' => 'appraisal_period_id',
            'targetForeignKey' => 'appraisal_type_id',
            'through' => 'StaffAppraisal.AppraisalPeriodsTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalStatusTypes', ['className' => 'StaffAppraisal.AppraisalStatusTypes']);
        $this->setDeleteStrategy('restrict');
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('appraisal_form_id', ['type' => 'select']);

        $this->field('appraisal_type_id', ['type' => 'chosenSelect', 'options' => [], 'attr' => ['required' => true]]);
    }
}
