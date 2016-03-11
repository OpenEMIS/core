<?php
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use ControllerAction\Model\Traits\EventTrait;

class MultiGradeBehavior extends Behavior {
	use EventTrait;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.add.afterSave'] = ['callable' => 'addAfterSave', 'priority' => 9];
		$events['ControllerAction.Model.add.beforePatch'] = ['callable' => 'addBeforePatch', 'priority' => 9];
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
    	$request = $model->request;
    	$institutionId = $extra['institution_id'];
    	$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
    	$selectedEducationGradeId = $extra['selectedEducationGradeId'];

    	/**
		 * PHPOE-2090, check if selected academic_period_id changes
		 */
    	if (array_key_exists($model->alias(), $request->data)) {
	    	$modelData = $request->data[$model->alias()];
			$selectedAcademicPeriodId = $modelData['academic_period_id'];
		}

		$gradeOptions = $model->Institutions->InstitutionGrades->getGradeOptions($institutionId, $selectedAcademicPeriodId, false);
		$model->field('multi_grade_field', [
			'type' => 'element',
			'data' => $gradeOptions,
			'model' => $model->alias(),
			'field' => 'multi_grade_field',
			'element' => 'Institution.Classes/multi_grade',
		]);
		$model->fields['students']['visible'] = false;
		$model->fields['staff_id']['options'] = $model->getStaffOptions('add', $selectedAcademicPeriodId, $institutionId);
		$model->setFieldOrder([
			'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'multi_grade_field'
		]);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$model = $this->_table;
		$request = $this->_table->request;
		$education_grades = $request->data($model->aliasField('education_grades'));
		pr($patchOptions);pr($request->data($model->aliasField('education_grades')));pr($requestData);die;
		$selected = [];
		if (isset($education_grades) && count($education_grades)>0) {
			foreach($education_grades as $key => $row) {
				// $requestData[$model->alias()]['education_grades'][$key]['_joinData']['id'] = Text::uuid();
				// $requestData[$model->alias()]['education_grades'][$key]['_joinData']['institution_class_id'] = 1;
				$selected[] = $row['_joinData']['education_grade_id'];
			}
		} else {
			/**
			 * set institution_id to empty to trigger validation error in ControllerActionComponent
			 */
			$requestData[$model->alias()]['institution_id'] = '';
			$errorMessage = 'Institution.'.$model->aliasField('noGrade');
			$requestData['errorMessage'] = $errorMessage;
		}
		$model->fields['multi_grade_field']['selected'] = $selected;
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra) {
    	$model = $this->_table;
		$errors = $entity->errors();
		if (!empty($errors)) {
			if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
				$model->Alert->error($requestData['errorMessage'], ['reset'=>true]);
			}
		}
	}

}
	