<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class InstitutionRubricsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;
	private $_fieldOrder = [];

	public function initialize(array $config) {
		$this->table('institution_site_quality_rubrics');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('status', ['visible' => ['index' => false, 'view' => true, 'edit' => false]]);
		$this->ControllerAction->field('comment', ['visible' => false]);
		$this->ControllerAction->field('institution_site_section_id', ['visible' => ['index' => false, 'view' => true, 'edit' => true]]);
	}

	public function afterAction(Event $event, ArrayObject $config) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	// public function onGetRubricTemplateId(Event $event, Entity $entity) {
	// 	return $event->subject()->Html->link($entity->rubric_template->name, [
	// 		'plugin' => $this->controller->plugin,
	// 		'controller' => $this->controller->name,
	// 		'action' => 'RubricAnswers',
	// 		'index',
	// 		'record' => $entity->id
	// 	]);
	// }

	public function onGetCustomRubricSectionsElement(Event $event, $action, $entity, $attr, $options=[]) {
		$value = '';

		if ($action == 'view') {
			$Form = $event->subject()->Form;
			$status = $this->get($entity->id)->status;

			$tableHeaders = [];
			$tableCells = [];

			if ($status == 1) {
				$tableHeaders = [__('No.'), __('Name'), __('No of Criterias (Answered)')];
			} else {
				$tableHeaders = [__('No.'), __('Name'), __('No of Criterias')];
			}

			$RubricSections = $this->RubricTemplates->RubricSections;
			$RubricCriterias = $this->RubricTemplates->RubricSections->RubricCriterias;

			$results = $RubricSections
				->find()
				->find('order')
				->contain(['RubricCriterias'])
				->where([$RubricSections->aliasField('rubric_template_id') => $entity->rubric_template_id])
				->all();

			if (!$results->isEmpty()) {
				$data = $results->toArray();

				$count = 1;
				foreach ($data as $key => $obj) {
					$rowData = [];
					$sectionId = $obj->id;
					$sectionName = $event->subject()->Html->link($obj->name, [
						'plugin' => $this->controller->plugin,
						'controller' => $this->controller->name,
						'action' => 'RubricAnswers',
						'edit',
						$entity->id,
						'status' => $status,
						'section' => $sectionId
					]);
					$criterias = $RubricCriterias
						->find()
						->where([
							$RubricCriterias->aliasField('rubric_section_id') => $sectionId,
							$RubricCriterias->aliasField('type !=') => 1
						])
						->count();

					$rowData[0] = $count;
					$rowData[1] = $sectionName;
					$rowData[2] = $criterias;

					$tableCells[$key] = $rowData;
					$count++;
				}
			}

			$attr['tableHeaders'] = $tableHeaders;
    		$attr['tableCells'] = $tableCells;

			$value = $event->subject()->renderElement('Institution.Rubrics/sections', ['attr' => $attr]);
		}

        return $value;
	}

	public function onGetLastModified(Event $event, Entity $entity) {
		return $entity->modified;
	}

	public function onGetToBeCompletedBy(Event $event, Entity $entity) {
		$value = '<i class="fa fa-minus"></i>';

		$RubricStatuses = $this->RubricTemplates->RubricStatuses;
		$results = $RubricStatuses
			->find()
			->select([
				$RubricStatuses->aliasField('date_disabled')
			])
			->where([
				$RubricStatuses->aliasField('rubric_template_id') => $entity->rubric_template->id
			])
			->join([
				'table' => 'rubric_status_periods',
				'alias' => 'RubricStatusPeriods',
				'conditions' => [
					'RubricStatusPeriods.rubric_status_id =' . $RubricStatuses->aliasField('id'),
					'RubricStatusPeriods.academic_period_id' => $entity->academic_period_id
				]
			])
			->all();

		if (!$results->isEmpty()) {
			$dateDisabled = $results->first()->date_disabled;
			$value = date('d-m-Y', strtotime($dateDisabled));
		}

		return $value;
	}

	public function indexBeforeAction(Event $event) {
		list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());

		$plugin = $this->controller->plugin;
		$controller = $this->controller->name;
		$action = $this->alias;

		$tabElements = [];
		foreach ($statusOptions as $key => $status) {
			$tabElements[$status] = [
				'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'?status='.$key],
				'text' => $status
			];
		}

		$this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);

		$this->_fieldOrder = ['rubric_template_id', 'academic_period_id', 'education_grade_id', 'institution_site_class_id', 'security_user_id'];
        if ($selectedStatus == 0) {	//New
			$this->ControllerAction->field('to_be_completed_by');
			$this->_fieldOrder[] = 'to_be_completed_by';
			$this->_buildRecords();
        } else if ($selectedStatus == 1) {	//Draft
			$this->ControllerAction->field('last_modified');
			$this->ControllerAction->field('to_be_completed_by');
			$this->_fieldOrder[] = 'last_modified';
			$this->_fieldOrder[] = 'to_be_completed_by';
        } else if ($selectedStatus == 2) {	//Completed
			$this->ControllerAction->field('completed_on');
			$this->_fieldOrder[] = 'completed_on';
        }
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		$query
			->where([$this->aliasField('status') => $selectedStatus])
			->order([$this->AcademicPeriods->aliasField('order')]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// pr('viewAfterAction');
		$this->ControllerAction->field('rubric_sections', [
			'type' => 'custom_rubric_sections',
			'valueClass' => 'table-full-width'
		]);

		switch ($entity->status) {
			case 1:
				$entity->status = __('Draft');
				break;
			case 2:
				$entity->status = __('Completed');
				break;
			default:
				$entity->status = __('New');
				break;
		}

		$this->_fieldOrder = ['status', 'rubric_template_id', 'academic_period_id', 'education_grade_id', 'institution_site_section_id', 'institution_site_class_id', 'security_user_id', 'rubric_sections'];
	}

	// public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
	// 	list(, $selectedStatus) = array_values($this->_getSelectOptions());

	// 	if ($selectedStatus == 2) {	//Completed
	// 		if ($action == 'view') {
	// 			if (isset($toolbarButtons['edit'])) {
	// 				unset($toolbarButtons['edit']);
	// 			}
	// 		}
	// 	}
	// }

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($selectedStatus == 0) {	// New
			// unset($buttons['view']);
			unset($buttons['remove']);
		} else if ($selectedStatus == 2) {	// Completed
			unset($buttons['edit']);
		}

		return $buttons;
	}

	public function _buildRecords() {
		$institutionId = $this->Session->read('Institutions.id');

		//delete all New Assessment by Institution Id and reinsert
		$this->deleteAll([
			$this->aliasField('institution_site_id') => $institutionId,
			$this->aliasField('status') => 0
		]);

		$rubrics = $this->RubricTemplates
			->find('list')
			->toArray();
		$todayDate = date("Y-m-d");

		$RubricStatuses = $this->RubricTemplates->RubricStatuses;
		// $RubricStatusProgrammes = TableRegistry::get('Institution.RubricStatusProgrammes');
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$Classes = TableRegistry::get('Institution.InstitutionSiteClasses');
		$SectionClasses = TableRegistry::get('Institution.InstitutionSiteSectionClasses');
		$SectionGrades = TableRegistry::get('Institution.InstitutionSiteSectionGrades');

		foreach ($rubrics as $key => $rubric) {
			$rubricStatuses = $RubricStatuses
				->find()
				->contain(['AcademicPeriods', 'SecurityRoles', 'Programmes'])
				->where([
					$RubricStatuses->aliasField('rubric_template_id') => $key,
					$RubricStatuses->aliasField('date_disabled >=') => $todayDate
				])
				->toArray();

			foreach ($rubricStatuses as $rubricStatus) {
				$statusId = $rubricStatus->id;
				$templateId = $rubricStatus->rubric_template_id;
				$programmeIds = [];
				// pr(' Template: ' . $templateId);
				foreach ($rubricStatus->programmes as $programme) {
					$programmeId = $programme->id;
					$programmeIds[$programmeId] = $programmeId;
				}
				// pr(' programmeIds: ' . $programmeIds);
				$gradeIds = $this->EducationGrades
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([$this->EducationGrades->aliasField('education_programme_id IN') => $programmeIds])
					->toArray();

				foreach ($rubricStatus->academic_periods as $academicPeriod) {
					$academicPeriodId = $academicPeriod->id;
					// pr(' academicPeriodId: ' . $academicPeriodId);
					$sectionResults = $Sections
						->find()
						->select([
							$Sections->aliasField('id'),
							$Sections->aliasField('name')
						])
						->where([
							$Sections->aliasField('institution_site_id') => $institutionId,
							$Sections->aliasField('academic_period_id') => $academicPeriodId,
						])
						->join([
							'table' => $SectionGrades->_table,
							'alias' => $SectionGrades->alias(),
							'conditions' => [
								$SectionGrades->aliasField('institution_site_section_id =') . $Sections->aliasField('id'),
								$SectionGrades->aliasField('education_grade_id IN') => $gradeIds
							]
						])
						->join([
							'table' => $SectionClasses->_table,
							'alias' => $SectionClasses->alias(),
							'conditions' => [
								$SectionClasses->aliasField('institution_site_section_id =') . $Sections->aliasField('id')
							]
						])
						->join([
							'table' => $Classes->_table,
							'alias' => $Classes->alias(),
							'conditions' => [
								$Classes->aliasField('id =') . $SectionClasses->aliasField('institution_site_class_id'),
								$Classes->aliasField('institution_site_id') => $institutionId,
								$Classes->aliasField('academic_period_id') => $academicPeriodId
							]
						])
						->group([
							$Sections->aliasField('id')
						])
						->contain(['InstitutionSiteSectionGrades', 'InstitutionSiteClasses.InstitutionSiteClassStaff'])
						->all();

					if (!$sectionResults->isEmpty()) {
						foreach ($sectionResults as $section) {
							$sectionId = $section->id;
							$gradeId = 0;
							foreach ($section->institution_site_section_grades as $grade) {
								$gradeId = $grade->education_grade_id;
							}

							// pr(' sectionId: ' . $sectionId);
							foreach ($section->institution_site_classes as $class) {
								$classId = $class->id;
								// pr(' classId: ' . $classId);
								foreach ($class->institution_site_class_staff as $staff) {
									$staffId = $staff->security_user_id;
									// pr(' staffId: ' . $staffId);

									$data = [
										'institution_site_id' => $institutionId,
										'rubric_template_id' => $templateId,
										'academic_period_id' => $academicPeriodId,
										'education_grade_id' => $gradeId,
										'institution_site_section_id' => $sectionId,
										'institution_site_class_id' => $classId,
										'security_user_id' => $staffId
									];
									$entity = $this->newEntity($data);

									if ($this->save($entity)) {
									} else {
										$this->log($entity->errors(), 'debug');
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->getSelectOptions('Rubrics.status');
		$selectedStatus = $this->queryString('status', $statusOptions);

		return compact('statusOptions', 'selectedStatus');
	}
}
