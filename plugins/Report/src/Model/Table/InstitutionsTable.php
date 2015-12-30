<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionsTable extends AppTable  {

	// filter
	const NO_FILTER = 0;
	const NO_STUDENT = 1;
	const NO_STAFF = 2;

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
		
		$this->addBehavior('Excel', ['excludes' => ['security_group_id'], 'pages' => false]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'model' => 'Institution.Institutions',
			'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
		]);
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
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
		// $this->ControllerAction->field('license', ['type' => 'hidden']);
		$this->ControllerAction->field('leaveDate', ['type' => 'hidden']);
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

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$requestData = json_decode($settings['process']['params']);
		$feature = $requestData->feature;
		$filter = $requestData->institution_filter;
		if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
			// Stop the customfieldlist behavior onExcelUpdateFields function
			$copyField = $fields->getArrayCopy();
			$includedFields = ['name', 'alternative_name', 'code', 'area_id'];
			foreach ($copyField as $key => $value) {
				if (!in_array($value['field'], $includedFields)) {
					unset($copyField[$key]);
				}
			}
			$fields->exchangeArray($copyField);
			$event->stopPropagation();
		}
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
				return $attr;
			} else {
				$attr['value'] = self::NO_FILTER;
			}
		}
	}

	public function onUpdateFieldLeaveDate(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionStaffOnLeave'])) {
				$attr['type'] = 'date';
				return $attr;
			}
		} else {
			$attr['value'] = self::NO_FILTER;
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

	public function onUpdateFieldType(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if (in_array($feature, ['Report.InstitutionStaff'])) {
				// need to find all types
				$typeOptions = [];
				$typeOptions[0] = __('All Types');

				$Types = TableRegistry::get('FieldOption.StaffTypes');
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
				$statusOptions[0] = __('All Statuses');

				switch ($feature) {
					case 'Report.InstitutionStudents': case 'Report.InstitutionStudentEnrollments':
						$Statuses = TableRegistry::get('Student.StudentStatuses');
						$statusData = $Statuses->find()->select(['id', 'name'])->toArray();
						foreach ($statusData as $key => $value) {
							$statusOptions[$value->id] = $value->name;
						}
						break;
					
					case 'Report.InstitutionStaff':
						$Statuses = TableRegistry::get('FieldOption.StaffStatuses');
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
			if (in_array($feature, ['Report.InstitutionStudents', 'Report.InstitutionStudentTeacherRatio'])) {
				$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
				$academicPeriodOptions = [];
				$academicPeriodOptions[0] = __('All Academic Periods');
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
		switch ($filter) {
			case self::NO_STUDENT:
				$query
					->leftJoin(
						['Students' => 'institution_students'],
						[$this->aliasField('id').' = Students.institution_id']
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
	}
}
