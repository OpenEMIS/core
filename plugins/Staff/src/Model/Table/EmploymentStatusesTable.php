<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class EmploymentStatusesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_employment_statuses');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	    $this->belongsTo('EmploymentStatusTypes', ['className' => 'FieldOption.EmploymentStatusTypes', 'foreignKey' => 'status_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.search', false);
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '2MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
    }

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('status_type_id', ['type' => 'select', 'before' => 'status_date']);

		$visible = ['index' => false, 'view' => true, 'add' => true, 'edit' => true];
        $this->field('file_content', ['visible' => $visible, 'attr' => ['label' => __('Attachment')]]);

        $this->field('file_name', ['type' => 'hidden']);
        if ($this->action == 'index' || $this->action == 'view') {
        	$this->field('file_name', ['visible' => false]);
        }

		$this->setFieldOrder(['status_type_id', 'status_date', 'comment', 'file_content']);

        $this->setupTabElements();

        $session = $this->request->session();
        $controllerName = $this->controller->name;     
        if ($controllerName == 'Profiles')
        {
            $header = $session->read('Auth.User.name');
        } else {        
            $header = $session->read('Staff.Staff.name');
        }
        
        $header = $header . ' - ' . __('Statuses');
        $this->controller->set('contentHeader', $header);
        $alias = $this->alias;
        $this->controller->Navigation->substituteCrumb($this->getHeader($alias), __('Statuses'));
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
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
            ->hydrate(false)
            ;

        return $licenseData->toArray();
    }
}
