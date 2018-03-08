<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class AppraisalTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsToMany('AppraisalPeriods', [
            'className' => 'StaffAppraisal.AppraisalPeriods',
            'foreignKey' => 'appraisal_type_id',
            'targetForeignKey' => 'appraisal_period_id',
            'joinTable' => 'appraisal_periods_types',
            'through' => 'StaffAppraisal.AppraisalPeriodsTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'appraisal_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AppraisalPeriods->alias()
        ];
    }
}
