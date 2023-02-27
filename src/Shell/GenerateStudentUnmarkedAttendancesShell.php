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
		$year = date('Y'); //6023 task
		$month = date('n');
		$day = date('d');
		
		foreach($workflowRules as $workflowRule){
			$rule = json_decode($workflowRule['rule'], true);	
			//6023 starts get workflow name
			$Workflows = TableRegistry::get('Workflows');
			$workflows = $Workflows->find()->where(['id' => $workflowRule['workflow_id']])->first();
			//6023 ends
			$daysUnmarked = $rule['where']['days_unmarked'];
			$securityRoleId = $rule['where']['security_role_id']; //POCOR-6363
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
				
				$mailed_data = []; //6023 task
				foreach($classAttendanceRecords as $classAttendanceRecord){
					//6023 starts 
					$studentAttendanceMarkedRecords = TableRegistry::get('student_attendance_marked_records');
					$studentRecords = $studentAttendanceMarkedRecords
									->find()
									->where([
										'institution_id' => $classAttendanceRecord['institution_class']['institution']['id'], 
										'academic_period_id' => $academicPeriodId,
										'institution_class_id' => $classAttendanceRecord['institution_class']['id']
									])->all();
					if(!empty($studentRecords)){
						$getDay =[];
						foreach ($studentRecords as $studentRecord) {
							//get dates of current month
							if(date('m') == date('m', strtotime($studentRecord['date']))){
								$getDay[] = date('d', strtotime($studentRecord['date']));
							}
						}
						//get unmarked dates for month
						$unmarked_dates_arr= [];
						for($j = 1; $j<= $daysUnmarked; $j++){
							$working_day = $day - $j;
							if(!in_array($working_day, $getDay)){
								$unmarked_dates_arr[] = $working_day.'-'.date('m').'-'.$year;
							}
						}
						$get_unmarked_dates = implode(',',$unmarked_dates_arr);
						//count unmarked dates for month
						$number_of_unmarked_day_in_month = $daysUnmarked;
					}
					
					$mailed_data = [
						'class_name' => $classAttendanceRecord['institution_class']['name'],
						'institution_name' => $classAttendanceRecord['institution_class']['institution']['name'],
						'workflow_name' => $workflows['name'],
						'date_of_unmarked_attendances' => $get_unmarked_dates,
						'number_of_days_that_are_unmarked' => $number_of_unmarked_day_in_month
					];
					unset($getDay);
					unset($unmarked_dates_arr);
					//6023 ends
					$title = $classAttendanceRecord['institution_class']['name'] . ' ' . $classAttendanceRecord['institution_class']['institution']['code'] . ' - ' . $classAttendanceRecord['institution_class']['institution']['name'] . ' with ' . $daysUnmarked . ' day Student Unmarked Attendances';
					//POCOR-6363:: START
					$institutionId = $classAttendanceRecord['institution_class']['institution']['id'];
					$INSTITUTIONS = TableRegistry::get('institutions');	
					$INSTITITUTEDATA = $INSTITUTIONS->find('all',['conditions'=>['id'=>$institutionId]])->first();	
					$securityGroupUsers = TableRegistry::get('security_group_users');	
					$dataForAssigneeID = $securityGroupUsers->find('all',['conditions'=>['security_group_id'=>$INSTITITUTEDATA->security_group_id,'security_role_id'=>$securityRoleId]])->first();	
					if(!empty($dataForAssigneeID)){	
						$assigneeId = $dataForAssigneeID->security_user_id;	
					}else{	
						$assigneeId = 0;	
					}
					//POCOR-6363:: END
					$recordId = $classAttendanceRecord['institution_class']['id'];
					$feature = $workflowRule['feature'];					
					$statusId = 59;
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
					$this->sendEmail($rule['where']['security_role_id'], $institutionId, $daysUnmarked,$mailed_data);//6023 add param $mailed_data  
					echo "saved";
				}
			}
		}
    }
	
	
	public function sendEmail($securityRoleId, $institutionId, $daysUnmarked, $mailed_data=array()){
		
		if (!empty($securityRoleId) && !empty($institutionId)) { //check if the alertRule have security role and institution id
			$emailList = $this->getEmailList($securityRoleId, $institutionId);
			$email = !empty($emailList) ? implode(', ', $emailList) : ' ';
			// subject and message for alert email
			$feature = 'StudentUnmarkedAttendances';
			$subject = '[Class Unmarked Student Attendance] (To Do) System Administrator';
			
			$defaultMessage = __('Your action is required for [Class Unmarked Student Attendance].');
			$defaultMessage .= "\n"; // line break
			//6023 starts 
			$defaultMessage .= "\n" . __('Status')      . ': ' . "\t \t"    . 'To Do' ;
			$defaultMessage .= "\n" . __('Sent By')     . ': ' . "\t \t"    . 'System Administrator' ;
			$defaultMessage .= "\n" . __('Title')    . ': ' . "\t"    . $daysUnmarked.' days unmarked attendance' ;
			$defaultMessage .= "\n" . __('Workflow Name')    . ': ' . "\t"    . $mailed_data['workflow_name'];
			$defaultMessage .= "\n" . __('Institution Name')    . ': ' . "\t"    . $mailed_data['institution_name'];
			$defaultMessage .= "\n" . __('Class Name')    . ': ' . "\t"    . $mailed_data['class_name'];
			$defaultMessage .= "\n" . __('Date of Unmarked Attendances')    . ': ' . "\t"    . $mailed_data['date_of_unmarked_attendances'];
			$defaultMessage .= "\n" . __('Number of days that are unmarked')    . ': ' . "\t"    . $mailed_data['number_of_days_that_are_unmarked'];
			//6023 ends 
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
                ->toArray();
			
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
