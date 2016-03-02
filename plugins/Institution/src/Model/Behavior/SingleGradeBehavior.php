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
    public function gradeAddBeforeAction(Event $event, ArrayObject $extra) {
    	$model = $this->_table;

    	if (array_key_exists($model->alias(), $model->request->data)) {
	    	$modelData = $model->request->data[$model->alias()];
			$model->selectedEducationGradeId = $modelData['education_grade'];
			$model->numberOfClasses = $modelData['number_of_classes'];
			/**
			 * PHPOE-2090, check if selected academic_period_id changes
			 */
			$model->selectedAcademicPeriodId = $modelData['academic_period_id'];
		}

		/**
		 * education_grade field setup
		 * PHPOE-1867 - Changed the population of grades from InstitutionGradesTable
		 */
		$gradeOptions = [];
		if (!empty($model->selectedAcademicPeriodId)) {
			$gradeOptions = $model->Institutions->InstitutionGrades->getGradeOptions($model->institutionId, $model->selectedAcademicPeriodId);
		}
		if ($model->selectedEducationGradeId != 0) {
			if (!array_key_exists($model->selectedEducationGradeId, $gradeOptions)) {
				$model->selectedEducationGradeId = key($gradeOptions);
			}
		} else {
			$model->selectedEducationGradeId = key($gradeOptions);
		}
		$model->field('education_grade', [
			'type' => 'select',
			'options' => $gradeOptions,
			'onChangeReload' => true,
			'attr' => [
					'empty' => ((empty($gradeOptions)) ? $model->Alert->getMessage($model->aliasField('education_grade_options_empty')) : '')
			]
		]);

		$numberOfClassesOptions = $model->numberOfClassesOptions();
		$model->field('number_of_classes', [
			'type' => 'select', 
			'options' => $numberOfClassesOptions,
			'onChangeReload' => true
		]);

		$grade = [];
		if ($model->InstitutionClassGrades->EducationGrades->exists(['id' => $model->selectedEducationGradeId])) {
			$grade = $model->InstitutionClassGrades->EducationGrades->get($model->selectedEducationGradeId, [
			    'contain' => ['EducationProgrammes']
			])->toArray();
		}

		$model->field('single_grade_field', [
			'type' 		=> 'element', 
			'element' 	=> 'Institution.Classes/single_grade',
			'data' 		=> [	'numberOfClasses' 	=> $model->numberOfClasses,
					 			'staffOptions' 		=> $model->getStaffOptions('add'),
					 			'existedClasses' 	=> $model->getExistedClasses(),
					 			'grade' 			=> $grade
			]
		]);

		$model->fields['name']['visible'] = false;
		$model->fields['students']['visible'] = false;
		$model->fields['staff_id']['visible'] = false;
		$model->fields['staff_id']['type'] = 'hidden';
		$model->setFieldOrder([
			'academic_period_id', 'education_grade', 'institution_shift_id', 'class_number', 'number_of_classes', 'single_grade_field'
		]);

	}

	public function gradeAddBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		if ($this->selectedGradeType == 'single') {
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

	public function gradeAfterSave(Event $event, Entity $entity, ArrayObject $options) {
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

}
	