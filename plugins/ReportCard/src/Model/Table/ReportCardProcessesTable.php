<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;//POCOR-6841
use Cake\I18n\Date;//POCOR-6841
use DateTime;//POCOR-6785

class ReportCardProcessesTable extends ControllerActionTable
{
    const NEW_PROCESS = 1;
    const RUNNING = 2;
    const COMPLETED = 3;
    const ERROR = -1;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('search', false);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'class_name') {
            return __('Class');
        } else if($field == 'student_id') {
            return __('OpenEMIS ID');
          }else if($field=='education_grade_id'){
             return __('Education Grades');

          }
        else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStudentID(Event $event, Entity $entity)
    {
        if (isset($entity->student->openemis_no) && !empty($entity->student->openemis_no)) {
            return $entity->student->openemis_no;
        }
        return ' - ';
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

       //POCOR_7319 starts
        $where=[];
        //Status Filter
        $ReportStatus=$this->getStatusList();
        $reportCardStatusOptions=['-1' => __(' All Status ')] + $ReportStatus;
        $selectedReportStatus = !is_null($this->request->query('status')) ? $this->request->query('status') :-1 ;
        $this->controller->set(compact( 'reportCardStatusOptions','selectedReportStatus'));
        // End
        foreach($reportCardStatusOptions AS $key =>$reportCardSatusOptionsData ){
            $reportStatusKey[$key] = $key;
        }
        if($selectedReportStatus!=-1){
        $where[$this->aliasField('status')] =$selectedReportStatus ;
        }
        if(!empty($reportStatusKey)){
        $where[$this->aliasField('status In')] =$reportStatusKey ;
        }
        //Area Filter
        $Areas = TableRegistry::get('Area.Areas');
        $areaOptions = [];
        $areaOptions = $Areas->find('list')
             ->toArray();
        $areaOptions = ['-1' => __(' All Areas ')] + $areaOptions;
        $selectedArea = !is_null($this->request->query('area_id')) ? $this->request->query('area_id') : -1;
        $this->controller->set(compact('areaOptions', 'selectedArea'));
        //End

        foreach($areaOptions AS $key => $areaOptionsData){
            $areaKey[$key] = $key;
        }

        //Institution Filter
        $Institutions = TableRegistry::get('Institutions');
        $institutionOptions = [];
        if($selectedArea == -1){
            $institutionOptions = $Institutions->find('list')
                                ->where([
                                    $Institutions->aliasField('institution_status_id !=') => 2 //POCOR-6329
                                ])->toArray();
        }else{
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }

            $institutionOptions = $Institutions->find('list')
                                ->where([ $Institutions->aliasField('area_id IN') => $allselectedAreas,
                                    $Institutions->aliasField('institution_status_id !=') => 2 //POCOR-6329
                                ])->toArray();
        }

        if(!empty($institutionOptions)){
            foreach($institutionOptions AS $institutionOptionsDataKey => $institutionOptionsData){
                $institutionOptionsKey[$institutionOptionsDataKey] = $institutionOptionsDataKey;
            }
        }

        $institutionOptions = ['-1' => __('All Institution')] + $institutionOptions;
        $selectedInstitution = !is_null($this->request->query('institution_id')) ? $this->request->query('institution_id') : -1;
        $this->controller->set(compact('institutionOptions', 'selectedInstitution'));


        if($selectedInstitution != -1){
             $where[$this->aliasField('institution_id')] = $selectedInstitution;
        }
        if(!empty($institutionOptionsKey)){
             $where[$this->aliasField('institution_id IN ')] = $institutionOptionsKey;
        }

        //Education grade Filter
         $InstitutionGrades = TableRegistry::get('institution_grades');
         $EducationGrades=TableRegistry::get('education_grades');
         $institutionGradeData = [];
         if($selectedInstitution == -1){
            $institutionGradeData = $InstitutionGrades->find()
                                    ->where([$InstitutionGrades->aliasField('institution_id IN ') => $institutionOptionsKey])->toArray();

         }
         else{
           $institutionGradeData = $InstitutionGrades->find()
                                ->where([ $InstitutionGrades->aliasField('institution_id IN') => $selectedInstitution])
                                ->toArray();
         }
         $institutionGradeOptions=[];
         foreach($institutionGradeData as $record){
               $result= $EducationGrades->find('list')
                                ->where(['id'=>$record['education_grade_id']])
                                ->first();


               $institutionGradeOptions[]=$result;

         }


        $institutionGradeOptions= array_unique($institutionGradeOptions);
        $selectedInstitutionGrade = !is_null($this->request->query('institution_grade')) ? $this->request->query('institution_grade') : $institutionGradeOptions[0] ;

        $selectedInstitutionGrade = !is_null($this->request->query('institution_id'));


        $this->controller->set(compact('institutionGradeOptions', 'selectedInstitutionGrade'));

        if($selectedInstitution != -1){
             $where[$this->aliasField('institution_id')] = $selectedInstitution;
        }
        if(!empty($institutionOptionsKey)){
             $where[$this->aliasField('institution_id IN ')] = $institutionOptionsKey;
        }
        // //End
           $InstitutionClasses = TableRegistry::get('institution_classes');
           $ReportCardProcessesTable = TableRegistry::get('report_card_processes');
           $UsersTable=TableRegistry::get('User.Users');


        //  $query
        //     ->select([

        //         'institution_name' => $Institutions->aliasField('name'),
        //         'education_grade' => $EducationGrades->aliasField('name'),
        //         'class_name'=>$InstitutionClasses->aliasField('name'),
        //         'openemis_id' =>$UsersTable->aliasField('name'),
        //         'status' => $this->ReportCardProcesses->aliasField('status'),
        //         // 'report_card_completed_on' => $this->ClassProfiles->aliasField('completed_on'),
        //     ])
        //     ->innerJoin([$Institutions->alias() => $Institutions->table()],
        //         [
        //             $InstitutionClasses->aliasField('institution_id = ') . $this->aliasField('id'),
        //             $InstitutionClasses->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
        //         ]
        //     )
        //     ->leftJoin([$this->ClassProfiles->alias() => $this->ClassProfiles->table()],
        //         [
        //             $this->ClassProfiles->aliasField('institution_id = ') . $this->aliasField('id'),
                    // $this->ClassProfiles->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
            //         $this->ClassProfiles->aliasField('institution_class_id = ') . $InstitutionClasses->aliasField('id'),
            //         $this->ClassProfiles->aliasField('class_profile_template_id = ') . $selectedReportCard
            //     ]
            // )
            // ->where($where)
            // ->autoFields(true)
            // ->order([
            //     $this->aliasField('name'),
            // ])
            // ->all();

        //POCOR-7319 ends

        // POCOR-7067 Starts
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $timeZone= $ConfigItems->value("time_zone");
        date_default_timezone_set($timeZone);//POCOR-7067 Ends
        //Start:POCOR-6785 need to convert this custom query to cake query
        $ReportCardProcessesTable = TableRegistry::get('report_card_processes');
        $entitydata = $ReportCardProcessesTable->find('all',['conditions'=>[
                'status !=' =>'-1'
        ]])->where([$ReportCardProcessesTable->aliasField('modified IS NOT NULL')])->toArray();

        foreach($entitydata as $keyy =>$entity ){
            //POCOR-7067 Starts
            $now = new DateTime();
            $currentDateTime = $now->format('Y-m-d H:i:s');
            $c_timestap = strtotime($currentDateTime);
            $modifiedDate = $entity->modified;
            //POCOR-6841 starts
            if($entity->status == 2){
                $currentTimeZone = new DateTime();
                $modifiedDate = ($modifiedDate === null) ? $currentTimeZone : $modifiedDate;
                $m_timestap = strtotime($modifiedDate);
                $interval  = abs($c_timestap - $m_timestap);
                $diff_mins   = round($interval / 60);
                if($diff_mins > 5 && $diff_mins < 30){
                    $entity->status = 1;
                    $ReportCardProcessesTable->save($entity);
                }elseif($diff_mins > 30){
                    $entity->status = self::ERROR;
                    $entity->modified = $currentTimeZone;//POCOR-6841
                    $ReportCardProcessesTable->save($entity);
                }
                //POCOR-7067 Ends
            }//POCOR-6841 ends
        }
         $extra['elements']['controls'] = ['name' => 'ReportCard.controls', 'data' => [], 'options' => [], 'order' => 1];
        //  //END:POCOR-6785
         $sortList = ['status', 'Users.openemis_no', 'InstitutionClasses.name', 'Institutions.name','EducationGrades.name'];//POCOR-7319
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Processes','Report Cards');
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
		// End POCOR-5188
    }
    public function onGetStatus(Event $event, Entity $entity)
    {
        $status = [
            '1'  => "New Process",
            '2'  => 'Running',
            '3'  => 'Completed',
            '-1' => 'Error'
        ];
        if (isset($status[$entity->status])) {
            return $status[$entity->status];
        }
        return 'Error';
    }
    //POCOR-7319
    public function getStatusList(){

        $status = [
            '1'  => "New Process",
            '2'  => 'Running',
            '3'  => 'Completed',
            '-1' => 'Error'
        ];
        return $status;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['institution_id']['visible']       = true;
        $this->fields['institution_class_id']['visible'] = true;
        $this->fields['student_id']['visible']           = true;
        $this->fields['status']['visible']               = true;

        $this->fields['report_card_id']['visible']       = false;
        $this->fields['education_grade_id']['visible']   = true;//POCOR 7319
        $this->fields['academic_period_id']['visible']   = false;
        $this->fields['created']['visible']              = false;

        $this->setFieldOrder([
            'institution_id',
            'education_grade_id',//POCOR 7319
            'class_name',
            'openemis_no',
            'status'
        ]);

    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //   print_r($extra);
        //   exit;
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('class_name', ['sort' => ['field' => 'InstitutionClasses.name']]);
        $this->field('institution_id', ['sort' => ['field' => 'Institutions.name']]);
        $this->field('education_grade_id', ['sort' => ['field' => 'EducationGrades.name']]);//POCOR 7319
        $this->field('status', ['sort' => ['field' => 'status']]);
        $this->setupNewTabElements();
    }

    private function setupNewTabElements()
    {
        $tabElements = $this->controller->getReportTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Processes');
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        if ($entity->has('user')) {
            return $entity->user->openemis_no;
        }
        return ' - ';
    }

    public function onGetClassName(Event $event, Entity $entity)
    {
        if ($entity->has('institution_class')) {
            return $entity->institution_class->name;
        }
        return ' - ';
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        # Update the status of student process
        $StudentsReportCards->query()->update()
            ->set([
                'status' => self::NEW_PROCESS,
                'started_on' => null,
                'completed_on' => null
            ])
            ->where([
                'report_card_id' => $entity->report_card_id,
                'student_id' => $entity->student_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'education_grade_id' => $entity->education_grade_id,
                'institution_class_id' => $entity->institution_class_id
            ])->execute();
    }
}
