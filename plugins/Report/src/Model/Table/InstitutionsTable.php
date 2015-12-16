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
