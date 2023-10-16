<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionRubricAnswersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_quality_rubric_answers');
		parent::initialize($config);

		$this->belongsTo('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics']);
		$this->belongsTo('RubricSections', ['className' => 'Rubric.RubricSections']);
		$this->belongsTo('RubricCriterias', ['className' => 'Rubric.RubricCriterias']);
		$this->belongsTo('RubricCriteriaOptions', ['className' => 'Rubric.RubricCriteriaOptions']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			->requirePresence('rubric_criteria_option_id')
			->notEmpty('rubric_criteria_option_id', 'Please select a criteria option.');

		return $validator;
	}

	public function validationSkipCheck(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator->remove('rubric_criteria_option_id');
        return $validator;
    }

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function edit($id=0) {
		// pr($this->ControllerAction->paramsPass()[1]);
		$paramsPass = $this->paramsDecode($this->ControllerAction->paramsPass()[1]);
		// pr($paramsPass);die;
		$request = $this->request;

		$id = $paramsPass['id'];

		if ($this->InstitutionRubrics->exists(['id' => $id])) {
			$query = $this->InstitutionRubrics
				->find()
				->contain(['InstitutionRubricAnswers'])
				->where([
					$this->InstitutionRubrics->aliasField('id') => $id
				]);
			$entity = $query->first();
			$selectedStatus = $entity->status;
			$alias = $this->InstitutionRubrics->alias();

			if ($this->request->is(['get'])) {
				// Rubric Templates
				$entity->rubric_template_name = $this->InstitutionRubrics->RubricTemplates->get($entity->rubric_template_id)->name;
				// End
				
				$selectedSection = $this->request->query('section');
				if (!is_null($selectedSection)) {
					$rubricCriterias = $this->RubricCriterias
						->find()
						->find('order')
						->contain(['RubricCriteriaOptions'])
						->where([
							$this->RubricCriterias->aliasField('rubric_section_id') => $selectedSection,
						])
						->all();

					if (!$rubricCriterias->isEmpty()) {
						// Rubric Sections
						$rubricSection = $this->RubricCriterias->RubricSections
							->find()
							->where([
								$this->RubricCriterias->RubricSections->aliasField('id') => $selectedSection,
							])
							->first();
						$entity->rubric_section_name = $rubricSection->name;
						$entity->rubric_section_order = $rubricSection->order;
						// End

						$entity->rubric_criterias = $rubricCriterias->toArray();
					}
				} else {
					$this->Alert->warning('InstitutionRubricAnswers.noSection');
					$url = $this->ControllerAction->url('index');
					$url['action'] = 'Rubrics';
					unset($url[1]);
					unset($url['section']);

					return $this->controller->redirect($url);
				}
			} else if ($this->request->is(['post', 'put'])) {
				$entity = $this->InstitutionRubrics->newEntity();
				$requestData = $request->data;
				$patchOptions = [];

				$submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';

				if ($submit == 'save') {
					// Skip validation on rubric_criteria_option_id if is draft
					if ($requestData[$alias]['status'] == 1) {
						$patchOptions['associated'] = [
							'InstitutionRubricAnswers' => ['validate' => 'SkipCheck']
						];
					}

					$entity = $this->InstitutionRubrics->patchEntity($entity, $requestData, $patchOptions);

					// Rebuild rubric_criterias
					$rubricCriterias = $entity->rubric_criterias;
					foreach ($rubricCriterias as $criteriaKey => $criteriaArray) {
						$criteria = $this->RubricCriterias
							->find()
							->contain(['RubricCriteriaOptions'])
							->where([
								$this->RubricCriterias->aliasField('id') => $criteriaArray['id'],
							])
							->first();

						$entity->rubric_criterias[$criteriaKey] = $criteria;
					}
					// End

					if ($this->InstitutionRubrics->save($entity)) {
						if ($entity->status == 1) {
							$this->Alert->success('InstitutionRubricAnswers.save.draft');
						} else if ($entity->status == 2) {
							$templateId = $entity->rubric_template_id;
							$RubricSections = $this->RubricCriterias->RubricSections;
							$criterias = $this->RubricCriterias
								->find()
								->matching('RubricSections', function($q) use ($RubricSections, $templateId) {
									return $q
										->where([
											$RubricSections->aliasField('rubric_template_id') => $templateId
										]);
								})
								->where([
									$this->RubricCriterias->aliasField('type') => 2
								])
								->count();

							$answers = $this
								->find()
								->where([
									$this->aliasField('institution_quality_rubric_id') => $entity->id,
									$this->aliasField('rubric_criteria_option_id IS NOT') => 0
								])
								->count();

							if ($answers != $criterias) {
								$this->Alert->error('InstitutionRubricAnswers.save.failed');

								$draftStatus = 1;
								$this->InstitutionRubrics->updateAll(
									['status' => $draftStatus],
									['id' => $entity->id]
								);
							} else {
								$this->Alert->success('InstitutionRubricAnswers.save.final');
							}
						}

						$url = $this->ControllerAction->url('index');
						$url['action'] = 'Rubrics';
						$url[0] = 'view';
						unset($url['section']);

						return $this->controller->redirect($url);
					} else {
						// Reset the status to the original value
						$entity->status = $selectedStatus;
						$this->log($entity->errors(), 'debug');
						$this->Alert->error('general.edit.failed');
					}
				} else {
					//reload
				}
			}

			// Rubric Template Options
			$RubricTemplateOptions = TableRegistry::get('Rubric.RubricTemplateOptions');
			$optionQuery = $RubricTemplateOptions
				->find()
				->find('order')
				->where([
					$RubricTemplateOptions->aliasField('rubric_template_id') => $entity->rubric_template_id
				]);
			$entity->count = $optionQuery->count();
			$entity->rubric_template_options = $optionQuery->toArray();
			// End

			// Rubric Answers
			$rubricAnswers = [];
			foreach ($entity->institution_rubric_answers as $answerKey => $answerObj) {
				$rubricAnswers[$answerObj->rubric_criteria_id] = $answerObj;
			}
			$entity->institution_rubric_answers = $rubricAnswers;
			// End

			$this->controller->set('data', $entity);
			$this->controller->set('alias', $this->InstitutionRubrics->alias());
		} else {
			$this->Alert->warning('general.notExists');
			$url = $this->ControllerAction->url('index');
			$url['action'] = 'Rubrics';
			unset($url[1]);
			unset($url['section']);

			return $this->controller->redirect($url);
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$toolbarButtons['back'] = $buttons['back'];
		$toolbarButtons['back']['url']['action'] = 'Rubrics';
		if (isset($toolbarButtons['back']['url']['section'])) {
			unset($toolbarButtons['back']['url']['section']);
		}
		$toolbarButtons['back']['type'] = 'button';
		$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
		$toolbarButtons['back']['attr'] = $attr;
		$toolbarButtons['back']['attr']['title'] = __('Back');
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$cancelButton = $buttons[1];
		$buttons[0] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Save As Draft'),
			'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[rubric-status=1]\').val(1);']
		];
		$buttons[1] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Submit'),
			'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[rubric-status=1]\').val(2);']
		];
		$buttons[2] = $cancelButton;
	}
}
