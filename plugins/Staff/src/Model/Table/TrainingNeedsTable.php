<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Staff\Model\Table\TrainingNeedsAppTable;

class TrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_training_needs');
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
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'type':
                return __('Type');
            case 'status_id':
                return __('Status');
            case 'training_course_id':
                return __('Training Course');
            case 'training_need_category_id':
                return __('Training Need Category');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
}
