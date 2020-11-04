<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Console\Shell;

class GenerateStudentUnmarkedAttendancesShell extends Shell
{
	
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Cases.InstitutionCases');
        $this->loadModel('Cases.InstitutionCaseRecords');
        $this->loadModel('Institution.ClassAttendanceRecords');
		$this->loadModel('Institution.InstitutionClasses');
		$this->loadModel('Institution.Institutions');
		$this->loadModel('AcademicPeriod.AcademicPeriods');
		$this->loadModel('Workflow.WorkflowRules');
		
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');

		$this->loadModel('Alert.AlertLogs');
    }

    public function main()
    {  
		$academicPeriodId = $this->AcademicPeriods->getCurrent();
		
		$workflowRules = $this->WorkflowRules->find()->where(['feature' => 'StudentUnmarkedAttendances'])->hydrate(false)->toArray();
					
		$getWorkingDaysOfWeek = $this->AcademicPeriods->getWorkingDaysOfWeek();
		$month = date('n');
		$day = date('d');
		
		foreach($workflowRules as $workflowRule){
			$rule = json_decode($workflowRule['rule'], true);	

			$daysUnmarked = $rule['where']['days_unmarked'];
			$conditions['ClassAttendanceRecords.academic_period_id'] = $academicPeriodId;
			$conditions['ClassAttendanceRecords.month'] = $month;
			
			if($day > $daysUnmarked){
				for($i = 1; $i<= $daysUnmarked; $i++){
					$num = $day - $i;
					$conditions['day_'.$num] = 0; 
				}
				
				$classAttendanceRecords =  $this->ClassAttendanceRecords->find()
					->contain(['InstitutionClasses' => ['Institutions']])
					->where($conditions)
					->hydrate(false)
					->toArray();
				
				foreach($classAttendanceRecords as $classAttendanceRecord){
					
					$title = $classAttendanceRecord['institution_class']['name'] . ' ' . $classAttendanceRecord['institution_class']['institution']['code'] . ' - ' . $classAttendanceRecord['institution_class']['institution']['name'] . ' with ' . $daysUnmarked . ' day Student Unmarked Attendances';
					
					$institutionId = $classAttendanceRecord['institution_class']['institution']['id'];
					
					$recordId = $classAttendanceRecord['institution_class']['id'];
					$feature = $workflowRule['feature'];					
					$statusId = 59;
					$assigneeId = 0;
					$institutionId = $institutionId;
					$workflowRuleId = $workflowRule['id'];
					$linkedRecords = [
						'record_id' => $recordId,
						'feature' => $feature
					];
					
					$caseData = [
						'case_number' => '',
						'title' => $title,
						'status_id' => $statusId,
						'assignee_id' => $assigneeId,
						'institution_id' => $institutionId,
						'workflow_rule_id' => $workflowRuleId, // required by workflow behavior to get the correct workflow
						'linked_records' => $linkedRecords
					];
					
					$patchOptions = ['validate' => false];
					
					$newEntity = $this->InstitutionCases->newEntity();
					$newEntity = $this->InstitutionCases->patchEntity($newEntity, $caseData, $patchOptions);
					$result = $this->InstitutionCases->save($newEntity);
					
					$linkedRecords['institution_case_id'] = $result->id;
					$newEntityInstitutionCaseRecord = $this->InstitutionCaseRecords->newEntity();
					$newEntityInstitutionCaseRecord = $this->InstitutionCaseRecords->patchEntity($newEntityInstitutionCaseRecord, $linkedRecords, $patchOptions);
					$this->InstitutionCaseRecords->save($newEntityInstitutionCaseRecord);
					$this->sendEmail($rule['where']['security_role_id'], $institutionId, $daysUnmarked);
					echo "saved";
				}
			}
		}
    }
	
	
	public function sendEmail($securityRoleId, $institutionId, $daysUnmarked){
		
		if (!empty($securityRoleId) && !empty($institutionId)) { //check if the alertRule have security role and institution id
			$emailList = $this->getEmailList($securityRoleId, $institutionId);
			
			$email = !empty($emailList) ? implode(', ', $emailList) : ' ';

			// subject and message for alert email
			$feature = 'StudentUnmarkedAttendances';
			$subject = '[Class Unmarked Student Attendance] (To Do) System Administrator';
			
			$defaultMessage = __('Your action is required for [Class Unmarked Student Attendance].');
			$defaultMessage .= "\n"; // line break
			$defaultMessage .= "\n" . __('Status')      . ': ' . "\t \t"    . 'To Do' ;
			$defaultMessage .= "\n" . __('Sent By')     . ': ' . "\t \t"    . 'System Administrator' ;
			$defaultMessage .= "\n" . __('Title')    . ': ' . "\t"    . $daysUnmarked.' days unmarked attendance' ;

			// insert record to  the alertLog
			$this->AlertLogs->insertAlertLog('Email', $feature, $email, $subject, $defaultMessage);
		}
	}
	
	public function getEmailList($securityRoleRecords, $institutionId = null)
    {
        $emailList = [];

        //foreach ($securityRoleRecords as $securityRolesObj) {
            $options = [
                'securityRoleId' => $securityRoleRecords
            ];

            if (!is_null($institutionId)) {
                $options['institutionId'] = $institutionId;
            }

            // all staff within securityRole and institution
            $emailListResult = $this->SecurityGroupUsers
                ->find('emailList', $options)
                ->toArray()
            ;
			
            // combine all email to the email list
            if (!empty($emailListResult)) {
                foreach ($emailListResult as $obj) {
                    if (!empty($obj->_matchingData['Users']->email)) {
                        $recipient = $obj->_matchingData['Users']->name . ' <' . $obj->_matchingData['Users']->email . '>';
                        if (!in_array($recipient, $emailList)) {
                            $emailList[] = $recipient;
                        }
                    }
                }
            }
        //}

        return $emailList;
    }
}
