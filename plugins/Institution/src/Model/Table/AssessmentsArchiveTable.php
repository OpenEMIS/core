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
use Cake\Utility\Security;

use App\Model\Table\ControllerActionTable;
class AssessmentsArchiveTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config)
    {
        $connectionone = ConnectionManager::get('default');
        $db1 = $connectionone->config()['database'];
        $transferConnections = TableRegistry::get('TransferConnections.TransferConnections');
        $transferConnectionsData = $transferConnections->find('all')
            ->select([
                'TransferConnections.host','TransferConnections.db_name','TransferConnections.host','TransferConnections.username','TransferConnections.password','TransferConnections.db_name'
            ])
            ->first();
        if ( base64_encode(base64_decode($transferConnectionsData['password'], true)) === $transferConnectionsData['password']){
        $db_password = $this->decrypt($transferConnectionsData['password'], Security::salt());
        }
        else {
        $db_password = $dbConnection['db_password'];
        }
        $connectiontwo = ConnectionManager::config($transferConnectionsData['db_name'], [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => $transferConnectionsData['host'],
            'username' => $transferConnectionsData['username'],
            'password' => $db_password,
            'database' => $transferConnectionsData['db_name'],
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
        ]);
        $checkconnection = ConnectionManager::get($transferConnectionsData['db_name']);
        $collection = $checkconnection->schemaCollection();
        $tableSchema = $collection->listTables();
        $table1 = $tableSchema[0];

        $stmt1 = $connectionone->prepare("CREATE OR REPLACE VIEW assessment_item_results_archived AS SELECT * FROM $table1");
        $stmt1->execute();

        $getArchiveData = $connectionone->query("SELECT 
        academic_periods.name AS 'Academic Periods',
        CONCAT(assessments.code,' ',assessments.name) AS 'Assessments',
        assessment_periods.name AS 'Assessment periods',
        education_grades.name AS 'Education Grade',
        institution_classes.name AS 'Institutions Class',
        education_subjects.name AS 'Subject',
        security_users.openemis_no AS 'OpenEMIS ID',
        CONCAT(security_users.first_name,' ',security_users.last_name) AS 'Student Name',
        IF(assessment_item_results_archived.marks IS NULL,'-',assessment_item_results_archived.marks) AS 'Mark',
        CONCAT(assessment_grading_options.code,'-',assessment_grading_options.name) AS 'Grade'
        FROM $db1.assessment_item_results_archived
        INNER JOIN assessment_grading_options
        ON assessment_item_results_archived.assessment_grading_option_id = assessment_grading_options.id
        INNER JOIN security_users
        ON assessment_item_results_archived.student_id = security_users.id
        INNER JOIN academic_periods
        ON academic_periods.id = assessment_item_results_archived.academic_period_id
        INNER JOIN assessments
        ON assessments.id = assessment_item_results_archived.assessment_id
        INNER JOIN assessment_periods
        ON assessment_periods.assessment_id = assessments.id
        AND assessment_periods.id = assessment_item_results_archived.assessment_period_id 
        INNER JOIN assessment_items
        ON assessment_items.assessment_id = assessments.id
        INNER JOIN education_subjects
        ON education_subjects.id = assessment_items.education_subject_id
        AND education_subjects.id = assessment_item_results_archived.education_subject_id
        INNER JOIN education_grades
        ON education_grades.id = assessments.education_grade_id
        AND education_grades.id = assessment_item_results_archived.education_grade_id
        INNER JOIN institution_class_students
        ON institution_class_students.student_id = security_users.id
        AND institution_class_students.academic_period_id = assessment_item_results_archived.academic_period_id
        INNER JOIN institution_classes
        ON institution_classes.id = institution_class_students.institution_class_id
        AND institution_classes.academic_period_id = assessment_item_results_archived.academic_period_id
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

    public function decrypt($encrypted_string, $secretHash) {

        $iv = substr($secretHash, 0, 16);
        $data = base64_decode($encrypted_string);
        $decryptedMessage = openssl_decrypt($data, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $decrypted = rtrim(
            $decryptedMessage
        );
        return $decrypted;
    }

    
}
