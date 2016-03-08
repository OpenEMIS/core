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
		$events['ControllerAction.Model.add.afterSave'] = ['callable' => 'addAfterSave', 'priority' => 9];
		$events['ControllerAction.Model.add.beforeSave'] = ['callable' => 'addBeforeSave', 'priority' => 9];
		// set priority to 100 so that this will be called after model's addBeforeAction
		$events['ControllerAction.Model.add.beforeAction'] = ['callable' => 'addBeforeAction', 'priority' => 100];
		return $events;
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction(Event $event, ArrayObject $extra) {
		/**
		 * add form setup
		 */
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

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra) {

		$process = function ($model, $entity) use ($requestData, $extra) {
			$commonData = $requestData['InstitutionClasses'];
			/**
			 * PHPOE-2090, check if grade is empty as it is mandatory
			 */
			if (!empty($commonData['education_grade'])) {
				foreach($requestData['MultiClasses'] as $key => $row) {
					$requestData['MultiClasses'][$key]['institution_shift_id'] = $commonData['institution_shift_id'];
					$requestData['MultiClasses'][$key]['institution_id'] = $commonData['institution_id'];
					$requestData['MultiClasses'][$key]['academic_period_id'] = $commonData['academic_period_id'];
					$requestData['MultiClasses'][$key]['institution_class_grades'][0] = [
							'education_grade_id' => $commonData['education_grade'],
							'status' => 1
						];
				}
				$classes = $model->newEntities($requestData['MultiClasses']);
				$error = false;
				foreach ($classes as $key=>$class) {
				    if ($class->errors()) {
				    	$error = $class->errors();
				    	$requestData['MultiClasses'][$key]['errors'] = $error;
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
					$requestData['errorMessage'] = false;
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
					$model->request->data['MultiClasses'] = $requestData['MultiClasses'];
					return false;
				}
			} else {
				$requestData['errorMessage'] = 'Institution.'.$model->alias().'.noGrade';
				return false;
			}
		};

		return $process;
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra) {
    	$model = $this->_table;
		$errors = $entity->errors();
		if (isset($requestData['errorMessage'])) {
			if (!empty($requestData['errorMessage'])) {
				$model->Alert->error($requestData['errorMessage'], ['reset'=>true]);
			} else {
				$entity->clean();
				$model->Alert->success('general.add.success', ['reset'=>true]);
			}
		}
	}

}
	