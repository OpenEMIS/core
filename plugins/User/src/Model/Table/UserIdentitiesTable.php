<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Exception;
use DateTime;

class UserIdentitiesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
	}

	public function beforeAction($event) {
		$this->fields['identity_type_id']['type'] = 'select';
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.beforeAction'] = 'beforeAction';
		// $events['ControllerAction.afterAction'] = 'afterAction';
		// $events['ControllerAction.beforePaginate'] = 'beforePaginate';
		// $events['ControllerAction.beforeAdd'] = 'beforeAdd';
		// $events['ControllerAction.beforeView'] = 'beforeView';
		return $events;
	}

	public function validationDefault(Validator $validator)
	{

		// 'identity_type_id' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'required' => true,
		// 		'message' => 'Please select a Type'
		// 	)
		// ),
		// 'number' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'required' => true,
		// 		'message' => 'Please enter a valid Number'
		// 	)
		// ),
		// 'issue_location' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'message' => 'Please enter a valid Issue Location'
		// 	)
		// ),
		// 'issue_date' => array(
		// 	'comparison' => array(
		// 		'rule' => array('compareDate', 'expiry_date'),
		// 		'allowEmpty' => true,
		// 		'message' => 'Issue Date Should be Earlier Than Expiry Date'
		// 	)
		// ),
		// 'expiry_date' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'message' => 'Expiry Date Is Required'
		// 	)
		// )
		// return $validator->add('issue_location', 'custom', [
		//     'rule' => [$this, 'customFunction'],
		//     'message' => 'asd'
		// ]);

		$validator = parent::validationDefault($validator);

		return $validator
			->requirePresence('identity_type_id')
			->notEmpty('identity_type_id', 'Please select a Type')
			->requirePresence('number')
			->notEmpty('number', 'Please enter a valid Number')
			// ->requirePresence('issue_location')
			// ->notEmpty('issue_location', 'Please enter a valid Issue Location')

			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			
			// ->requirePresence('expiry_date')
			// ->notEmpty('expiry_date', 'Expiry Date Is Required')
		;
	}

	public function customFunction($value,$context){
        //some logic here
        return false;
    }

}
