<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class UserLanguagesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);

        $this->behaviors()->get('ControllerAction')->config('actions.search', false);
        $this->addBehavior('User.SetupTab');

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Languages', ['className' => 'Languages']);
	}

	public function beforeAction($event) {
		$this->fields['language_id']['type'] = 'select';
		$gradeOptions = $this->getGradeOptions();
		$this->fields['listening']['type'] = 'select';
		$this->fields['listening']['options'] = $gradeOptions;
		$this->fields['listening']['translate'] = false;
		$this->fields['speaking']['type'] = 'select';
		$this->fields['speaking']['options'] = $gradeOptions;
		$this->fields['speaking']['translate'] = false;
		$this->fields['reading']['type'] = 'select';
		$this->fields['reading']['options'] = $gradeOptions;
		$this->fields['reading']['translate'] = false;
		$this->fields['writing']['type'] = 'select';
		$this->fields['writing']['options'] = $gradeOptions;
		$this->fields['writing']['translate'] = false;
	}

	public function getGradeOptions() {
		$gradeOptions = array();
		for ($i = 0; $i < 6; $i++) {
			$gradeOptions[$i] = $i;
		}
		return $gradeOptions;
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('listening', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
			->add('speaking', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
			->add('reading', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
			->add('writing', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
		;
	}

	/*POCOR-6267 Starts*/
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $queryString = $this->getQueryString();
        if (!empty($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        } else {
            $userId = $session->read('Student.Students.id');
        }

        $query->where([$this->aliasField('security_user_id') => $userId]);
    }
    /*POCOR-6267 Ends*/
}
