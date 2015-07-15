<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class UserGroupsTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('security_groups');
		parent::initialize($config);

		$this->belongsToMany('Users', [
			'className' => 'User.Users',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$this->belongsToMany('Areas', [
			'className' => 'Area.Areas',
			'joinTable' => 'security_group_areas',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'area_id',
			'dependent' => true
		]);

		$this->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'security_group_institutions',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'institution_site_id',
			'dependent' => true
		]);
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$tabElements = [
			$this->alias() => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
				'text' => $this->getMessage($this->aliasField('tabTitle'))
			],
			'SystemGroups' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'SystemGroups'],
				'text' => $this->getMessage('SystemGroups.tabTitle')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());

		$this->ControllerAction->field('areas', ['type' => 'area_table', 'valueClass' => 'table-full-width']);
		$this->ControllerAction->field('institutions', ['type' => 'institution_table', 'valueClass' => 'table-full-width']);

		$this->ControllerAction->setFieldOrder([
			'name', 'areas'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['Areas.Levels']);
	}

	public function onGetAreaTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Level'), __('Code'), __('Area')];
		$tableCells = [];

		if ($action == 'index') {
			// $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
			// $value = $EducationGradesSubjects
			// 	->findByEducationGradeId($entity->id)
			// 	->where([$EducationGradesSubjects->aliasField('visible') => 1])
			// 	->count();
			// $attr['value'] = $value;
		} else if ($action == 'view') {
			
			
			$areas = $entity->extractOriginal(['areas']);
			if (!empty($areas)) {
				foreach ($areas['areas'] as $key => $obj) {
					$rowData = [];
					$rowData[] = [$obj->level->name, ['autocomplete-exclude' => $obj->id]];
					$rowData[] = $obj->code;
					$rowData[] = $obj->name;
					$tableCells[] = $rowData;
				}
			}

			$attr['tableHeaders'] = $tableHeaders;
			$attr['tableCells'] = $tableCells;
		} else if ($action == 'edit') {
			// if (isset($entity->id)) {
			// 	$form = $event->subject()->Form;
			// 	// Build Education Subjects options
			// 	$subjectOptions = $this->EducationSubjects
			// 		->find('list')
			// 		->find('visible')
			// 		->find('order')
			// 		->toArray();
			// 	// End

			// 	$tableHeaders = [__('Name'), __('Code'), __('Hours Required'), ''];
			// 	$cellCount = 0;

			// 	$arraySubjects = [];
				if ($this->request->is(['get'])) {
					$areas = $entity->extractOriginal(['areas']);
					if (!empty($areas)) {
						foreach ($areas['areas'] as $key => $obj) {
							$rowData = [];
							$rowData[] = [$obj->level->name, ['autocomplete-exclude' => $obj->id]];
							$rowData[] = $obj->code;
							$rowData[] = $obj->name;
							$tableCells[] = $rowData;
						}
					}
				} //else if ($this->request->is(['post', 'put'])) {
			// 		$requestData = $this->request->data;
			// 		if (array_key_exists('education_subjects', $requestData[$this->alias()])) {
			// 			foreach ($requestData[$this->alias()]['education_subjects'] as $key => $obj) {
			// 				$arraySubjects[] = $obj['_joinData'];
			// 			}
			// 		}

			// 		if (array_key_exists('education_subject_id', $requestData[$this->alias()])) {
			// 			$subjectId = $requestData[$this->alias()]['education_subject_id'];
			// 			$subjectObj = $this->EducationSubjects
			// 				->findById($subjectId)
			// 				->first();

			// 			$arraySubjects[] = [
			// 				'name' => $subjectObj->name,
			// 				'code' => $subjectObj->code,
			// 				'hours_required' => 0,
			// 				'education_grade_id' => $entity->id,
			// 				'education_subject_id' => $subjectObj->id,
			// 				'visible' => 1
			// 			];
			// 		}
			// 	}

			// 	foreach ($arraySubjects as $key => $obj) {
			// 		$fieldPrefix = $attr['model'] . '.education_subjects.' . $cellCount++;
			// 		$joinDataPrefix = $fieldPrefix . '._joinData';

			// 		$subjectId = $obj['education_subject_id'];
			// 		$subjectCode = $obj['code'];
			// 		$subjectName = $obj['name'];

			// 		$cellData = "";
			// 		$cellData .= $form->input($joinDataPrefix.".hours_required", ['label' => false, 'type' => 'number', 'value' => $obj['hours_required']]);
			// 		$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $subjectId]);
			// 		$cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $subjectName]);
			// 		$cellData .= $form->hidden($joinDataPrefix.".code", ['value' => $subjectCode]);
			// 		$cellData .= $form->hidden($joinDataPrefix.".education_grade_id", ['value' => $obj['education_grade_id']]);
			// 		$cellData .= $form->hidden($joinDataPrefix.".education_subject_id", ['value' => $subjectId]);
			// 		$cellData .= $form->hidden($joinDataPrefix.".visible", ['value' => $obj['visible']]);
			// 		if (isset($obj['id'])) {
			// 			$cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
			// 		}

			// 		$rowData = [];
			// 		$rowData[] = $subjectName;
			// 		$rowData[] = $subjectCode;
			// 		$rowData[] = $cellData;
			// 		$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

			// 		$tableCells[] = $rowData;
			// 		unset($subjectOptions[$obj['education_subject_id']]);
			// 	}

				$attr['tableHeaders'] = $tableHeaders;
	    		$attr['tableCells'] = $tableCells;

	  //   		$subjectOptions[0] = "-- ".__('Add Subject') ." --";
	  //   		ksort($subjectOptions);
	  //   		$attr['options'] = $subjectOptions;
			// }
		}

		return $event->subject()->renderElement('Security.Groups/areas', ['attr' => $attr]);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('no_of_users', ['visible' => ['index' => true]]);
		$this->ControllerAction->setFieldOrder(['name', 'no_of_users']);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$query = $request->query;
		if (!array_key_exists('sort', $query) && !array_key_exists('direction', $query)) {
			$options['order'][$this->aliasField('name')] = 'asc';
		}
		$options['finder'] = ['notInInstitutions' => []];
	}

	public function findNotInInstitutions(Query $query, array $options) {
		$query->where([
			'NOT EXISTS (SELECT `id` FROM `institution_sites` WHERE `security_group_id` = `UserGroups`.`id`)'
		]);
		return $query;
	}

	public function findByUser(Query $query, array $options) {
		$userId = $options['userId'];
		$alias = $this->alias();

		$query
		->join([
			[
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUsers',
				'type' => 'LEFT',
				'conditions' => ["SecurityGroupUsers.security_group_id = $alias.id"]
			]
		])
		->where([
			'OR' => [
				"$alias.created_user_id" => $userId,
				'SecurityGroupUsers.security_user_id' => $userId
			]
		])
		->group([$this->aliasField('id')]);
		return $query;
	}

	public function onGetNoOfUsers(Event $event, Entity $entity) {
		$id = $entity->id;

		$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$count = $GroupUsers->findAllBySecurityGroupId($id)->count();

		return $count;
	}

	public function ajaxAreaAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];

			// pr($this->Areas->autocomplete($term));
			$data = $this->Areas->autocomplete($term);
			echo json_encode($data);
			die;
		}
	}
}
