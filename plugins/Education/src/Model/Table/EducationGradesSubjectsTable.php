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
    private $defaultProgramme;
    private $defaultGrade;

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
        // populate 'to be deleted' field for removeBehavior
        if ($this->action == 'remove') {
            $entity = $extra['entity'];
            $subject = $this->EducationSubjects->get($entity->education_subject_id);
            $subjectName = $subject->code_name;
            $entity->name = $subjectName;
        }

        // visible field is not used for now
        $this->field('visible', ['visible' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('education_grade_id', ['visible' => 'hidden']);
        $this->setFieldOrder(['code', 'education_subject_id', 'hours_required']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Add controls filter to index page
        list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade) = array_values($this->_getSelectOptions());
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade'));

        $query->where([$this->aliasField('education_grade_id') => $selectedGrade]);

        $extra['auto_contain_fields'] = ['EducationSubjects' => ['code']];
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('education_programme_id');
        $this->setFieldOrder(['code', 'education_subject_id', 'education_grade_id', 'education_programme_id', 'hours_required']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query->contain(['EducationGrades.EducationProgrammes', 'EducationSubjects']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('code', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('education_subject_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('education_programme_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->setFieldOrder(['code', 'education_subject_id', 'education_grade_id', 'education_programme_id', 'hours_required']);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->_getSelectOptions();

        $this->field('education_programme_id', ['type' => 'readonly']);
        $this->field('education_grade_id', ['type' => 'readonly']);
        $this->field('education_subject_id');
        $this->setFieldOrder(['education_programme_id', 'education_grade_id', 'education_subject_id',  'hours_required']);
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        return $entity->education_subject->code;
    }

    public function onGetEducationProgrammeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->education_programme->cycle_programme_name;
    }


    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $subjectCode = '';
            if ($attr['entity']->has('education_subject')) {
                $subjectCode = $attr['entity']->education_subject->code;
            }

            $attr['attr']['value'] = $subjectCode;
            return $attr;
        }

    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $subjectId = $attr['entity']->education_subject_id;

            $subjectName = '';
            if ($attr['entity']->has('education_subject')) {
                $subjectName = $attr['entity']->education_subject->name;
            }

            $attr['attr']['value'] = $subjectName;
            $attr['value'] = $subjectId;
            return $attr;

        } else if ($action == 'add') {
            $gradeId = !is_null($this->request->query('grade')) ? $this->request->query('grade') : $this->defaultGrade;

            $existingSubjectsInGrade = $this
                ->find('list', [
                    'keyField' =>'education_subject_id',
                    'valueField' => 'education_subject_id'
                ])
                ->where([$this->aliasField('education_grade_id') => $gradeId])
                ->toArray();

            $subjectQuery = $this->EducationSubjects
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->find('visible')
                ->find('order');

            // only show subjects that have not been added in the grade
            if (!empty($existingSubjectsInGrade)) {
                $subjectQuery->where([$this->EducationSubjects->aliasField('id NOT IN') => $existingSubjectsInGrade]);
            }

            $subjectOptions = $subjectQuery->toArray();

            if (!empty($subjectOptions)) {
                $subjectOptions = ['' => '-- ' . __('Add Subject') . ' --'] + $subjectOptions;
            }

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['options'] = $subjectOptions;
            return $attr;
        }

    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'edit') {
                $gradeId = $attr['entity']->education_grade_id;

                $gradeName = '';
                if ($attr['entity']->has('education_grade')) {
                    $gradeName = $attr['entity']->education_grade->code_name;
                }
            } else {
                $gradeId = !is_null($this->request->query('grade')) ? $this->request->query('grade') : $this->defaultGrade;
                $gradeQuery = $this->EducationGrades->get($gradeId);
                $gradeName = $gradeQuery->code_name;
            }

            $attr['attr']['value'] = $gradeName;
            $attr['value'] = $gradeId;
            return $attr;
        }
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'edit') {
                $programmeName = '';
                if ($attr['entity']->has('education_grade') && $attr['entity']->education_grade->has('education_programme')) {
                    $programmeName = $attr['entity']->education_grade->education_programme->cycle_programme_name;
                }

            } else {
                $programmeId = !is_null($this->request->query('programme')) ? $this->request->query('programme') : $this->defaultProgramme;
                $programmeQuery = $this->EducationGrades->EducationProgrammes->get($programmeId);
                $programmeName = $programmeQuery->cycle_programme_name;
            }

            $attr['attr']['value'] = $programmeName;
            return $attr;
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $gradeId = $entity->education_grade_id;
        $subjectId = $entity->education_subject_id;

        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $associatedInstitutionSubjectsCount = $InstitutionSubjects->find()
            ->matching('ClassSubjects.InstitutionClasses.ClassGrades')
            ->where([
                $InstitutionSubjects->aliasField('education_subject_id') => $subjectId,
                'ClassGrades.education_grade_id' => $gradeId
            ])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'InstitutionSubjects', 'count' => $associatedInstitutionSubjectsCount];

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
            ->where([$EducationProgrammes->aliasField('education_cycle_id') . ' IN (' .  $cycleIds . ')'])
            ->toArray();
        $selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);
        $this->defaultProgramme = key($programmeOptions);

        $EducationGrades = $this->EducationGrades;
        $gradeOptions = $EducationGrades
            ->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
            ->find('visible')
            ->order([$EducationGrades->aliasField('order')])
            ->where([$EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
            ->toArray();
        $selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);
        $this->defaultGrade = key($gradeOptions);

        return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade');
    }
}
