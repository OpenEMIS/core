<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use App\Model\Table\ControllerActionTable;

class MembershipsTable extends ControllerActionTable {
	public function initialize(array $config): void {
		$this->setTable('staff_memberships');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->addBehavior('User.UserTab', [
            'appliedAction' => [
				'StaffMemberships' => ['id', 'staff_id'], 
				'Memberships' => ['id', 'staff_id']// for staff
            ]
        ]);
        $this->addBehavior('Staff.StaffTab');
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		$validator->setProvider('custom', $this);
		return $validator
			->requirePresence('membership', true)
			->notEmptyString('membership', __('This field cannot be left empty'))
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			]);
	}

	public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        if (!empty($queryString['staff_id'])) {
            $staffId = $queryString['staff_id'];
        } elseif (!empty($queryString['user_id'])) {
            $staffId = $queryString['user_id'];
        } elseif (!empty($queryString['id'])) {
            $staffId = $queryString['id'];
        } else {
            $staffId = null;
        }
        $this->field('staff_id', ['type' => 'hidden', 'value' => $staffId]);
    }

	private function setupTabElements() {
		$tabElements = $this->getProfessionalTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->getAlias());
	}

	public function afterAction(EventInterface $event, ArrayObject $extra) {
		$this->setFieldOrder(['membership', 'issue_date', 'expiry_date', 'comment']);
		$this->setupTabElements();

		// Start POCOR-5188
		if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Memberships','Staff - Professional');
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
			$is_manual_exist = $this->getManualUrl('Directory','Memberships','Staff - Professional');
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

	public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'membership') {
            return __('Membership');
        } elseif ($field == 'issue_date') {
            return __('Issue Date');
        } elseif ($field == 'expiry_date') {
            return __('Expiry Date');
        } elseif ($field == 'comment') {
            return __('Comment');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
        } elseif ($field == 'validation_rule') {
            return __('Validation Rule');
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
}
