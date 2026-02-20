<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Staff\Model\Table\TrainingNeedsAppTable;
use Cake\Datasource\ConnectionManager;

class TrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_training_needs');
        parent::initialize($config);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Institution.StaffTrainingNeeds']);
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['TrainingNeeds' =>
                ['id'],
            ]
        ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra) {
        $connection = ConnectionManager::get('default');
        $connection->execute('SET foreign_key_checks = 0');
        $session = $this->request->getSession();
        $queryString = $this->getQueryString();
        $data['staff_id'] = $queryString['staff_id'];
        if(empty($data['staff_id'])){
            $data['staff_id'] = $session->read('Auth.User.id');
        }
        $this->field('staff_id', ['type' => 'hidden', 'value' => $data['staff_id']]);
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
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
            case 'training_priority_id':
                return __('Training Priority');
            case 'reason':
                return __('Reason');
            case 'assignee_id':
                return __('Assignee'); 
            case 'course_code':
                return __('Course Code'); 
            case 'course_name':  
                return __('Course Name'); 
            case 'course_description':  
                return __('Course Description');
            case 'training_need_competency_id':  
                return __('Training Need Competency'); 
            case 'training_need_standard_id':  
                return __('Training Need Standard');
            case 'training_need_sub_standard_id':  
                return __('Training Need Sub Standard'); 
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
}
