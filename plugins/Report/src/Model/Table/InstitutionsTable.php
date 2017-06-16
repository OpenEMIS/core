<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionsTable extends AppTable
{
    use OptionsTrait;
    private $classificationOptions = [];

	// filter
	const NO_FILTER = 0;
	const NO_STUDENT = 1;
	const NO_STAFF = 2;

	// For Academic / Non-Academic Institution type
	const ACADEMIC = 1;
	const NON_ACADEMIC = 0;

	public function initialize(array $config) {
		$this->table('institutions');
		parent::initialize($config);

		$this->belongsTo('Localities', 			['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
		$this->belongsTo('Types', 				['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
		$this->belongsTo('Ownerships',	 		['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
		$this->belongsTo('Statuses', 			['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
		$this->belongsTo('Sectors',				['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
		$this->belongsTo('Providers',	 		['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
		$this->belongsTo('Genders',				['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
		$this->belongsTo('Areas', 				['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('NetworkConnectivities', [
            'className' => 'Institution.NetworkConnectivities',
            'foreignKey' => 'institution_network_connectivity_id'
        ]);

		$this->addBehavior('Excel', ['excludes' => ['security_group_id'], 'pages' => false]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'model' => 'Institution.Institutions',
			'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
		$this->addBehavior('Report.InstitutionSecurity');

        $this->shiftTypes = $this->getSelectOptions('Shifts.types'); //get from options trait

        $this->classificationOptions = [
			self::ACADEMIC => 'Academic Institution',
			self::NON_ACADEMIC => 'Non-Academic Institution'
		];
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature', ['select' => false]);
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['options'] = $this->controller->getFeatureOptions($this->alias());
			$attr['onChangeReload'] = true;
			if (!(isset($this->request->data[$this->alias()]['feature']))) {
				$option = $attr['options'];
				reset($option);
				$this->request->data[$this->alias()]['feature'] = key($option);
			}
			return $attr;
		}
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('status', ['type' => 'hidden']);
		$this->ControllerAction->field('type', ['type' => 'hidden']);
		$this->ControllerAction->field('module', ['type' => 'hidden']);
		// $this->ControllerAction->field('license', ['type' => 'hidden']);
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$requestData = json_decode($settings['process']['params']);
		$feature = $requestData->feature;
		$filter = $requestData->institution_filter;
		if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
			$sheets[] = [
				'name' => $this->alias(),
				'table' => $this,
				'query' => $this->find(),
			];
			// Stop the customfieldlist behavior onExcelBeforeStart function
			$event->stopPropagation();
		}
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
	{
		$requestData = json_decode($settings['process']['params']);
		$feature = $requestData->feature;
		$filter = $requestData->institution_filter;

		$cloneFields = $fields->getArrayCopy();
		$newFields = [];
		foreach ($cloneFields as $key => $value) {
			$newFields[] = $value;
			if ($value['field'] == 'area_id') {
				$newFields[] = [
					'key' => 'Areas.code',
					'field' => 'area_code',
					'type' => 'string',
					'label' => __('Area Education Code')
				];
			}

			if ($value['field'] == 'area_administrative_id') {
				$newFields[] = [
					'key' => 'AreaAdministratives.code',
					'field' => 'area_administrative_code',
					'type' => 'string',
					'label' => __('Area Administrative Code')
				];
			}
		}

		$fields->exchangeArray($newFields);

		if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
			// Stop the customfieldlist behavior onExcelUpdateFields function
			$includedFields = ['name', 'alternative_name', 'code', 'area_code', 'area_id', 'area_administrative_code', 'area_administrative_id'];
			foreach ($newFields as $key => $value) {
				if (!in_array($value['field'], $includedFields)) {
					unset($newFields[$key]);
				}
			}
			$fields->exchangeArray($newFields);
			$event->stopPropagation();
		}
	}

    public function onExcelGetShiftType(Event $event, Entity $entity) {
        if (isset($this->shiftTypes[$entity->shift_type])) {
            return __($this->shiftTypes[$entity->shift_type]);
        } else {
            return '';
        }
    }

    public function onExcelGetClassification(Event $event, Entity $entity)
    {
        return __($this->classificationOptions[$entity->classification]);
    }

	public function onUpdateFieldInstitutionFilter(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if ($feature == 'Report.Institutions') {
				$option[self::NO_FILTER] = __('All Institutions');
				$option[self::NO_STUDENT] = __('Institutions with No Students');
				$option[self::NO_STAFF] = __('Institutions with No Staff');
				$attr['type'] = 'select';
				$attr['options'] = $option;
				$attr['onChangeReload'] = true;
				return $attr;
			} else {
				$attr['value'] = self::NO_FILTER;
			}
		}
	}

	public function onUpdateFieldLicense(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionStaff'])) {
				// need to find all types
				$typeOptions = [];
				$typeOptions[0] = __('All Licenses');

				$Types = TableRegistry::get('FieldOption.LicenseTypes');
				$typeData = $Types->getList();
				foreach ($typeData as $key => $value) {
					$typeOptions[$key] = $value;
				}

				$attr['type'] = 'select';
				$attr['options'] = $typeOptions;
				return $attr;
			} else {
				$attr['value'] = self::NO_FILTER;
			}
		}
	}

	public function onUpdateFieldModule(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionCases'])) {
				$WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();

				$attr['type'] = 'select';
				$attr['options'] = $featureOptions;
				$attr['select'] = false;
				return $attr;
			} else {
				$attr['value'] = self::NO_FILTER;
			}
		}
	}

	public function onUpdateFieldType(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionStaff'])) {
				// need to find all types
				$typeOptions = [];
				$typeOptions[0] = __('All Types');

				$Types = TableRegistry::get('Staff.StaffTypes');
				$typeData = $Types->getList();
				foreach ($typeData as $key => $value) {
					$typeOptions[$key] = $value;
				}

				$attr['type'] = 'select';
				$attr['options'] = $typeOptions;
				return $attr;
			} else {
				$attr['value'] = self::NO_FILTER;
			}
		}
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionStudents', 'Report.InstitutionStudentEnrollments', 'Report.InstitutionStaff'])) {
				// need to find all status
				$statusOptions = [];

				switch ($feature) {
					case 'Report.InstitutionStudents': case 'Report.InstitutionStudentEnrollments':
						$Statuses = TableRegistry::get('Student.StudentStatuses');
						$statusData = $Statuses->find()->select(['id', 'name'])->toArray();
						foreach ($statusData as $key => $value) {
							$statusOptions[$value->id] = $value->name;
						}
						break;

					case 'Report.InstitutionStaff':
						$Statuses = TableRegistry::get('Staff.StaffStatuses');
						$statusData = $Statuses->getList();
						foreach ($statusData as $key => $value) {
							$statusOptions[$key] = $value;
						}
						break;

					default:
						return [];
						break;
				}

				$attr['type'] = 'select';
				$attr['options'] = $statusOptions;
				return $attr;
			} else {
				$attr['value'] = self::NO_FILTER;
			}
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if (isset($request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionStudents', 'Report.InstitutionStudentTeacherRatio', 'Report.InstitutionStudentClassroomRatio'])) {
				$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
				$academicPeriodOptions = [];
				$academicPeriodData = $InstitutionStudentsTable->find()
					->matching('AcademicPeriods')
					->select(['id' => $InstitutionStudentsTable->aliasField('academic_period_id'), 'name' => 'AcademicPeriods.name'])
					->group('id')
					->toArray()
					;

				foreach ($academicPeriodData as $key => $value) {
					$academicPeriodOptions[$value->id] = $value->name;
				}

				// $attr['onChangeReload'] = true;
				$attr['options'] = $academicPeriodOptions;
				$attr['type'] = 'select';
				$attr['select'] = false;

				if (empty($request->data[$this->alias()]['academic_period_id'])) {
					reset($academicPeriodOptions);
					$request->data[$this->alias()]['academic_period_id'] = key($academicPeriodOptions);
				}
				return $attr;
			} else if (in_array($feature, ['Report.StaffAbsences', 'Report.StudentAbsences', 'Report.StaffLeave', 'Report.InstitutionCases'])
				|| (in_array($feature, ['Report.Institutions'])
					&& !empty($request->data[$this->alias()]['institution_filter'])
					&& $request->data[$this->alias()]['institution_filter'] == self::NO_STUDENT)
				) {
				$academicPeriodOptions = [];
				$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
				$periodOptions = $AcademicPeriodTable->getList();

				$academicPeriodOptions = $academicPeriodOptions + $periodOptions;

				// $attr['onChangeReload'] = true;
				$attr['options'] = $academicPeriodOptions;
				$attr['type'] = 'select';
				$attr['select'] = false;

				if (empty($request->data[$this->alias()]['academic_period_id'])) {
					reset($academicPeriodOptions);
					$request->data[$this->alias()]['academic_period_id'] = key($academicPeriodOptions);
				}
				return $attr;
			}
		}
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$requestData = json_decode($settings['process']['params']);
		$filter = $requestData->institution_filter;
		$superAdmin = $requestData->super_admin;
		$userId = $requestData->user_id;
		$query
			->contain(['Areas', 'AreaAdministratives'])
			->select(['area_code' => 'Areas.code', 'area_administrative_code' => 'AreaAdministratives.code']);
		switch ($filter) {
			case self::NO_STUDENT:
                $StudentsTable = TableRegistry::get('Institution.Students');
				$academicPeriodId = $requestData->academic_period_id;

				$query
					->leftJoin(
						[$StudentsTable->alias() => $StudentsTable->table()],
						[
							$StudentsTable->aliasField('institution_id') . ' = '. $this->aliasField('id'),
                            $StudentsTable->aliasField('academic_period_id') => $academicPeriodId
						]
					)
					->select(['student_count' => $query->func()->count('Students.id')])
					->group([$this->aliasField('id')])
					->having(['student_count' => 0]);
				break;

			case self::NO_STAFF:
				$query
					->leftJoin(
						['Staff' => 'institution_staff'],
						[$this->aliasField('id').' = Staff.institution_id']
					)
					->select(['staff_count' => $query->func()->count('Staff.id')])
					->group([$this->aliasField('id')])
					->having(['staff_count' => 0]);
				break;

			case self::NO_FILTER:
				break;
		}
		if (!$superAdmin) {
			$query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('id')]);
		}
	}
}
