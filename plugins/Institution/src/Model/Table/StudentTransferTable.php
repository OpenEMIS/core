<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
// use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class StudentTransferTable extends AppTable {
	private $dataCount = null;

	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		// $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'select_all') {
			$html = '';
			$Form = $event->subject()->Form;

			$alias = $this->alias() . '.select_all';
			$html .= $Form->checkbox($alias, ['class' => 'icheck-input']);

			return $html;
			return __('Programme') . '<span class="divider"></span>' . __('Grade');
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

    public function onGetSelectAll(Event $event, Entity $entity) {
    	$html = '';

    	$id = $entity->user->id;

		$StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
		$alias = Inflector::underscore($this->alias());
		$fieldPrefix = $this->EducationGrades->alias() . '.'.$alias.'.' . $id;
		$Form = $event->subject()->Form;

		$checked = false;
		$html .= $Form->checkbox($fieldPrefix, ['class' => 'icheck-input', 'checked' => $checked]);

		return $html;
	}

    public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

    public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
    	$this->ControllerAction->field('select_all');
    	$this->ControllerAction->field('openemis_no');
    	$this->ControllerAction->field('student_status_id', ['visible' => false]);
    	$this->ControllerAction->field('education_grade_id', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('institution_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['select_all', 'openemis_no', 'student_id']);

		$settings['pagination'] = false;
		if ($this->Session->check('Institution.Institutions.id')) {
			$institutionId = $this->Session->read('Institution.Institutions.id');

			// Academic Periods
			if (!is_null($this->request->query('mode'))) {
				// edit mode, disabled Periods control and restrict selectedPeriod to current
				$selectedPeriod = $this->AcademicPeriods->getCurrent();
			} else {

			}
			$this->request->query['period'] = $selectedPeriod;
			// End

			$query
				->contain(['StudentStatuses', 'Users'])
				->where([
					$this->aliasField('institution_id') => $institutionId,
					// $this->aliasField('academic_period_id') => $selectedPeriod,
					// $this->aliasField('education_grade_id') => $selectedGrade
				]);

			return $query;
		} else {
			return $query
				->where([$this->aliasField('institution_id') => 0]);
		}
    }

    public function indexAfterAction(Event $event, $data) {
		$this->dataCount = $data->count();
	}

    public function afterAction(Event $event, ArrayObject $config) {
    	if (!is_null($this->request->query('mode'))) {
			$indexElements = $this->controller->viewVars['indexElements'];
			$selectedPeriod = $this->request->query('period');
			$currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
			$startDate = $currentPeriod->start_date->format('Y-m-d');

			$where = [
				$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
				$this->AcademicPeriods->aliasField('academic_period_level_id') => $currentPeriod->academic_period_level_id,
				$this->AcademicPeriods->aliasField('start_date >=') => $startDate
			];

			$periodOptions = $this->AcademicPeriods
				->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();

			$indexElements[] = [
				'name' => 'Institution.StudentTransfer/filters',
				'data' => [
					'alias' => $this->alias(),
					'period' => $currentPeriod->name,
					'periods' => $periodOptions
				],
				'options' => [],
				'order' => 1
			];

			$this->controller->set(compact('indexElements'));

			if ($this->dataCount > 0) {
				$config['formButtons'] = true;
				$config['url'] = $config['buttons']['index']['url'];
				$config['url'][0] = 'indexEdit';
			} else {
				$this->Alert->info('StudentTransfer.noData');
			}
    	}
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if (!is_null($this->request->query('mode'))) {
			$toolbarButtons['back'] = $buttons['back'];
			if ($toolbarButtons['back']['url']['mode']) {
				unset($toolbarButtons['back']['url']['mode']);
			}
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $attr;
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['action'] = 'Students';
		}
	}

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			$requestData = $this->request->data;
			pr($requestData);

			$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students'];
			$url = array_merge($url, $this->request->query, $this->request->pass);
			$url[0] = 'index';
			unset($url['mode']);
			pr($url);die;

			return $this->controller->redirect($url);
		}
	}
}
