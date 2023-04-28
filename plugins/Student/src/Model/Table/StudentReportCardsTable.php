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
    //POCOR-7321 start
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.viewPDF'] = 'viewPDF';//POCOR-7321
        return $events;
    }
     //POCOR-7321 end
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

        // Start POCOR-5188
		if($this->request->params['controller'] == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Report Cards (PDF)','Students - Academic');       
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];
				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}
		}elseif($this->request->params['controller'] == 'Directories'){ 
			$is_manual_exist = $this->getManualUrl('Directory','Report Cards (PDF)','Students - Academic');       
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}

		}
		// End POCOR-5188
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {   
        $user = $this->Auth->user();
       
        $InstitutionStudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $StudentGuardians = TableRegistry::get('student_guardians');

        //Start POCOR-7055
        if ($user['is_student'] == 1 && $user['is_guardian'] == 1 && $user['is_staff'] == 1) {
            if ($this->controller->name == 'Profiles') {
                $query
                ->contain('AcademicPeriods', 'Institutions', 'EducationGrades')            
                ->where([$this->aliasField('status') => $InstitutionStudentsReportCards::PUBLISHED,
                    $this->aliasField('student_id') => $user['id'] 
                ])
                ->order(['AcademicPeriods.order', 'Institutions.name', 'EducationGrades.order']);
            }
            $session = $this->request->session();
            $session = $this->request->session();//POCOR-6267
            $student_id = $session->read('Student.Students.id');

            $query
            ->contain('AcademicPeriods', 'Institutions', 'EducationGrades')            
            ->where([$this->aliasField('status') => $InstitutionStudentsReportCards::PUBLISHED,
                $this->aliasField('student_id') => $student_id 
            ])
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
        //POCOR 7321 start    
        $params = [
            'report_card_id' => $entity->report_card_id,
            'student_id' => $entity->student_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id
        ];
        
        $viewPdfUrl = $this->url('viewPDF');
        $viewPdfUrl[1] = $this->paramsEncode($params);
        $buttons['viewPdf'] = [
                'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
                'attr' =>[ 'role' => 'menuitem', 'tabindex' => '-1', 'escape' => false,'target'=>'_blank'],
                'url' => $viewPdfUrl
        ];
        //POCOR-7321 end
        $downloadAccess = false;
        if ($this->controller->name == 'Students') {
            $downloadAccess = $this->AccessControl->check(['Students', 'ReportCards', 'download']);
            $downloadExcel = $this->AccessControl->check(['Students', 'ReportCard', 'download']);
        } else if ($this->controller->name == 'Directories') {
            $downloadAccess = $this->AccessControl->check(['Directories', 'StudentReportCards', 'download']);
            $downloadExcel = $this->AccessControl->check(['Directories', 'StudentReportCard', 'download']);
        } else if ($this->controller->name == 'Profiles') {
            $downloadAccess = $this->AccessControl->check(['Profiles', 'StudentReportCards', 'download']);
            $downloadExcel = $this->AccessControl->check(['Profiles', 'StudentReportCard', 'download']);
            // unset($buttons['view']);
        }
        /**POCOR-6845 starts - Added condition to get download button when logged in as Guardian*/  
        else if ($this->controller->name == 'GuardianNavs') {
            $downloadAccess = $this->AccessControl->check(['GuardianNavs', 'StudentReportCards', 'download']);
            $downloadExcel = $this->AccessControl->check(['GuardianNavs', 'StudentReportCard', 'download']);
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
                'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                'attr' => ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false],
                'url' => $url
            ];
        }

        if ($downloadExcel) {
            $params = [
                'report_card_id' => $entity->report_card_id,
                'student_id' => $entity->student_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'education_grade_id' => $entity->education_grade_id
            ];

            $url = $this->url('download');
            $url[1] = $this->paramsEncode($params);

            $buttons['download'] = [
                'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
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
    //POCOR-7321 start
    public function viewPDF(Event $event, ArrayObject $extra){
        $model = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $ids = $this->paramsDecode($this->paramsPass(0));

        if ($model->exists($ids)) {
            $data = $model->get($ids);
            $fileName = $data->file_name;
			$fileNameData = explode(".",$fileName);
			$fileName = $fileNameData[0].'.pdf';
			$pathInfo['extension'] = 'pdf';
            $file = $this->getFile($data->file_content_pdf);
            $fileType = 'application/pdf';
            
            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            // header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: inline; filename="' . $fileName . '"');

            echo $file;
            
        }
        exit();
   
    }
    private function getFile($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
    //POCOR-7321 ends
}