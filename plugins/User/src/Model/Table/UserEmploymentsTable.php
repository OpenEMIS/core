<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Log\Log;
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

    // The bootstrap-datepicker "date" fields (date_from/date_to) render/accept text in whatever
    // format is configured in System Configurations > Date Format (e.g. "July 31, 2026"), not just
    // 'Y-m-d'. Cake's DateType::marshal() only ever accepts the strict 'Y-m-d' format, so with any
    // other configured format the submitted value was marshalled to null - and since date_from has
    // no default, saving then failed with "Field 'date_from' doesn't have a default value".
    // Normalize the submitted value to 'Y-m-d' here, before patchEntity()/marshal() runs.
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $systemDateFormat = $ConfigItems->value('date_format') ?: 'd-m-Y';
        // bootstrap-datepicker cannot emit ordinals; parse without "S" (strip legacy "31st" input too)
        $editableDateFormat = preg_replace('/\s+/', ' ', trim(str_replace('S', '', $systemDateFormat))) ?: 'd-m-Y';

        foreach (['date_from', 'date_to'] as $field) {
            if (!array_key_exists($field, (array) $data) || empty($data[$field])) {
                continue;
            }

            $rawValue = $data[$field];
            if (!is_string($rawValue) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawValue)) {
                // already Y-m-d (or not a string we can parse) - leave untouched
                continue;
            }

            $normalized = preg_replace('/(\d+)(st|nd|rd|th)\b/i', '$1', $rawValue);

            try {
                try {
                    $date = \Cake\Chronos\Chronos::createFromFormat($editableDateFormat, $normalized);
                } catch (\Exception $e) {
                    $date = \Cake\Chronos\Chronos::createFromFormat($systemDateFormat, $rawValue);
                }
                if ($date !== false && $date !== null) {
                    $data[$field] = $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                Log::warning("UserEmploymentsTable: Invalid date '{$rawValue}' for field '{$field}' with format '{$systemDateFormat}'");
            }
        }
    }

	public function beforeAction(EventInterface $event, ArrayObject $extra) {
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
	public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
