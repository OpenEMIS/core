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
    public function initialize(array $config): void
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
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StudentGpa');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        
        if($this->request->getParam('controller') == 'Profiles'){
            $userId = $this->Auth->user()['id'];
            $query->where([$this->aliasField('student_id') => $userId]);
        }
         if($this->request->getParam('controller') == 'Students'){
            $queryString = $this->getQueryString();
            $studentId = $this->getQueryString('student_id');
            $encodedQueryString = $this->paramsEncode($queryString);
            $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions = $this->AcademicPeriods->getYearList();
            $selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
            $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            $programmeOptions = $EducationProgrammes
                        ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                        ->find('visible')
                        ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                        ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                        ->where(['EducationSystems.academic_period_id IS' => $selectedAcademicPeriodId])
                        ->toArray();

            $programmeOptions = array(-1 => __('-- Select Education Programme --')) + $programmeOptions;
            
                if (!empty($this->request->getQuery()['education_programme_id'])) {
                    $selectedProgramme = $this->request->getQuery()['education_programme_id'];
                } else {
                    $selectedProgramme = -1;
                }

                $extra['selectedProgramme'] = $selectedProgramme;
              //  echo "<pre>"; print_r(extra); die;
                $gradeOptions = $this->EducationGrades
                    ->find('list')
                    ->find('visible')
                    ->contain(['EducationProgrammes'])
                    ->where([$this->EducationGrades->aliasField('education_programme_id IS') => $selectedProgramme])
                    ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
                    ->toArray();

                    $gradeOptions = array(-1 => __('-- Select Education Grade --')) + $gradeOptions;
                if ($this->request->getQuery()['education_grade_id']) {
                    $selectedGrade = $this->request->getQuery()['education_grade_id'];
                } else {
                    $selectedGrade = -1;
                }
                $extra['selectedGrade'] = $selectedGrade;

            $extra['elements']['control'] = [
                'name' => 'Student.Gpa/controls',
                'data' => [
                    'academicPeriodOptions'=>$academicPeriodOptions,
                    'encodedQueryString' => $encodedQueryString,
                    'selectedAcademicPeriod'=>$selectedAcademicPeriodId,
                    'programmeOptions'=>$programmeOptions,
                    'selectedProgramme'=>$selectedProgramme,
                    'gradeOptions'=>$gradeOptions,
                    'selectedGrade'=>$selectedGrade,
                ],
                'options' => [],
                'order' => 3
            ];
           $query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriodId,$this->aliasField('education_grade_id') => $selectedGrade,$this->aliasField('student_id') => $studentId]);
        }
    }
    
}
