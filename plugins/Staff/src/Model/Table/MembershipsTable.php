<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class MembershipsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_memberships');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			]);
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->setFieldOrder(['membership', 'issue_date', 'expiry_date', 'comment']);
		$this->setupTabElements();

		// Start POCOR-5188
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
		// End POCOR-5188
	}
}
