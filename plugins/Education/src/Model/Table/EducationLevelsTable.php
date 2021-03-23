<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class EducationLevelsTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('EducationLevelIsced', ['className' => 'Education.EducationLevelIsced']);
		$this->belongsTo('EducationSystems', ['className' => 'Education.EducationSystems']);
		$this->hasMany('EducationCycles', ['className' => 'Education.EducationCycles']);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_system_id',
			]);
		}

		$this->setDeleteStrategy('restrict');
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['education_level_isced_id']['sort'] = ['field' => 'EducationLevelIsced.name'];
		$this->fields['education_system_id']['sort'] = ['field' => 'EducationSystems.name'];
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$query->where([$this->aliasField('education_system_id') => $entity->education_system_id]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		// Academic period filter
	    $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        // Education System filter
        $systemOptions = $this->EducationSystems->getSystemOptions($selectedAcademicPeriod);
        if (!empty($systemOptions )) {
        	$selectedSystem = !empty($this->request->query('system')) ? $this->request->query('system') : key($systemOptions);
        } else {
        	$systemOptions = ['0' => '-- '.__('No Education System').' --'] + $systemOptions;
        	$selectedSystem = !empty($this->request->query('system')) ? $this->request->query('system') : 0;
        }
        
        $this->controller->set(compact('systemOptions', 'selectedSystem'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_system_id') => $selectedSystem])
                        ->order([$this->aliasField('order')]);
        
        //sort
		$sortList = ['name', 'EducationLevelIsced.name', 'EducationSystems.name'];
		if (array_key_exists('sortWhitelist', $extra['options'])) {
			$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
		}
		$extra['options']['sortWhitelist'] = $sortList;
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('education_system_id');
		$this->fields['education_level_isced_id']['type'] = 'select';
	}

	public function onUpdateFieldEducationSystemId(Event $event, array $attr, $action, Request $request)
	{
		list($systemOptions, $selectedSystem) = array_values($this->getSelectOptions());
		$attr['options'] = $systemOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedSystem;
		}

		return $attr;
	}

	public function findWithSystem(Query $query, array $options)
	{
		return $query
			->contain(['EducationSystems'])
			->order(['EducationSystems.order' => 'ASC', $this->aliasField('order') => 'ASC']);
	}

	public function getSelectOptions()
	{
		 //Return all required options and their key
		$systemOptions = $this->EducationSystems
			->find('list')
			->find('visible')
			->find('order')
			->toArray();
		$selectedSystem = !is_null($this->request->query('system')) ? $this->request->query('system') : key($systemOptions);

		return compact('systemOptions', 'selectedSystem');
	}

	public function getEducationLevelOptions($selectedAcademicPeriod)
	{
		$educationSystems = TableRegistry::get('Education.EducationSystems');

		$list = $this
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('visible')
			->contain(['EducationSystems'])
			->where([$educationSystems->aliasField('academic_period_id') => $selectedAcademicPeriod])
			->order([
				$this->EducationSystems->aliasField('order'),
				$this->aliasField('order')
			])
			->toArray();

		return $list;
	}

	public function getLevelOptions()
	{
		$list = $this
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('visible')
			->contain(['EducationSystems'])
			->order([
				$this->EducationSystems->aliasField('order'),
				$this->aliasField('order')
			])
			->toArray();

		return $list;
	}

    public function getLevelOptionsByInstitution($institutionId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        $list = $InstitutionGrades
            ->find()
            ->select(['level_id' => 'EducationLevels.id', 'level_name' => 'EducationLevels.name', 'system_name' => 'EducationSystems.name'])
            ->matching('EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems')
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->order(['EducationSystems.order', 'EducationLevels.order'])
            ->group(['level_id'])
            ->toArray();

        $returnList = [];
        foreach ($list as $key => $value) {
            $returnList[$value->level_id] = $value->system_name . " - " . $value->level_name;
        }

        return $returnList;
    }

    //updating type of academic period
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list(,,  $systemOptions, $selectedSystem) = array_values($this->getSelectOptions());
        $attr['options'] = $cycleOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedCycle;
        }

        return $attr;
    }

}
