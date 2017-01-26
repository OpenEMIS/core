<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Database\ValueBinder;
use App\Model\Table\ControllerActionTable;

class LicensesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('staff_licenses');
		parent::initialize($config);

		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
		$this->belongsTo('Assignees', ['className' => 'User.Users']);

		$this->belongsToMany('Classifications', [
            'className' => 'FieldOption.LicenseClassifications',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'staff_license_id',
            'targetForeignKey' => 'license_classification_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

		$this->addBehavior('Workflow.Workflow');
		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('HighChart', [
			'institution_staff_licenses' => [
				'_function' => 'getNumberOfStaffByLicenses'
			]
		]);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			]);
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
		$this->setFieldOrder(['license_type_id', 'license_number', 'issue_date', 'expiry_date', 'issuer']);
    }

	public function viewEditBeforeQuery(Event $event, Query $query)
	{
		$query->contain(['Users', 'LicenseTypes', 'Classifications']);
	}

	public function viewAfterAction(Event $event, Entity $entity)
	{
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity)
	{
		$this->setupFields($entity);
	}

	public function afterAction(Event $event, ArrayObject $extra)
	{
		$this->setupTabElements();
	}

	public function onUpdateFieldClassifications(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$classificationOptions = $this->Classifications->getList();

			$attr['options'] = $classificationOptions;
		}

		return $attr;
	}

	// Use for Mini dashboard (Institution Staff)
	public function getNumberOfStaffByLicenses($params=[])
	{
		$query = $params['query'];
		$table = $params['table'];

		$StaffTableQuery = clone $query;
		$staffTableInnerJoinQuery = $StaffTableQuery->select([$table->aliasField('staff_id')]);
		$staffTable = TableRegistry::get('Institution.Staff');
		$innerJoinArray = [
			'StaffUser.Staff__staff_id = '. $this->aliasField('staff_id'),
			];
		$licenseRecord = $this->find();
		$licenseCount = $licenseRecord
			->contain(['Users', 'LicenseTypes'])
			->select([
				'license' => 'LicenseTypes.name',
				'count' => $licenseRecord->func()->count($this->aliasField('staff_id'))
			])
			->join([
				'StaffUser' => [
					'table' => $staffTableInnerJoinQuery,
					'type' => 'INNER',
					'conditions' => $innerJoinArray
				]
			])
			->group('license')
			->toArray()
			;
		$dataSet = [];
		foreach ($licenseCount as $value) {
            //Compile the dataset
			$dataSet[] = [$value['license'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	private function setupTabElements()
	{
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	private function setupFields(Entity $entity)
	{
		$this->field('license_type_id', ['type' => 'select']);
		$this->field('classifications', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'classifications',
            'fieldName' => $this->alias() . '.classifications._ids',
            'placeholder' => $this->getMessage($this->aliasField('select_classification'))
        ]);

        $this->setFieldOrder(['license_type_id', 'classifications', 'license_number', 'issue_date', 'expiry_date', 'issuer']);
	}
}
