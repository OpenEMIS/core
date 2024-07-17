<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\Query;

use App\Model\Table\ControllerActionTable;

class StaffBehavioursTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffBehaviourCategories', ['className' => 'Staff.StaffBehaviourCategories']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']); //POCOR-6670
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);//POCOR-6670

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('Staff.StaffTab');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['visible' => false]);
        $this->field('staff_behaviour_category_id', ['type' => 'select']);
        $this->field('behaviour_classification_id', ['type' => 'select']);
        $this->field('description', ['visible' => false]);
        $this->field('action', ['visible' => false]);

        $this->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options['type'] = 'staff';
        $tabElements = $this->getCareerTabElements($options);
        $controllerName = $this->controller->getName();
        $selectedAction = 'Behaviours';
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAction);

        // Start POCOR-5188
		if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Behaviour','Staff - Career');
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
		}elseif($this->request->getParam('controller') == 'Directories'){
			$is_manual_exist = $this->getManualUrl('Directory','Behaviours','Staff - Career');
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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StaffBehaviours',
                'view',
                $this->paramsEncode(['id' => $entity->id]),
                'institution_id' => $entity->institution->id,
            ];
            $buttons['view']['url'] = $url;

            // POCOR-1893 unset the view button on profiles controller
            if ($this->controller->getName() == 'Profiles') {
                unset($buttons['view']);
            }
        }

        return $buttons;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'start_date':
                return __('Date');
            case 'time_in':
                return __('Time');
            case 'behaviour_classification_id':
                return __('Behaviour Classifications');
            case 'institution_id':
                return __('Institution');
            case 'academic_period_id':
                return __('Academic Period');
            case 'status_id':
                return __('Status');
            case 'assignee_id':
                return __('Assignees');
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
