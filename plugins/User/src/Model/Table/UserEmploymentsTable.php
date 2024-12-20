<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class UserEmploymentsTable extends ControllerActionTable {
	public function initialize(array $config): void {
		$this->setTable('user_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Industries', ['className' => 'FieldOption.Industries', 'foreignKey' => 'industry_id']);//POCOR-7376
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['Employments' =>
                ['id','industry_id', 'security_user_id']
            ]
        ]);
        $this->addBehavior('Staff.StaffTab');
	}

	public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ]);
    }

	public function beforeAction(Event $event, ArrayObject $extra) {
        $this->setupTabElements();

		// Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Employment','Students - Professional');
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

		}else if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Employment','Staff - Professional');
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
            $is_manual_exist = $this->getManualUrl('Directory','Employment','Professional');
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

        }elseif($this->request->getParam('controller') == 'Profiles'){
            $is_manual_exist = $this->getManualUrl('Personal','Employments','Professional');
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
		$queryString = $this->getQueryString();
		if(isset($queryString['staff_id']) && !empty($queryString['staff_id'])){
			$securityUserId = $queryString['staff_id'];
		}else{
			$securityUserId = $queryString['user_id'];
		}
		$this->field('security_user_id', ['type' => 'hidden', 'value' => $securityUserId]);
	}

	private function setupTabElements() {
		$options['type'] = $this->controller->getName();
		$tabElements = $this->getProfessionalTabElements($options);
		$action = 'Employments';
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $action);
	}
	//POCOR-7376 start
	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('date_from');
        $this->field('date_to');
		$this->field('organisation');
		$this->field('position');
		$this->field('industry_id',["type"=>"select"]);
        $this->setFieldOrder([
            'date_from', 'date_to', 'organisation', 'position', 'industry_id',
        ]);
    }
}
