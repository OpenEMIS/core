<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\I18n\Date;

class StudentWithdrawalReportTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_withdraw');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
   
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',         'foreignKey' => 'institution_id']);
       
       $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

     public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
       

        $requestData = json_decode($settings['process']['params']);
        $academic_period_id = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $Users = TableRegistry::get('User.Users');
        $where = [];
        if ( $institution_id > 0) {
            $where = [$this->aliasField('institution_id = ') => $institution_id];
        } else {
            $where = [];
        }
        // echo "<pre>"; print_r($where);
        // die('asdfrahul');

        $query
            ->select(['*'
                // 'openemis_no' => 'Users.openemis_no',
                // 'student_first_name' => 'Users.first_name',
                // 'student_middle_name' => 'Users.middle_name',
                // 'student_third_name' => 'Users.third_name',
                // 'student_last_name' => 'Users.last_name',
                // 'education_grade' => $EducationGrades->aliasField('name'),
            ])
            // ->leftJoin(
            //     [$Users->alias() => $Users->table()],
            //         [
            //             $Users->aliasField('id = ') . $this->aliasField('student_id')
            //         ]
            // ) 
            // ->leftJoin(
            //         [$EducationGrades->alias() => $EducationGrades->table()],
            //         [
            //             $EducationGrades->aliasField('id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
            //         ]
            //     )
            ->where([
                //$this->aliasField('date >= ') => $startDate,
               //// $this->aliasField('date <= ') => $endDate,
                $where
            ]);
            
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        // echo "<pre>"; print_r($fields); die();
       $newArray = [];
      
        $newArray[] = [
            'key' => 'StudentWithdrawalReport.institution_id',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => __('Institution')
        ];
        
        

        $fields->exchangeArray($newArray);
       
    }
}
