<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionRubricAnswersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_quality_rubric_answers');
		parent::initialize($config);

		$this->belongsTo('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics']);
		$this->belongsTo('RubricSections', ['className' => 'Rubric.RubricSections']);
		$this->belongsTo('RubricCriterias', ['className' => 'Rubric.RubricCriterias']);
		$this->belongsTo('RubricCriteriaOptions', ['className' => 'Rubric.RubricCriteriaOptions']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function edit($id=0) {
		$request = $this->request;

		if ($this->InstitutionRubrics->exists(['id' => $id])) {
			$query = $this->InstitutionRubrics
				->find()
				->contain(['RubricTemplates', 'InstitutionRubricAnswers'])
				->where([
					$this->InstitutionRubrics->aliasField('id') => $id
				]);
			$entity = $query->first();
			$alias = $this->InstitutionRubrics->alias();

			if ($this->request->is(['get'])) {
				$RubricTemplateOptions = TableRegistry::get('Rubric.RubricTemplateOptions');
				$optionQuery = $RubricTemplateOptions
					->find()
					->find('order')
					->where([
						$RubricTemplateOptions->aliasField('rubric_template_id') => $entity->rubric_template_id
					]);
				$entity->count = $optionQuery->count();
				$entity->rubric_template_options = $optionQuery->toArray();
				
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
						$sectionQuery = $this->RubricCriterias->RubricSections
							->find()
							->where([
								$this->RubricCriterias->RubricSections->aliasField('id') => $selectedSection,
							]);
						$entity->rubric_section = $sectionQuery->first();
						$entity->rubric_criterias = $rubricCriterias->toArray();

						// Rubric Answers
						$rubricAnswers = [];
						foreach ($entity->institution_rubric_answers as $key => $answerObj) {
							$rubricAnswers[$answerObj->rubric_criteria_id] = $answerObj;
						}
						$entity->institution_rubric_answers = $rubricAnswers;
						// End
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
				$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
				$patchOptions = new ArrayObject([]);
				$requestData = new ArrayObject($request->data);

				if ($submit == 'save') {
					$entity = $this->InstitutionRubrics->newEntity($request->data);
					if ($this->InstitutionRubrics->save($entity)) {
						$this->Alert->success('general.edit.success');
						$url = $this->ControllerAction->url('index');
						$url['action'] = 'Rubrics';
						unset($url[1]);
						unset($url['section']);

						return $this->controller->redirect($url);
					} else {
						$this->log($entity->errors(), 'debug');
						$this->Alert->error('general.edit.failed');
					}
				} else {
					//reload
				}
			}

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
