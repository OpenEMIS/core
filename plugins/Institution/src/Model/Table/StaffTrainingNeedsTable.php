<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Staff\Model\Table\TrainingNeedsAppTable;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

use Cake\Datasource\ConnectionManager; // POCOR-7158

class StaffTrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Excel',[
            'excludes' => ['reason','training_need_competency_id','training_need_sub_standard_id','training_priority_id'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffTrainingNeeds' =>['id']
            ]
        ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        //echo "<pre>"; print_r($queryString); die;
        /** Start POCOR-7158 */
        $connection = ConnectionManager::get('default');
        $connection->execute('SET foreign_key_checks = 0');
        /** End POCOR-7158 */

        $modelAlias = 'Needs';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);

        // redirect to staff index page if session not found
        $session = $this->request->getSession();
        $sessionKey = 'Staff.Staff.id';

        if (!$session->check($sessionKey)) {
            $url = $this->url('index');
            $url['plugin'] = 'Institution';
            $url['controller'] = 'Institutions';
            $url['action'] = 'Staff';
            $url['0'] = $encodedQueryString;

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

    public function afterAction(EventInterface $event, ArrayObject $extra)
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
        $this->controller->set('selectedAction', $this->getAlias());
    }

    // POCOR-6137 start
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession(); //POCOR-9584: CakePHP 5 - getSession()
        $staffId = $this->getStaffID(); //POCOR-9584: CakePHP 5 - getStaffID()
        $status = $this->request->getQuery('category'); //POCOR-9584: CakePHP 5 - getQuery()
        $workflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps'); //POCOR-9584: CakePHP 5 - namespaced plugin model

        $query
        ->innerJoin([$workflowSteps->getAlias() => $workflowSteps->getTable()],[ //POCOR-9584: CakePHP 5 - getAlias()/getTable()
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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'type') {
            return __('Type');
        } elseif ($field == 'training_course_id') {
            return __('Training Course');
        } elseif ($field == 'course_code') {
            return __('Course Code');
        } elseif ($field == 'course_name') {
            return __('Course Name');
        } elseif ($field == 'course_description') {
            return __('Course Description');
        } elseif ($field == 'training_requirement_id') {
            return __('Training Requirement');
        } elseif ($field == 'training_priority_id') {
            return __('Training Priority');
        } elseif ($field == 'reason') {
            return __('Reason');
        } elseif ($field == 'status_id') {
            return __('Status');
        } elseif ($field == 'status_id') {
            return __('Status');
        } elseif ($field == 'training_need_category_id') {
            return __('Training Need Category');
        } elseif ($field == 'assignee_id') {
            return __('Assignee');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    // POCOR-6137 end
}
