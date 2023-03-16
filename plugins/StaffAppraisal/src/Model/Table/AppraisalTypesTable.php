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
    // Start POCOR-5188
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $is_manual_exist = $this->getManualUrl('Administration','Types','Staff Appraisals');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
    }
    // End POCOR-5188
}
