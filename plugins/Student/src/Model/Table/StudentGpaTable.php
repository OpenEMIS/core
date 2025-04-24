<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Authentication\IdentityInterface;
use Cake\ORM\Query;

/**
 * POCOR-8222
 * GPA's and Cummulative GPA develop
**/
class StudentGpaTable extends ControllerActionTable
{
    public function initialize(array $config):void
    {
        $this->setTable('institution_students_gpa');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('view', false);
        $this->addBehavior('Institution.InstitutionTab');
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->field('institution_id', ['type' => 'hidden']);
        $this->field('education_grades_gpa_id', ['type' => 'hidden']);
        $this->field('gpa_name');
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        if($this->getAlias() == 'StudentGpa' && $this->request->getParam('controller') == 'Students'){
            $selectedAlias = 'StudentGpa';
        }if($this->getAlias() == 'StudentGpa' && $this->request->getParam('controller') == 'Profiles'){
            $selectedAlias = 'Gpa';
        }else{
            $selectedAlias = $this->getAlias();
        }
        $tabElements = $this->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAlias);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionGrade = TableRegistry::get('Institution.InstitutionGrades');
        if($this->request->getParam('controller') == 'Profiles') {
            $userId = $this->Auth->user()['id'];
            $Classes = TableRegistry::get('Institution.InstitutionClasses');
            $classStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
            $institution = TableRegistry::get('Institution.Institutions');
            $gpaGrades = TableRegistry::get('Gpa.GpaSystem');

            // Academic Periods filter
            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $academicPeriodOptions = ['-1' => '-- '.__('All Academic Period').' --'] + $academicPeriodOptions;
            $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : -1;
            $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
            $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;

            // Institution Filter
            $InstitutionOptions = [];
            $InstitutionOptions = $institutionStudents->find()
                ->where([
                    $institutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $institutionStudents->aliasField('student_id') => $userId,
                ])
                ->extract('institution_id')
                ->toArray();

            if (!empty($InstitutionOptions)) {
                $InstitutionOptions = $institution->find('list')
                    ->where([
                        $institution->aliasField('id IN') => $InstitutionOptions
                    ])
                    ->toArray();
            }
            $InstitutionOptions = ['-1' => '-- '.__('All Institution').' --'] + $InstitutionOptions;
            $selectedInstitution = !is_null($this->request->getQuery('institution_id')) ? $this->request->getQuery('institution_id') : -1;
            $this->controller->set(compact('InstitutionOptions', 'selectedInstitution'));

            // Education Grade Filter
            $educationGradeOptions = [];
            $availableGrades = $InstitutionGrades->find()
                ->where([
                    $InstitutionGrades->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $InstitutionGrades->aliasField('institution_id') => $selectedInstitution,
                ])
                ->extract('education_grade_id')
                ->toArray();

            if (!empty($availableGrades)) {
                $educationGradeOptions = $this->EducationGrades->find('list')
                    ->where([
                        $this->EducationGrades->aliasField('id IN') => $availableGrades
                    ])
                    ->toArray();
            }
    
            $educationGradeOptions = ['-1' => '-- '.__('All Education Grade').' --'] + $educationGradeOptions;
            $selectedGrade = !is_null($this->request->getQuery('education_grade_id')) ? $this->request->getQuery('education_grade_id') : -1;
            $this->controller->set(compact('educationGradeOptions', 'selectedGrade'));
            $where[$this->aliasField('education_grade_id')] = $selectedGrade;

            // Class Filter
            $classOptions = [];
            $selectedClass = !is_null($this->request->getQuery('class_id')) ? $this->request->getQuery('class_id') : -1;

            $institutionId = $classStudents->find()
                ->where([
                    $classStudents->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $classStudents->aliasField('education_grade_id') => $selectedGrade,
                    $classStudents->aliasField('student_status_id') => 1
                ])
                ->first()
                ->institution_id;

            if (!empty($this->request->getQuery('education_grade_id'))) {
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        'ClassGrades.education_grade_id' => $this->request->getQuery('education_grade_id'),
                        $Classes->aliasField('institution_id') => $institutionId,
                    ])
                    ->group([$Classes->aliasField('id')])
                    ->order([$Classes->aliasField('name')])
                    ->toArray();
            } else {
                $selectedClass = -1;
            }

            if (!empty($classOptions)) {
                $classOptions['all'] = "All Classes";
            }

            $classOptions = ['-1' => '-- ' . __('All Institution Class') . ' --'] + $classOptions;
            $this->controller->set(compact('classOptions', 'selectedClass'));

            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $encodedQueryString = $this->request->getParam('pass')[1];

            $extra['elements']['controls'] = [
                'name' => 'Profile.Gpa/studentcontrols',
                'data' => ['encodedQueryString' => $encodedQueryString],
                'options' => [],
                'order' => 1
            ];

            // Filtering conditions based on selected options
            if ($selectedAcademicPeriod == -1 || $selectedAcademicPeriod === null) {
                return $query->where([
                    $this->aliasField('student_id') => $userId
                ]);
            } elseif ($selectedAcademicPeriod != -1 && $selectedGrade == -1) {
                return $query->where([
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('academic_period_id') => $selectedAcademicPeriod
                ]);
            } elseif ($selectedAcademicPeriod != -1 && $selectedGrade != -1 && $selectedClass == -1) {
                return $query->where([
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $this->aliasField('education_grade_id') => $selectedGrade
                ]);
            } else {
                return $query
                    ->innerJoin(
                        ['InstitutionClassStudents' => 'institution_class_students'],
                        ['InstitutionClassStudents.student_id = ' . $this->aliasField('student_id')]
                    )
                    ->where([
                        $this->aliasField('student_id') => $userId,
                        $this->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $this->aliasField('education_grade_id') => $selectedGrade,
                        'InstitutionClassStudents.student_id' => $userId,
                        'InstitutionClassStudents.institution_class_id' => $selectedClass,
                    ]);
            }
        }

        if($this->request->getParam('controller') == 'Students'){
            $queryString = $this->getQueryString();
            $studentId = $this->getQueryString('student_id');
            $encodedQueryString = $this->paramsEncode($queryString);
            $Classes = TableRegistry::get('Institution.InstitutionClasses');
            $classStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $gpaGrades = TableRegistry::get('Gpa.GpaSystem');
            $institutionId = $this->getInstitutionID();

            // Academic Periods filter
            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $academicPeriodOptions = ['-1' => '-- '.__('All Academic Period').' --'] + $academicPeriodOptions;
            $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id'): -1 ;
            $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
            $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
            //End

            $educationGradeOptions = [];
            $availableGrades = $institutionGrade->find()
                        ->where([
                            $institutionGrade->aliasField('academic_period_id') => $selectedAcademicPeriod,
                            $institutionGrade->aliasField('institution_id') => $institutionId,
                        ])
                        ->extract('education_grade_id')
                        ->toArray();
            if (!empty($availableGrades)) {
                $educationGradeOptions = $this->EducationGrades->find('list')
                    ->where([
                        $this->EducationGrades->aliasField('id IN') => $availableGrades
                    ])
                    ->toArray();

            } 
            $educationGradeOptions = ['-1' => '-- '.__('All Education Grade').' --'] + $educationGradeOptions;
            $selectedGrade = !is_null($this->request->getQuery('education_grade_id')) ? $this->request->getQuery('education_grade_id') : -1;
            $this->controller->set(compact('educationGradeOptions', 'selectedGrade'));
            //End

            // Class filter
            $classOptions = [];
            $selectedClass = !is_null($this->request->getQuery('class_id')) ? $this->request->getQuery('class_id') : -1;
            if (!empty($this->request->getQuery('education_grade_id'))) {
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        'ClassGrades.education_grade_id' => $this->request->getQuery('education_grade_id'),
                        $Classes->aliasField('institution_id') => $institutionId,
                    ])->group([$Classes->aliasField('id')])
                    ->order([$Classes->aliasField('name')])
                    ->toArray();
            } else {
                
                $selectedClass = -1;
            }

            if (!empty($classOptions)) {
                $classOptions['all'] = "All Institution Classes";
            }
            
            $classOptions = ['-1' => '-- ' . __('All Institution Class') . ' --'] + $classOptions;
            $this->controller->set(compact('classOptions', 'selectedClass'));
            $where[$this->aliasField('education_grade_id')] = $selectedGrade;
           
            //End
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $encodedQueryString = $this->request->getParam('pass')[1];

            $extra['elements']['controls'] = ['name' => 'Profile.Gpa/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

            // Check if academic period is unselected or null
            if ($selectedAcademicPeriod == -1 || $selectedAcademicPeriod === null) {
                return $query->where([
                    $this->aliasField('student_id') => $studentId
                ]);
            }

            // Check if only academic period is selected
            elseif ($selectedAcademicPeriod != -1 && $selectedGrade == -1) {
                return $query->where([
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('academic_period_id') => $selectedAcademicPeriod
                ]);
            }

            // Check if academic period and education grade are selected, but class is unselected
            elseif ($selectedAcademicPeriod != -1 && $selectedGrade != -1 && $selectedClass == -1) {
                return $query->where([
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $this->aliasField('education_grade_id') => $selectedGrade
                ]);
            }

            // If all filters are selected, including class
            else {
                return $query
                    ->innerJoin(
                        ['InstitutionClassStudents' => 'institution_class_students'],
                        ['InstitutionClassStudents.student_id = ' . $this->aliasField('student_id')]
                    )
                    ->where([
                        $this->aliasField('student_id') => $studentId,
                        $this->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $this->aliasField('education_grade_id') => $selectedGrade,
                        'InstitutionClassStudents.student_id' => $studentId,
                        'InstitutionClassStudents.institution_class_id' => $selectedClass,
                    ]);
            }

        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'gpa') {
            return __('GPA');
        } else if ($field == 'cumulative_gpa') {
            return  __('Cumulative GPA');
        }else if ($field == 'gpa_name') {
            return  __('GPA Name');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetGpaNameOld(Event $event, Entity $entity)
    {
        $studentGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
        $gpaTable = TableRegistry::get('Gpa.GpaSystem');
        $gpaRecord = $studentGpa->find()
                        ->select(['name' => $gpaTable->aliasField('name')])
                        ->leftJoin(
                            [$gpaTable->getAlias() => $gpaTable->getTable()],
                            $gpaTable->aliasField('id') . ' = ' . $studentGpa->aliasField('education_grades_gpa_id')
                        )
                        ->where([
                            $studentGpa->aliasField('academic_period_id') => $entity->academic_period_id,
                            $studentGpa->aliasField('student_id') => $entity->student_id,
                            $studentGpa->aliasField('institution_id') => $entity['institution']['id'],
                            $studentGpa->aliasField('education_grade_id') => $entity->education_grade_id,
                            $studentGpa->aliasField('education_grades_gpa_id') => $entity->education_grades_gpa_id
                        ])
                        ->first();
                        
            if(!empty($gpaRecord)){
                return $gpaRecord->name ;
            }
        
        return '';
    }

    //POCOR-8962 -- function updated for null conditions
    public function onGetGpaName(Event $event, Entity $entity)
    {
        $studentGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
        $gpaTable = TableRegistry::get('Gpa.GpaSystem');

        $conditions = [
            $studentGpa->aliasField('academic_period_id') => $entity->academic_period_id,
            $studentGpa->aliasField('student_id') => $entity->student_id,
            $studentGpa->aliasField('institution_id') => $entity['institution']['id'],
            $studentGpa->aliasField('education_grade_id') => $entity->education_grade_id,
        ];

        // Handle null value properly
        if ($entity->education_grades_gpa_id === null) {
            $conditions[] = new \Cake\Database\Expression\QueryExpression(
                $studentGpa->aliasField('education_grades_gpa_id') . ' IS NULL'
            );
        } else {
            $conditions[$studentGpa->aliasField('education_grades_gpa_id')] = $entity->education_grades_gpa_id;
        }

        $gpaRecord = $studentGpa->find()
            ->select(['name' => $gpaTable->aliasField('name')])
            ->leftJoin(
                [$gpaTable->getAlias() => $gpaTable->getTable()],
                $gpaTable->aliasField('id') . ' = ' . $studentGpa->aliasField('education_grades_gpa_id')
            )
            ->where($conditions)
            ->first();

        return !empty($gpaRecord) ? $gpaRecord->name : '';
    }
    
}
