<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StudentReportCardsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_students_report_cards');

        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['principal_comments']['visible'] = false;
        $this->fields['homeroom_teacher_comments']['visible'] = false;
        $this->fields['file_name']['visible'] = false;
        $this->fields['file_content']['visible'] = false;
        $this->fields['started_on']['visible'] = false;
        $this->fields['completed_on']['visible'] = false;
        $this->fields['status']['visible'] = false;
        $this->fields['file_content_pdf']['visible'] = false;
        $this->fields['report_card_id']['type'] = 'integer';
        $this->fields['education_grade_id']['type'] = 'integer';
        $this->fields['institution_id']['type'] = 'integer';
        $this->fields['academic_period_id']['type'] = 'integer';
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['academic_period_id', 'institution_id', 'report_card_id', 'education_grade_id', 'institution_class_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {   
        $user = $this->Auth->user();
       
        $InstitutionStudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $StudentGuardians = TableRegistry::get('student_guardians');

        //Start POCOR-7055
        if ($user['is_student'] == 1 && $user['is_guardian'] == 1 && $user['is_staff'] == 1) {
            $query
            ->contain('AcademicPeriods', 'Institutions', 'EducationGrades')            
            ->where([$this->aliasField('status') => $InstitutionStudentsReportCards::PUBLISHED])
            ->order(['AcademicPeriods.order', 'Institutions.name', 'EducationGrades.order']);
        }//End POCOR-7055
        
        else if ($user['is_student'] == 1) {
            $query
            ->contain('AcademicPeriods', 'Institutions', 'EducationGrades')            
            ->where([$this->aliasField('student_id') => $user['id']])   //  POCOR-5910
            //->where([$this->aliasField('status') => $InstitutionStudentsReportCards::PUBLISHED])
            ->order(['AcademicPeriods.order', 'Institutions.name', 'EducationGrades.order']);
        }else if($user['is_guardian'] == 1){ //POCOR-6202 starts
            $session = $this->request->session();//POCOR-6267
            //$studentId = $session->read('Student.Students.id');
            $student_id = $session->read('Student.Students.id'); 
            if ($this->request->params['pass'][1]) {
                $student_id = $this->ControllerAction->paramsDecode($this->request->params['pass'][1])['id']; 
            }

            $query
            ->contain('AcademicPeriods', 'Institutions', 'EducationGrades') 
            ->leftJoin(
                [$StudentGuardians->alias() => $StudentGuardians->table()],
                [
                    $StudentGuardians->aliasField('student_id = ') . $this->aliasField('student_id')
                ]
            )    
            ->where([
                $this->aliasField('status') => $InstitutionStudentsReportCards::PUBLISHED,
                $StudentGuardians->aliasField('guardian_id') => $user['id'],
                $StudentGuardians->aliasField('student_id') => $student_id 
            ])
            ->order(['AcademicPeriods.order', 'Institutions.name', 'EducationGrades.order']);//POCOR-6202 ends
        }else{
            $query
            ->contain('AcademicPeriods', 'Institutions', 'EducationGrades')            
            ->where([$this->aliasField('status') => $InstitutionStudentsReportCards::PUBLISHED])
            ->order(['AcademicPeriods.order', 'Institutions.name', 'EducationGrades.order']);
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['academic_period_id', 'report_card_id', 'institution_id', 'institution_class_id', 'education_grade_id']);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
   
        $downloadAccess = false;
        if ($this->controller->name == 'Students') {
            $downloadAccess = $this->AccessControl->check(['Students', 'ReportCards', 'download']);
        } else if ($this->controller->name == 'Directories') {
            $downloadAccess = $this->AccessControl->check(['Directories', 'StudentReportCards', 'download']);
        } else if ($this->controller->name == 'Profiles') {
            $downloadAccess = $this->AccessControl->check(['Profiles', 'StudentReportCards', 'download']);
            unset($buttons['view']);
        }
        /**POCOR-6845 starts - Added condition to get download button when logged in as Guardian*/  
        else if ($this->controller->name == 'GuardianNavs') {
            $downloadAccess = $this->AccessControl->check(['GuardianNavs', 'StudentReportCards', 'download']);
        }
        /**POCOR-6845 ends*/
        if ($downloadAccess) {
            $params = [
                'report_card_id' => $entity->report_card_id,
                'student_id' => $entity->student_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'education_grade_id' => $entity->education_grade_id
            ];

            $url = $this->url('downloadPdf');
            $url[1] = $this->paramsEncode($params);
            $buttons['downloadPdf'] = [
                'label' => '<i class="fa kd-download"></i>'.__('Download'),
                'attr' => ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false],
                'url' => $url
            ];
        }
        return $buttons;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'ReportCards');
    }
}
