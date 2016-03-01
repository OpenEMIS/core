<?php
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use ControllerAction\Model\Traits\EventTrait;

class SingleGradeBehavior extends Behavior {
	use EventTrait;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		// $events['ControllerAction.Model.add'] = 'add';
		return $events;
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction(Event $event, ArrayObject $extra) {
		$query = $this->request->query;
    	if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
    		$action = $this->url('add');
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($action['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($action['education_grade_id']);
    		}
			$this->controller->redirect($action);
    	}
    	if (array_key_exists('grade_type', $query)) {
    		$this->_selectedGradeType = $query['grade_type'];
    	}

		if ($this->_selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Classes'
			]);
		}

		/**
		 * add/edit form setup
		 */
		$staffOptions = $this->getStaffOptions('add');
		if ($this->_selectedGradeType == 'single') {
	    	if (array_key_exists($this->alias(), $this->request->data)) {
		    	$_data = $this->request->data[$this->alias()];
				$this->_selectedEducationGradeId = $_data['education_grade'];
				$this->_numberOfClasses = $_data['number_of_classes'];
				/**
				 * PHPOE-2090, check if selected academic_period_id changes
				 */
				$this->_selectedAcademicPeriodId = $_data['academic_period_id'];
			}

			/**
			 * education_grade field setup
			 * PHPOE-1867 - Changed the population of grades from InstitutionGradesTable
			 */
			$gradeOptions = [];
			if (!empty($this->_selectedAcademicPeriodId)) {
				$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
			}
			if ($this->_selectedEducationGradeId != 0) {
				if (!array_key_exists($this->_selectedEducationGradeId, $gradeOptions)) {
					$this->_selectedEducationGradeId = key($gradeOptions);
				}
			} else {
				$this->_selectedEducationGradeId = key($gradeOptions);
			}
			$this->field('education_grade', [
				'type' => 'select',
				'options' => $gradeOptions,
				'onChangeReload' => true,
				'attr' => [
 					'empty' => ((empty($gradeOptions)) ? $this->Alert->getMessage($this->aliasField('education_grade_options_empty')) : '')
				]
			]);

			$numberOfClassesOptions = $this->numberOfClassesOptions();
			$this->field('number_of_classes', [
				'type' => 'select', 
				'options' => $numberOfClassesOptions,
				'onChangeReload' => true
			]);

			$grade = [];
			if ($this->InstitutionClassGrades->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->InstitutionClassGrades->EducationGrades->get($this->_selectedEducationGradeId, [
				    'contain' => ['EducationProgrammes']
				])->toArray();
			}

			$this->field('single_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Classes/single_grade',
				'data' => [	'numberOfClasses'=>$this->_numberOfClasses,
				 			'staffOptions'=>$staffOptions,
				 			'existedClasses'=>$this->getExistedClasses(),
				 			'grade'=>$grade	
				]
			]);

			$this->fields['name']['visible'] = false;
			$this->fields['students']['visible'] = false;
			$this->fields['staff_id']['visible'] = false;
			$this->fields['staff_id']['type'] = 'hidden';
			$this->setFieldOrder([
				'academic_period_id', 'education_grade', 'institution_shift_id', 'class_number', 'number_of_classes', 'single_grade_field'
			]);

    	} else {
			/**
			 * PHPOE-2090, check if selected academic_period_id changes
			 */
	    	if (array_key_exists($this->alias(), $this->request->data)) {
		    	$_data = $this->request->data[$this->alias()];
				$this->_selectedAcademicPeriodId = $_data['academic_period_id'];
			}

			$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId, false);
			$this->field('multi_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Classes/multi_grade',
				'model' => $this->alias(),
				'field' => 'multi_grade_field',
				'data' => $gradeOptions
			]);
			$this->fields['staff_id']['options'] = $staffOptions;
			$this->fields['students']['visible'] = false;
			$this->setFieldOrder([
				'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'multi_grade_field'
			]);

    	}

		$this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)).' '.ucwords(strtolower($this->_selectedGradeType)).' Grade');

		$tabElements = [
			'single' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', $this->action, 'grade_type'=>'single'],
				'text' => __('Single Grade')
			],
			'multi' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', $this->action, 'grade_type'=>'multi'],
				'text' => __('Multi Grade')
			],
		];
        $this->controller->set('tabElements', $tabElements);
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		if ($this->_selectedGradeType == 'single') {
			$process = function ($model, $entity) use ($data) {
				$commonData = $data['InstitutionClasses'];
				/**
				 * PHPOE-2090, check if grade is empty as it is mandatory
				 */
				if (!empty($commonData['education_grade'])) {
					foreach($data['MultiClasses'] as $key => $row) {
						$data['MultiClasses'][$key]['institution_shift_id'] = $commonData['institution_shift_id'];
						$data['MultiClasses'][$key]['institution_id'] = $commonData['institution_id'];
						$data['MultiClasses'][$key]['academic_period_id'] = $commonData['academic_period_id'];
						$data['MultiClasses'][$key]['institution_class_grades'][0] = [
								'education_grade_id' => $commonData['education_grade'],
								'status' => 1
							];
					}
					$classes = $model->newEntities($data['MultiClasses']);
					$error = false;
					foreach ($classes as $key=>$class) {
					    if ($class->errors()) {
					    	$error = $class->errors();
					    	$data['MultiClasses'][$key]['errors'] = $error;
					    }
					}
					/**
					 * attempt to prevent memory leak
					 */
					unset($key);
					unset($class);
					if (!$error) {
						foreach ($classes as $class) {
							$model->save($class);
					    }
						unset($class);
						return true;
					} else {
						$errorMessage='';
						foreach ($error as $key=>$value) {
							$errorMessage .= Inflector::classify($key);
						}
						unset($value);
						$model->log($error, 'debug');
						/**
						 * unset all field validation except for "name" to trigger validation error in ControllerActionComponent
						 */
						foreach ($model->fields as $value) {
							if ($value['field'] != 'name') {
								$model->validator()->remove($value['field']);
							}
						}
						unset($value);
						$model->fields['single_grade_field']['data']['classes'] = $classes;
						$model->request->data['MultiClasses'] = $data['MultiClasses'];
						return false;
					}
				} else {
					$model->Alert->error('Institution.'.$model->alias().'.noGrade');
					return false;
				}
			};
		} else {
			$process = function ($model, $entity) use ($data) {
				$error = false;
				if (array_key_exists('MultiClasses', $data)) {
					$error = [$data['MultiClasses']=>0];
				}
				if (!$error) {
					return $model->save($entity);
				} else {
					$errorMessage='';
					foreach ($error as $key=>$value) {
						$errorMessage .= Inflector::classify($key);
					}
					unset($value);
					$model->log($error, 'debug');
					return false;
				}
			};			
		}
		return $process;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
        if ($entity->isNew()) {
			/**
			 * using the entity->id (class id), find the list of grades from (institution_class_grades) linked to this class
			 */
			$grades = [];
			foreach ($entity->institution_class_grades as $grade) {
				$grades[] = $grade->education_grade_id;
			}
			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			/**
			 * from the list of grades, find the list of subjects group by grades in (education_grades_subjects) where visible = 1
			 */
			$educationGradeSubjects = $EducationGrades
					->find()
					->contain(['EducationSubjects' => function($query) use ($grades) {
						return $query
							->join([
								[
									'table' => 'education_grades_subjects',
									'alias' => 'EducationGradesSubjects',
									'conditions' => [
										'EducationGradesSubjects.education_grade_id IN' => $grades,
										'EducationGradesSubjects.education_subject_id = EducationSubjects.id',
										'EducationGradesSubjects.visible' => 1
									]
								]
							]);
					}])
					->where([
						'EducationGrades.id IN' => $grades,
						'EducationGrades.visible' => 1
					])
					->toArray();
			unset($EducationGrades);
			unset($grades);
			
			$educationSubjects = [];
			if (count($educationGradeSubjects)>0) {
				foreach ($educationGradeSubjects as $gradeSubject) {
					foreach ($gradeSubject->education_subjects as $subject) {
						if (!isset($educationSubjects[$subject->id])) {
							$educationSubjects[$subject->id] = [
								'id' => $subject->id,
								'name' => $subject->name
							];
						}
					}
					unset($subject);
				}
				unset($gradeSubject);
			}
			unset($educationGradeSubjects);	

			/**
			 * for each education subjects, find the primary key of institution_classes using (entity->academic_period_id and institution_id and education_subject_id)
			 */
			$InstitutionSubjects = TableRegistry::get('Institution.InstitutionClasses');
			$institutionSubjects = $InstitutionSubjects->find('list', [
				    'keyField' => 'id',
				    'valueField' => 'education_subject_id'
				])
				->where([
					$InstitutionSubjects->aliasField('academic_period_id') => $entity->academic_period_id,
					$InstitutionSubjects->aliasField('institution_id') => $entity->institution_id,
					$InstitutionSubjects->aliasField('education_subject_id').' IN' => array_keys($educationSubjects)
				])
				->toArray();
			$institutionSubjectsIds = [];
			foreach ($institutionSubjects as $key => $value) {
				$institutionSubjectsIds[$value][] = $key;
			}
			unset($institutionSubjects);	

			/**
			 * using the list of primary keys, search institution_class_subjects (InstitutionClassSubjects) to check for existing records
			 * if found, don't insert, 
			 * else create a record in institution_classes (InstitutionSubjects)
			 * and link to the subject in institution_class_subjects (InstitutionClassSubjects) with status 1
			 */
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
			$newSchoolSubjects = [];

			foreach ($educationSubjects as $key=>$educationSubject) {
				$getExistingRecord = false;
				if (empty($institutionSubjects)) {
					if (array_key_exists($key, $institutionSubjectsIds)) {
						$getExistingRecord = $InstitutionClassSubjects->find()
							->where([
								$InstitutionClassSubjects->aliasField('institution_class_id') => $entity->id,
								$InstitutionClassSubjects->aliasField('institution_class_id').' IN' => $institutionSubjectsIds[$key],
							])
							->select(['id'])
							->first();
					}
				}
				if (!$getExistingRecord) {
					$newSchoolSubjects[$key] = [
						'name' => $educationSubject['name'],
						'institution_id' => $entity->institution_id,
						'education_subject_id' => $educationSubject['id'],
						'academic_period_id' => $entity->academic_period_id,
						'institution_class_subjects' => [
							[
								'status' => 1,
								'institution_class_id' => $entity->id
							]
						]
					];
				}
			}

			if (!empty($newSchoolSubjects)) {
				$newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
				foreach ($newSchoolSubjects as $subject) {
				    $InstitutionSubjects->save($subject);
				}
				unset($subject);
			}
			unset($newSchoolSubjects);
			unset($InstitutionSubjects);
			unset($InstitutionClassSubjects);
        }
        return true;
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if ($this->_selectedGradeType != 'single') {
			if (isset($data['InstitutionClasses']['institution_class_grades']) && count($data['InstitutionClasses']['institution_class_grades'])>0) {
				foreach($data['InstitutionClasses']['institution_class_grades'] as $key => $row) {
					$data['InstitutionClasses']['institution_class_grades'][$key]['status'] = 1;
				}
			} else {
				/**
				 * set institution_id to empty to trigger validation error in ControllerActionComponent
				 */
				$data['InstitutionClasses']['institution_id'] = '';
				$errorMessage = 'Institution.'.$this->alias().'.noGrade';
				$data['MultiClasses'] = $errorMessage;
				$this->Alert->error($errorMessage);
			}
		}
	}

	public function addAfterAction(Event $event, Entity $entity) {
        $this->controller->set('selectedAction', $this->_selectedGradeType);
	}


}
	