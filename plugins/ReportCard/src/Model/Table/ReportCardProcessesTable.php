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
        }else {
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
        //POCOR-7067 Starts
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
         //END:POCOR-6785
        $sortList = ['status', 'Users.openemis_no', 'InstitutionClasses.name', 'Institutions.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['institution_id']['visible']       = true;
        $this->fields['institution_class_id']['visible'] = true;
        $this->fields['student_id']['visible']           = true;
        $this->fields['status']['visible']               = true;

        $this->fields['report_card_id']['visible']       = false;
        $this->fields['education_grade_id']['visible']   = false;
        $this->fields['academic_period_id']['visible']   = false;
        $this->fields['created']['visible']              = false;

        $this->setFieldOrder([
            'institution_id',
            'class_name',
            'openemis_no',
            'status'
        ]);
        
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('class_name', ['sort' => ['field' => 'InstitutionClasses.name']]);
        $this->field('institution_id', ['sort' => ['field' => 'Institutions.name']]);
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
