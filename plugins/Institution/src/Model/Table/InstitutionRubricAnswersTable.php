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
		$entity = $this->newEntity();

		if ($this->InstitutionRubrics->exists(['id' => $id])) {
			if ($this->request->is(['get'])) {
				$rubric = $this->InstitutionRubrics->get($id);
				$selectedSection = $this->request->query('section');

				if (!is_null($selectedSection)) {
					$results = $this->RubricCriterias
						->find()
						->find('order')
						->contain(['RubricSections', 'RubricCriteriaOptions'])
						->where([
							$this->RubricCriterias->aliasField('rubric_section_id') => $selectedSection,
						])
						->all();

					if (!$results->isEmpty()) {
						$entity = $results->toArray();
					}
					// pr($data);
					// pr($rubric);
					// pr($id);
					// pr('for get');
				} else {
					$this->Alert->warning('InstitutionRubricAnswers.noSection');
					$url = $this->ControllerAction->url('index');
					$url['action'] = 'Rubrics';
					unset($url[1]);
					unset($url['section']);

					return $this->controller->redirect($url);
				}
			} else if ($this->request->is(['post', 'put'])) {
				// pr('for post');die;
				$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
				$patchOptions = new ArrayObject([]);
				$requestData = new ArrayObject($request->data);

				if ($submit == 'save') {
					$patchOptionsArray = $patchOptions->getArrayCopy();
					$request->data = $requestData->getArrayCopy();
					$entity = $this->patchEntity($entity, $request->data, $patchOptionsArray);
				} else {
				}
			}

			$this->controller->set('data', $entity);
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
}
