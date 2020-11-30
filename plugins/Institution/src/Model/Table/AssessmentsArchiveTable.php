<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

use App\Model\Table\ControllerActionTable;
class AssessmentsArchiveTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config)
    {
        $connectionone = ConnectionManager::get('default');
        $connectiontwo = ConnectionManager::get('prd_cor_arc');
        $db1 =  $connectionone->config()['database'];

        $getArchiveConnection = $connectionone->query("SELECT * FROM transfer_connections");
        $archiveConnection = $getArchiveConnection->fetchAll();
        foreach($archiveConnection AS $archiveConnectionData){
            $db2 = $archiveConnectionData[5];
        }

        $getArchiveData = $connectionone->query("SELECT 
        academic_periods.name AS 'Academic Periods',
        CONCAT(assessments.code,' ',assessments.name) AS 'Assessments',
        assessment_periods.name AS 'Assessment periods',
        education_grades.name AS 'Education Grade',
        institution_classes.name AS 'Institutions Class',
        education_subjects.name AS 'Subject',
        security_users.openemis_no AS 'OpenEMIS ID',
        CONCAT(security_users.first_name,' ',security_users.last_name) AS 'Student Name',
        IF(assessment_item_results.marks IS NULL,'-',assessment_item_results.marks) AS 'Mark',
        CONCAT(assessment_grading_options.code,'-',assessment_grading_options.name) AS 'Grade'
        FROM $db1.assessment_item_results
        INNER JOIN assessment_grading_options
        ON assessment_item_results.assessment_grading_option_id = assessment_grading_options.id
        INNER JOIN security_users
        ON assessment_item_results.student_id = security_users.id
        INNER JOIN academic_periods
        ON academic_periods.id = assessment_item_results.academic_period_id
        INNER JOIN assessments
        ON assessments.id = assessment_item_results.assessment_id
        INNER JOIN assessment_periods
        ON assessment_periods.assessment_id = assessments.id
        AND assessment_periods.id = assessment_item_results.assessment_period_id 
        INNER JOIN assessment_items
        ON assessment_items.assessment_id = assessments.id
        INNER JOIN education_subjects
        ON education_subjects.id = assessment_items.education_subject_id
        AND education_subjects.id = assessment_item_results.education_subject_id
        INNER JOIN education_grades
        ON education_grades.id = assessments.education_grade_id
        AND education_grades.id = assessment_item_results.education_grade_id
        INNER JOIN institution_class_students
        ON institution_class_students.student_id = security_users.id
        AND institution_class_students.academic_period_id = assessment_item_results.academic_period_id
        INNER JOIN institution_classes
        ON institution_classes.id = institution_class_students.institution_class_id
        AND institution_classes.academic_period_id = assessment_item_results.academic_period_id 
        WHERE academic_periods.current = 1;
        ");
        $archiveDataArr = $getArchiveData->fetchAll();
        foreach($archiveDataArr AS $archiveDataval)
        {
            $archiveDataKeyValAssociation[] = array("academic_period"=>$archiveDataval[0], "assessment"=>$archiveDataval[1],"education_grade"=>$archiveDataval[2], "class"=> $archiveDataval[3],"subject"=>$archiveDataval[5],"open_emis_id"=>$archiveDataval[6],"name"=>$archiveDataval[7],"mark"=>$archiveDataval[8]);
            
        }
        echo json_encode($archiveDataKeyValAssociation); exit;
        
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentArchive' => ['index', 'view']
        ]);

        // $this->attachAngularModules();
    }

    // private function attachAngularModules()
    // {
    //     $action = $this->request->action;
    //     switch ($action) {
    //         case 'AssessmentsArchive':
    //             $this->Angular->addModules([
    //                 'institution.assessments.archive.ctrl',
    //                 'institution.assessments.archive.svc'
    //             ]);
    //             break;
    //     }
    // }

    // To select another one more field from the containable data

    
}
