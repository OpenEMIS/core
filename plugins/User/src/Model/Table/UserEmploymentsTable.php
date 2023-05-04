<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class UserEmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('user_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Industries', ['className' => 'FieldOption.Industries', 'foreignKey' => 'industry_id']);//POCOR-7376
	}

	public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ]);
    }

	public function beforeAction(Event $event, ArrayObject $extra) {
        $this->setupTabElements();
		
		// Start POCOR-5188
		if($this->request->params['controller'] == 'Students'){
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

		}else if($this->request->params['controller'] == 'Staff'){
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

		}elseif($this->request->params['controller'] == 'Directories'){ 
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

        }elseif($this->request->params['controller'] == 'Profiles'){ 
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
	}

	private function setupTabElements() {
		$options['type'] = $this->controller->name;
		$tabElements = $this->controller->getProfessionalTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', __('Employments'));
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
