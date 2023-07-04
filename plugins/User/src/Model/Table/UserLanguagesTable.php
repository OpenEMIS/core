<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

use Cake\Datasource\ConnectionManager;

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
		// Start POCOR-4824

		// $gradeOptions = array();
		// for ($i = 0; $i < 8; $i++) {
		// 	$gradeOptions[$i] = $i;
		// }
		// return $gradeOptions;

		$connection = ConnectionManager::get('default');
		$res= $connection->execute('Select * from language_proficiencies order by name ASC');
		$rows = $res->fetchAll('assoc');
		$lp = [];
		if(!empty($rows)){
			foreach($rows as $key => $value){
				$lp[$value['name']] =  $value['name'];
			}
		}
		return $lp;
		// END POCOR-4824
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('listening', 'ruleRange', [
				'rule' => ['range', -1, 100]	// POCOR-4824
			])
			->add('speaking', 'ruleRange', [
				'rule' => ['range', -1, 100]	// POCOR-4824
			])
			->add('reading', 'ruleRange', [
				'rule' => ['range', -1, 100]	// POCOR-4824
			])
			->add('writing', 'ruleRange', [
				'rule' => ['range', -1, 100]	// POCOR-4824
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
		// Start POCOR-5188
        if($this->request->params['controller'] == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Languages','Staff - General');       
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
        }elseif($this->request->params['controller'] == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Languages','Students - General');       
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
            $is_manual_exist = $this->getManualUrl('Directory','Languages','General');       
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
            $is_manual_exist = $this->getManualUrl('Personal','Languages','General');       
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
    /*POCOR-6267 Ends*/
}
