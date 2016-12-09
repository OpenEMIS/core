<?php
namespace Education\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class EducationGradesSubjectsTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		parent::initialize($config);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

        $this->setDeleteStrategy('restrict');
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('hours_required', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ]);

        return $validator;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'remove') {
            $entity = $extra['entity'];
            $subject = $this->EducationSubjects->get($entity->education_subject_id);
            $subjectName = $subject->code_name;
            $entity->name = $subjectName;
        }

        $this->field('visible', ['visible' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

        $this->controller->set('toolbarElements', $toolbarElements);

        $this->field('code');
        $this->field('education_grade_id', ['visible' => 'hidden']);
        $this->setFieldOrder(['code', 'education_subject_id', 'hours_required']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade) = array_values($this->_getSelectOptions());
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade'));

        $query->where([$this->aliasField('education_grade_id') => $selectedGrade]);

        $extra['auto_contain_fields'] = ['EducationSubjects' => ['code']];
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->setFieldOrder(['code', 'education_subject_id', 'education_grade_id',  'hours_required']);
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        return $entity->education_subject->code;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('code', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('education_subject_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->setFieldOrder(['code', 'education_subject_id', 'education_grade_id',  'hours_required']);
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $subjectId = $attr['entity']->education_subject_id;
            $subjectQuery = $this->EducationSubjects->get($subjectId);
            $subjectCode = $subjectQuery->code;
            $attr['attr']['value'] = $subjectCode;
            return $attr;
        }

    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $subjectId = $attr['entity']->education_subject_id;
            $subjectQuery = $this->EducationSubjects->get($subjectId);
            $subjectName = $subjectQuery->name;
            $attr['attr']['value'] = $subjectName;
            return $attr;
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $gradeId = $attr['entity']->education_grade_id;
            $gradeQuery = $this->EducationGrades->get($gradeId);
            $gradeName = $gradeQuery->name;
            $attr['attr']['value'] = $gradeName;
            return $attr;
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $gradeId = $entity->education_grade_id;
        $subjectId = $entity->education_subject_id;

        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $institutionSubjectsList = $InstitutionSubjects
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->where([
                $InstitutionSubjects->aliasField('education_subject_id') => $subjectId
            ])
            ->toArray();

        $associatedClassCount = 0;
        if (!empty($institutionSubjectsList)) {
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $associatedClassCount = $InstitutionClasses->find()
            ->matching('ClassGrades')
            ->matching('ClassSubjects')
            ->where([
                'ClassGrades.education_grade_id' => $gradeId,
                'ClassSubjects.institution_subject_id IN' => $institutionSubjectsList
            ])
            ->count();
        }
        $extra['associatedRecords'][] = ['model' => 'InstitutionClass', 'count' => $associatedClassCount];

        $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $associatedSubjectStudentsCount = $SubjectStudents->find()
            ->matching('InstitutionClasses.ClassGrades')
            ->where([
                $SubjectStudents->aliasField('education_subject_id') => $subjectId,
                'ClassGrades.education_grade_id' => $gradeId
            ])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'InstitutionSubjectStudents', 'count' => $associatedSubjectStudentsCount];
    }

    public function _getSelectOptions()
    {
        //Return all required options and their key
        $levelOptions = $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->getLevelOptions();
        $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

        $EducationCycles = $this->EducationGrades->EducationProgrammes->EducationCycles;
        $cycleIds = $EducationCycles
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->find('visible')
            ->where([$EducationCycles->aliasField('education_level_id') => $selectedLevel])
            ->toArray();

        if (is_array($cycleIds) && !empty($cycleIds)) {
            $cycleIds = implode(', ', $cycleIds);
        } else {
            $cycleIds = 0;
        }

        $EducationProgrammes = $this->EducationGrades->EducationProgrammes;
        $programmeOptions = $EducationProgrammes
            ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
            ->find('visible')
            ->contain(['EducationCycles'])
            ->order([
                $EducationCycles->aliasField('order'),
                $EducationProgrammes->aliasField('order')
            ])
            ->where([
                $EducationProgrammes->aliasField('education_cycle_id') . ' IN (' .  $cycleIds . ')'
            ])
            ->toArray();
        $selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);

        $EducationGrades = $this->EducationGrades;
        $gradeOptions = $EducationGrades
            ->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
            ->find('visible')
            ->order([
                $EducationGrades->aliasField('order')
            ])
            ->where([
                $EducationGrades->aliasField('education_programme_id') => $selectedProgramme
            ])
            ->toArray();
        $selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);

        return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade');
    }
}
