<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;
use App\Model\Traits\OptionsTrait;


//POCOR-7211
class AgeOutliersTable extends AppTable  {
    use OptionsTrait;

    public function initialize(array $config) {
        $this->table('institution_students');
        parent::initialize($config);
        
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event) {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $query
        ->contain(['Institutions'])
        ->group([$this->aliasField('institution_id'), $this->aliasField('Institutions.name')])->first();
        $query
        ->where([
        $this->aliasField('academic_period_id') => $academicPeriodId
        ]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($academicPeriodId) {
            return $results->map(function ($row) use($academicPeriodId){
                $academicPeriodId = $academicPeriodId;
                
                $institutionStudents = TableRegistry::get('institution_students');
                $academicPeriod = TableRegistry::get('academic_periods');
                $institutions = TableRegistry::get('institutions');
                $connection = ConnectionManager::get('default');
                $statement = $connection->prepare("SELECT main_query.academic_period_name AS 'academic_period_name'
                                ,main_query.institution_code AS 'institution_code'
                                ,main_query.institution_name AS 'institution_name'
                                ,main_query.education_grade_name AS 'education_Grade'
                                ,main_query.openemis_no AS 'student_OpenEMIS_No.'
                                ,main_query.student_name AS 'student_Name'
                                ,main_query.gender_name AS 'student_Gender'
                                ,main_query.student_age AS 'student_Age'
                            FROM
                            (
                                SELECT academic_periods.name academic_period_name   
                                    ,institutions.code institution_code         
                                    ,institutions.name institution_name     
                                    ,education_grades.name education_grade_name 
                                    ,security_users.openemis_no
                                    ,CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name) student_name
                                    ,genders.name gender_name 
                                    ,FLOOR(DATEDIFF(security_users.date_of_birth,academic_periods.start_date) / 365.25 * -1) student_age
                                FROM institution_students
                                INNER JOIN academic_periods
                                ON academic_periods.id = institution_students.academic_period_id
                                INNER JOIN institutions
                                ON institutions.id = institution_students.institution_id
                                INNER JOIN education_grades
                                ON education_grades.id = institution_students.education_grade_id
                                INNER JOIN security_users
                                ON security_users.id = institution_students.student_id
                                INNER JOIN genders
                                ON genders.id = security_users.gender_id
                                WHERE academic_periods.id = $academicPeriodId
                                AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                                GROUP BY institution_students.student_id
                            ) main_query
                            CROSS JOIN
                            (
                                SELECT @min_age := MAX(CASE WHEN config_items.code = 'report_outlier_min_age' THEN config_items.value ELSE 0 END) min_age
                                    ,@max_age := MAX(CASE WHEN config_items.code = 'report_outlier_max_age' THEN config_items.value ELSE 0 END) max_age
                                    ,@min_enrolment := MAX(CASE WHEN config_items.code = 'report_outlier_min_student' THEN config_items.value ELSE 0 END) min_enrolment
                                    ,@max_enrolment := MAX(CASE WHEN config_items.code = 'report_outlier_max_student' THEN config_items.value ELSE 0 END) max_enrolment
                                FROM config_items
                                WHERE config_items.code IN ('report_outlier_min_age', 'report_outlier_max_age', 'report_outlier_min_student', 'report_outlier_max_student')
                            ) subq
                            WHERE main_query.student_age NOT BETWEEN subq.min_age AND subq.max_age");
            $statement->execute();
            $list =  $statement->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($list as  $value) {
                $row['academic_period_name'] = $value['academic_period_name'];
                $row['institution_code'] = $value['institution_code'];
                $row['institution_name'] = $value['institution_name'];
                $row['education_Grade'] = $value['education_Grade'];
                $row['student_OpenEMIS_No'] = $value['student_OpenEMIS_No'];
                $row['student_Name'] = $value['student_Name'];
                $row['student_Gender'] = $value['student_Gender'];
                $row['student_Age'] = $value['student_Age'];
            }
            return $row;
            });
            
        }); 
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraFields = [];
        $extraFields[] = [
            'key' => 'academic_period_name',
            'field' => 'academic_period_name',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];
        $extraFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution code')
        ];
        $extraFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $extraFields[] = [
            'key' => 'education_Grade',
            'field' => 'education_Grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
        $extraFields[] = [
            'key' => 'student_OpenEMIS_No',
            'field' => 'student_OpenEMIS_No',
            'type' => 'integer',
            'label' => __('student OpenEMIS No')
        ];
        $extraFields[] = [
            'key' => 'student_Name',
            'field' => 'student_Name',
            'type' => 'string',
            'label' => __('Student Name')
        ];
        $extraFields[] = [
            'key' => 'student_Gender',
            'field' => 'student_Gender',
            'type' => 'string',
            'label' => __('Student Gender')
        ];

        $extraFields[] = [
            'key' => 'student_Age',
            'field' => 'student_Age',
            'type' => 'string',
            'label' => __('Student Age')
        ];
        

        $fields->exchangeArray($extraFields);
    }


}
