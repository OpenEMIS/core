<?php
namespace Education\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class EducationGradesSubjectsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $autoAllocationOptions = [];

	public function initialize(array $config)
    {
		parent::initialize($config);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

        $this->addBehavior('CompositeKey');

        $this->autoAllocationOptions = $this->getSelectOptions('general.yesno');

        $this->setDeleteStrategy('restrict');
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('hours_required')
            ->add('hours_required', 'ruleIsDecimal', [
                'rule' => ['decimal', null]
            ])
            ->add('hours_required', 'ruleRange', [
                    'rule' => ['range', 0, 999.99]
            ]);

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';

        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'education_subject_id';
        $searchableFields[] = 'code';
    }

    private function setupFields(Entity $entity)
    {
        $this->field('code', ['entity' => $entity]);
        $this->field('education_subject_id', ['type' => 'integer', 'entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'integer', 'entity' => $entity]);
        $this->field('education_programme_id', ['entity' => $entity]);
        $this->field('education_level_id', ['entity' => $entity]);
        $this->field('hours_required', ['type' => 'float', 'attr' => ['step' => 0.01]]);
        $this->field('auto_allocation');
        $this->setFieldOrder(['code', 'education_subject_id', 'education_grade_id', 'education_programme_id', 'education_level_id', 'hours_required']);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        // visible field is not used for now
        $this->field('visible', ['visible' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('education_subject_id', ['type' => 'integer']);
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->field('auto_allocation');
        $this->fields['code']['sort'] = ['field' => 'EducationSubjects.code'];
        $this->fields['education_subject_id']['sort'] = ['field' => 'EducationSubjects.name'];
        $this->fields['auto_allocation']['sort'] = ['field' => 'auto_allocation'];
        $this->setFieldOrder(['code', 'education_subject_id', 'hours_required', 'auto_allocation']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $searchKey = $this->getSearchKey();

        // Add controls filter to index page
        /*list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade) = array_values($this->_getSelectOptions());
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade'));

        $query->where([$this->aliasField('education_grade_id') => $selectedGrade]);

        $extra['auto_contain_fields'] = ['EducationSubjects' => ['code']];*/
                // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        
        //level filter
        $levelOptions = $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);
        } else{
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : 0;
        }
        $this->controller->set(compact('levelOptions', 'selectedLevel'));
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

        if (!empty($programmeOptions)) {
            $selectedProgramme = !is_null($this->request->query('programme')) ? $this->request->query('programme') : key($programmeOptions);
        } else {
            $programmeOptions = ['0' => '-- '.__('No Education Programme').' --'] + $programmeOptions;
            $selectedProgramme = !empty($this->request->query('programme')) ? $this->request->query('programme') : 0;
        }
        
        $this->controller->set(compact('programmeOptions', 'selectedProgramme'));

        $EducationGrades = $this->EducationGrades;
        $gradeOptions = $EducationGrades
            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
            ->find('visible')
            ->order([$EducationGrades->aliasField('order')])
            ->where([$EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
            ->toArray();
        if (!empty($gradeOptions)) {
            $selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);
        } else {
            $gradeOptions = ['0' => '-- '.__('No Education Grade').' --'] + $gradeOptions;
            $selectedGrade = !empty($this->request->query('grade')) ? $this->request->query('grade') : 0;
        }
        
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('gradeOptions', 'selectedGrade'));

        $query->where([$this->aliasField('education_grade_id') => $selectedGrade]);

        $extra['auto_contain_fields'] = ['EducationSubjects' => ['code']];

        if (strlen($searchKey)) {
            $extra['OR'] = [
                $this->EducationSubjects->aliasField('code').' LIKE' => '%' . $searchKey . '%',
                $this->EducationSubjects->aliasField('name').' LIKE' => '%' . $searchKey . '%',
            ];
        }

        //check for grades setup, if nothing is set, then hide the add button to prevent error
        $educationGradeCount = $this->EducationGrades->find('visible');

        if (!empty($selectedProgramme)) {
            $educationGradeCount->where([
                $this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme
            ]);

            if ($educationGradeCount->count() < 1) {
                $this->Alert->warning('EducationStructure.noGradesSetup');
                unset($extra['toolbarButtons']['add']);
            }
        } else { //no programme
            //$this->Alert->warning('EducationStructure.noProgrammesSetup'); //POCOR-5681
            unset($extra['toolbarButtons']['add']);
        }

        $sortList = ['EducationSubjects.code', 'EducationSubjects.name', 'auto_allocation'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels', 'EducationSubjects']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        list($levelOptions, $selectedLevel, $programmeOptions, $selectedProgramme, $gradeOptions, $selectedGrade) = array_values($this->_getSelectOptions());
        $this->field('education_level_id', ['selectedLevel' => $selectedLevel]);
        $this->field('education_programme_id', ['selectedProgramme' => $selectedProgramme]);
        $this->field('education_grade_id', ['selectedGrade' => $selectedGrade]);
        $this->field('education_subject_id', ['selectedGrade' => $selectedGrade]);
        $this->field('hours_required', ['type' => 'float', 'attr'=>['step' => 0.01]]);
        $this->field('auto_allocation');
        $this->setFieldOrder(['education_level_id', 'education_programme_id', 'education_grade_id', 'education_subject_id',  'hours_required', 'auto_allocation']);
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        return $entity->education_subject->code;
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->code_name;
    }

    public function onGetEducationProgrammeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->education_programme->cycle_programme_name;
    }

    public function onGetEducationLevelId(Event $event, Entity $entity)
    {
        return $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
    }

    public function onGetAutoAllocation(Event $event, Entity $entity)
    {
        // return $entity->auto_allocation == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>'; // if wanted to displayed the tick and cross
        return $this->autoAllocationOptions[$entity->auto_allocation];
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $subjectCode = '';
            if ($attr['entity']->has('education_subject')) {
                $subjectCode = $attr['entity']->education_subject->code;
            }

            $attr['type'] = 'readonly';
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

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $subjectName;
            $attr['value'] = $subjectId;
            return $attr;

        } else if ($action == 'add') {
            $gradeId = $attr['selectedGrade'];

            $existingSubjectsInGrade = $this
                ->find('list', [
                    'keyField' => 'education_subject_id',
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
                $gradeId = $attr['selectedGrade'];
                $gradeQuery = $this->EducationGrades->get($gradeId);
                $gradeName = $gradeQuery->code_name;
            }

            $attr['type'] = 'readonly';
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
                $programmeId = $attr['selectedProgramme'];
                $programmeQuery = $this->EducationGrades->EducationProgrammes->get($programmeId);
                $programmeName = $programmeQuery->cycle_programme_name;
            }

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $programmeName;
            return $attr;
        }
    }

    public function onUpdateFieldEducationLevelId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'edit') {
                $levelName = '';
                if ($attr['entity']->has('education_grade') && $attr['entity']->education_grade->has('education_programme') && $attr['entity']->education_grade->education_programme->has('education_cycle') && $attr['entity']->education_grade->education_programme->education_cycle->has('education_level')) {
                    $levelName = $attr['entity']->education_grade->education_programme->education_cycle->education_level->system_level_name;
                }

            } else {
                $levelId = $attr['selectedLevel'];
                $levelQuery = $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->get($levelId);
                $levelName = $levelQuery->system_level_name;
            }

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $levelName;
            return $attr;
        }
    }

    public function onUpdateFieldAutoAllocation(Event $event, array $attr, $action, Request $request)
    {
        // setting the tooltip message
        $tooltipMessage = $this->getMessage($this->alias().'.tooltip_message');
        $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        $attr['attr']['label']['text'] = __(Inflector::humanize($attr['field'])) . $this->tooltipMessage($tooltipMessage);


        $options = $this->autoAllocationOptions;
        $attr['options'] = $options;
        $attr['select'] = false;

        return $attr;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        // populate 'to be deleted' field
        $subject = $this->EducationSubjects->get($entity->education_subject_id);
        $entity->name = $subject->code_name;

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

        //check textbook
        $Textbooks = TableRegistry::get('Textbook.Textbooks');
        $associatedTextbooksCount = $Textbooks->find()
            ->where([
                $Textbooks->aliasField('education_subject_id') => $subjectId,
                $Textbooks->aliasField('education_grade_id') => $gradeId
            ])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Textbooks', 'count' => $associatedTextbooksCount];
    }

    public function _getSelectOptions()
    {
        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //Return all required options and their key
        $levelOptions = $this->EducationGrades->EducationProgrammes->EducationCycles->EducationLevels->getLevelOptions($selectedAcademicPeriod);
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

        $EducationGrades = $this->EducationGrades;
        $gradeOptions = $EducationGrades
            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
            ->find('visible')
            ->order([$EducationGrades->aliasField('order')])
            ->where([$EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
            ->toArray();
        $selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);

        return compact('levelOptions', 'selectedLevel', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'selectedGrade');
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }
}
