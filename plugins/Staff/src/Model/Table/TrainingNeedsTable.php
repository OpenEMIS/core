<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Staff\Model\Table\TrainingNeedsAppTable;

class TrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_needs');
        parent::initialize($config);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Institution.StaffTrainingNeeds']);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
        // start pocor-5188
        $is_manual_exist = $this->getManualUrl('Directory','Guardian Relation','Students - Guardians');       
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
         // end pocor-5188
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
