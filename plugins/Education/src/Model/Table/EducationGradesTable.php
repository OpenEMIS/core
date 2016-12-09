<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class EducationGradesTable extends ControllerActionTable
{
	private $_contain = ['EducationSubjects._joinData'];
	private $_fieldOrder = ['name', 'code', 'education_programme_id', 'visible'];

	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'institution_grades',
			'foreignKey' => 'education_grade_id',
			'targetForeignKey' => 'Institution_id',
			'through' => 'Institution.InstitutionGrades',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);
		$this->belongsTo('EducationProgrammes',		['className' => 'Education.EducationProgrammes']);
		$this->hasMany('Assessments',				['className' => 'Assessment.Assessments', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionFees',			['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('Rubrics',					['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionClassGrades',	['className' => 'Institution.InstitutionClassGrades', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionClassStudents',	['className' => 'Institution.InstitutionClassStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionStudents',		['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentAdmission',			['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentDropout',			['className' => 'Institution.StudentDropout', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->belongsToMany('EducationSubjects', [
			'className' => 'Education.EducationSubjects',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_grade_id',
			'targetForeignKey' => 'education_subject_id',
			'through' => 'Education.EducationGradesSubjects',
			'dependent' => true,
			'cascadeCallbacks' => true
			// 'saveStrategy' => 'append'
		]);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_programme_id',
			]);
		}

		$this->setDeleteStrategy('restrict');
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew()) {
			if ($entity->setVisible) {
				// to be revisit
				// $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
				// $EducationGradesSubjects->updateAll(
				// 	['visible' => 0],
				// 	['education_grade_id' => $entity->id]
				// );
			}
		}
	}

	 /**
     * Method to get the education system id for the particular grade given
     *
     * @param integer $gradeId The grade id to check for
     * @return integer Education system id that the grade belongs to
     */
	public function getEducationSystemId($gradeId) {
		$educationSystemId = $this->find()
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
			->where([$this->aliasField('id') => $gradeId])
			->first();
		return $educationSystemId->education_programme->education_cycle->education_level->education_system->id;
	}

	 /**
     * Method to check the list of the grades that belongs to the education system
     *
     * @param integer $systemId The education system id to check for
     * @return array A list of the education system grades belonging to that particular education system
     */
	public function getEducationGradesBySystem($systemId) {
		$educationSystemId = $this->find('list', [
				'keyField' => 'id',
				'valueField' => 'id'
			])
			->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
			->where(['EducationSystems.id' => $systemId])->toArray();
		return $educationSystemId;
	}

	/**
	* Method to get the list of available grades by a given education grade
	*
	* @param integer $gradeId The grade to find the list of available education grades
	* @param bool|true $getNextProgrammeGrades If flag is set to false, it will only fetch all the education
	*											grades of the same programme. If set to true it will get all
	*											the grades of the next programmes plus the current programme grades
	*/
	public function getNextAvailableEducationGrades($gradeId, $getNextProgrammeGrades=true) {
		if (!empty($gradeId)) {
			$gradeObj = $this->get($gradeId);
			$programmeId = $gradeObj->education_programme_id;
			$order = $gradeObj->order;
			$gradeOptions = $this->find('list', [
					'keyField' => 'id',
					'valueField' => 'programme_grade_name'
				])
				->where([
					$this->aliasField('education_programme_id') => $programmeId,
					$this->aliasField('order').' > ' => $order
				])
				->order([$this->aliasField('order')])
				->toArray();
			// Default is to get the list of grades with the next programme grades
			if ($getNextProgrammeGrades) {
				$nextProgrammesGradesOptions = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->getNextGradeList($programmeId);
				$results = $gradeOptions + $nextProgrammesGradesOptions;
			} else {
				$results = $gradeOptions;
			}
			return $results;
		} else {
			return [];
		}
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
		$this->association('Institutions')->name('InstitutionProgrammes');
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);

		$this->_fieldOrder = ['visible', 'name', 'code', 'education_programme_id'];
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
		$extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme'));
		$query->where([$this->aliasField('education_programme_id') => $selectedProgramme]);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('education_programme_id');
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
	{
		list(, , $programmeOptions, $selectedProgramme) = array_values($this->_getSelectOptions());
		$attr['options'] = $programmeOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedProgramme;
		}

		return $attr;
	}

	public function _getSelectOptions()
	{
		//Return all required options and their key
		$levelOptions = $this->EducationProgrammes->EducationCycles->EducationLevels->getLevelOptions();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		$cycleIds = $this->EducationProgrammes->EducationCycles
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->find('visible')
			->where([$this->EducationProgrammes->EducationCycles->aliasField('education_level_id') => $selectedLevel])
			->toArray();

		if (is_array($cycleIds) && !empty($cycleIds)) {
			$cycleIds = implode(', ', $cycleIds);
		} else {
			$cycleIds = 0;
		}

		$EducationProgrammes = $this->EducationProgrammes;
		$programmeOptions = $EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('visible')
			->contain(['EducationCycles'])
			->order([
				$EducationProgrammes->EducationCycles->aliasField('order'),
				$EducationProgrammes->aliasField('order')
			])
			->where([
				$EducationProgrammes->aliasField('education_cycle_id') . ' IN (' .  $cycleIds . ')'
			])
			->toArray();
		$selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);

		return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme');
	}

    public function getEducationGradesByProgrammes($programmeId) 
    {
        $gradeOptions = $this
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->aliasField('education_programme_id') => $programmeId])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->aliasField('order') => 'ASC'])
                        ->toArray();

        return $gradeOptions;
    } 

    public function findGradeSubjectsByProgramme(Query $query, $options)
    {
    	$educationProgrammeId = $options['education_programme_id'];
    	$query
    		->find('visible')
    		->contain(['EducationSubjects'])
    		->where([$this->aliasField('education_programme_id') => $educationProgrammeId])
    		->order([$this->aliasField('order')]);

    	return $query;
    }
}
