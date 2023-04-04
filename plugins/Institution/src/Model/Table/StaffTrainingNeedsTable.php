<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Staff\Model\Table\TrainingNeedsAppTable;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

use Cake\Datasource\ConnectionManager; // POCOR-7158

class StaffTrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config)
    { 
        parent::initialize($config);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Excel',[
            'excludes' => ['reason','training_need_competency_id','training_need_sub_standard_id','training_priority_id'],
            'pages' => ['index'],
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        /** Start POCOR-7158 */
        $connection = ConnectionManager::get('default');
        $connection->execute('SET foreign_key_checks = 0');
        /** End POCOR-7158 */

        $modelAlias = 'Needs';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);

        // redirect to staff index page if session not found
        $session = $this->request->session();
        $sessionKey = 'Staff.Staff.id';

        if (!$session->check($sessionKey)) {
            $url = $this->url('index');
            $url['plugin'] = 'Institution';
            $url['controller'] = 'Institutions';
            $url['action'] = 'Staff';

            $event->stopPropagation();
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($url);
        }
        // End

                        
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Needs','Staff - Training');       
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
		// End POCOR-5188
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
        /** Start POCOR-7158 */
        $connection = ConnectionManager::get('default');
        $connection->execute('SET foreign_key_checks = 1');
        /** End POCOR-7158 */
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    // POCOR-6137 start
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');
        $status = $this->request->query('category');
        $workflowSteps = TableRegistry::get('workflow_steps');

        $query
        ->innerJoin([$workflowSteps->alias() => $workflowSteps->table()],[
            $workflowSteps->aliasField('id = ').$this->aliasField('status_id')
        ])
        ->where([
            $this->aliasField('staff_id') => $staffId
        ]);

        if($status > 0){
            $query
            ->where([
                $workflowSteps->aliasField('category = ') => $status
            ]); 
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'StaffTrainingNeeds.status_id',
            'field' => 'status_id',
            'type'  => 'string',
            'label' => __('Status')
        ];

        $extraField[] = [
            'key'   => 'StaffTrainingNeeds.assignee_id',
            'field' => 'assignee_id',
            'type'  => 'string',
            'label' => __('Assignee')
        ];
        $extraField[] = [
            'key'   => 'StaffTrainingNeeds.type',
            'field' => 'type',
            'type'  => 'string',
            'label' => __('Type')
        ];
        $extraField[] = [
            'key'   => 'StaffTrainingNeeds.training_course_id',
            'field' => 'training_course_id',
            'type'  => 'string',
            'label' => __('Training Course')
        ];
        $extraField[] = [
            'key'   => 'StaffTrainingNeeds.training_need_category_id',
            'field' => 'training_need_category_id',
            'type'  => 'string',
            'label' => __('Training Need Category')
        ];
        $fields->exchangeArray($extraField);
    }
    // POCOR-6137 end
}
