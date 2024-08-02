<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class EmploymentStatusesTable extends ControllerActionTable {
	public function initialize(array $config): void {
        $this->setTable('staff_employment_statuses');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	    $this->belongsTo('EmploymentStatusTypes', ['className' => 'FieldOption.EmploymentStatusTypes', 'foreignKey' => 'status_type_id']);

		$this->behaviors()->get('ControllerAction')->setConfig('actions.search', false);
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '2MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
		$this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['EmploymentStatuses' =>['id', 'staff_id']]
        ]);
        $this->addBehavior('Staff.StaffTab');
	}

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->allowEmpty('file_content');
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        if($this->action == 'download'){
            return;
        }
        $this->field('status_type_id', ['type' => 'select', 'before' => 'status_date']);

		$visible = ['index' => false, 'view' => true, 'add' => true, 'edit' => true];
        $this->field('file_content', ['visible' => $visible, 'attr' => ['label' => __('Attachment')]]);

        $this->field('file_name', ['type' => 'hidden']);
        if ($this->action == 'index' || $this->action == 'view') {
        	$this->field('file_name', ['visible' => false]);
        }

		$this->setFieldOrder(['status_type_id', 'status_date', 'comment', 'file_content']);

        $this->setupTabElements();
        
        $session = $this->request->getSession();
        $controllerName = $this->controller->getName();
        if ($controllerName == 'Profiles')
        {
            $header = $session->read('Auth.User.name');
        } else {
            $userTable = TableRegistry::get('Security.Users');
            $staffId = $this->getStaffID();
            $header = $userTable->get($staffId)->name;
        }

        $header = $header . ' - ' . __('Statuses');
        $this->controller->set('contentHeader', $header);
        $alias = $this->alias;
        $this->controller->Navigation->substituteCrumb($this->getHeader($alias), __('Statuses'));
            
        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Employment Status','Staff - Career');
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
			$is_manual_exist = $this->getManualUrl('Directory','Employment Status','Staff - Career');
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
        $data['staff_id'] = $queryString['staff_id'];
        $this->field('staff_id', ['type' => 'hidden', 'value' => $data['staff_id']]);
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->getCareerTabElements($options);
        $controllerName = $this->controller->getName();
        $selectedAction = $this->getAlias();
        $this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $selectedAction);
	}

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);

        $conditions = [
            1 => ('DATEDIFF(' . $this->aliasField('status_date') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // before
            2 => ('DATEDIFF(NOW(), ' . $this->aliasField('status_date') . ')' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // after
        ];

        // will do the comparison with threshold when retrieving the absence data
        $licenseData = $this->find()
            ->select([
                'EmploymentStatusTypes.name',
                'status_date',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth'
            ])
            ->contain(['Users', 'EmploymentTypes'])
            ->where([
                $this->aliasField('status_type_id') => $thresholdArray['status_type'],
                $this->aliasField('status_date') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])
            ->enableHydration(false);

        return $licenseData->toArray();
    }

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'status_type_id') {
            return __('Status Type');
        } elseif ($field == 'status_date') {
            return __('Status Date');
        } elseif ($field == 'comment') {
            return __('Comment');
        } elseif ($field == 'file_content') {
            return __('Attachment');
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
