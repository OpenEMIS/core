<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Console\Shell;

class EmailAllStudentReportCardsShell extends Shell
{
	CONST EMAIL_TEMPLATE = 2;
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('SystemProcesses');
        $this->loadModel('ReportCard.StudentReportCardEmailProcesses');
        $this->loadModel('Institution.InstitutionStudentsProfileTemplates');
        $this->loadModel('Email.EmailProcesses');
        $this->loadModel('Email.EmailProcessAttachments');
        $this->loadModel('Email.EmailTemplates');
    }

    public function main()
    {
        if (!empty($this->args[0])) {
            $pid = getmypid();
            $systemProcessId = !empty($this->args[0]) ? $this->args[0] : 0;
            $this->SystemProcesses->updatePid($systemProcessId, $pid);

            $this->out('Initialize Email All Student Report Cards ('.Time::now().')');

            $exit = false;
            while (!$exit) {
                $recordToProcess = $this->StudentReportCardEmailProcesses->find()
                    ->select([
                        $this->StudentReportCardEmailProcesses->aliasField('student_profile_template_id'),
                        $this->StudentReportCardEmailProcesses->aliasField('student_id'),
                        $this->StudentReportCardEmailProcesses->aliasField('institution_id'),
                        $this->StudentReportCardEmailProcesses->aliasField('education_grade_id'),
                        $this->StudentReportCardEmailProcesses->aliasField('academic_period_id')
                    ])
                    ->where([
                        $this->StudentReportCardEmailProcesses->aliasField('status') => $this->StudentReportCardEmailProcesses::SENDING
                    ])
                    ->order([
                        $this->StudentReportCardEmailProcesses->aliasField('created'),
                        $this->StudentReportCardEmailProcesses->aliasField('student_id')
                    ])
                    ->hydrate(false)
                    ->first();
				
                if (!empty($recordToProcess)) {
                    $this->out('Sending report card for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');

                    $studentsReportCardEntity = $this->InstitutionStudentsProfileTemplates
                        ->find()
                        ->select([
                            $this->InstitutionStudentsProfileTemplates->aliasField('file_name'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('file_content'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('file_content_pdf'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('student_id'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('institution_id'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('education_grade_id'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id'),
                            $this->InstitutionStudentsProfileTemplates->aliasField('academic_period_id'),
                        ])
                        ->contain([
                            'Students' => [
                                'fields' => [
                                    'openemis_no',
                                    'first_name',
                                    'middle_name',
                                    'third_name',
                                    'last_name',
                                    'preferred_name',
                                    'address',
                                    'postal_code',
                                    'date_of_birth',
                                    'identity_number',
                                    'email'
                                ]
                            ],
                            'StudentTemplates' => [
                                'fields' => [
                                    'code',
                                    'name'
                                ]
                            ],
                            'AcademicPeriods' => [
                                'fields' => [
                                    'code',
                                    'name'
                                ]
                            ]
                        ])
                        ->where([
                            $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id') => $recordToProcess['student_profile_template_id'],
                            $this->InstitutionStudentsProfileTemplates->aliasField('student_id') => $recordToProcess['student_id'],
                            $this->InstitutionStudentsProfileTemplates->aliasField('education_grade_id') => $recordToProcess['education_grade_id'],
                            $this->InstitutionStudentsProfileTemplates->aliasField('institution_id') => $recordToProcess['institution_id'],
                            $this->InstitutionStudentsProfileTemplates->aliasField('academic_period_id') => $recordToProcess['academic_period_id'],
                            $this->InstitutionStudentsProfileTemplates->aliasField('status') => $this->InstitutionStudentsProfileTemplates::PUBLISHED
                        ])
                        ->first();
					
                    if (!empty($studentsReportCardEntity)) {
                        $emailProcessesObj = new ArrayObject([
                            'recipients' => '',
                            'subject' => '',
                            'message' => '',
                            'email_process_attachments' => []
                        ]);

                        $this->setRecipients($studentsReportCardEntity, $emailProcessesObj);
                        $this->setSubject($studentsReportCardEntity, $emailProcessesObj);
                        $this->setMessage($studentsReportCardEntity, $emailProcessesObj);
                        $this->setAttachments($studentsReportCardEntity, $emailProcessesObj);

                        $emailProcessesData = $emailProcessesObj->getArrayCopy();
						// default email status is error
                        $emailStatus = $this->StudentReportCardEmailProcesses::ERROR;
                        $errorMsg = NULL;
                        if (empty($emailProcessesData['recipients'])) {
                            $errorMsg = 'Email address is not configured';

                            $this->out($errorMsg);
                        } else {
                            if (!empty($emailProcessesData['subject']) && !empty($emailProcessesData['message'])) {
                                $emailProcessesEntity = $this->EmailProcesses->newEntity($emailProcessesData);

                                if ($this->EmailProcesses->save($emailProcessesEntity)) {
                                    $emailProcessesId = $emailProcessesEntity->id;

                                    try {
                                        $result = $this->EmailProcesses->sendEmail($emailProcessesId);
                                        if ($result) {
                                            $emailStatus = $this->StudentReportCardEmailProcesses::SENT;
                                        } else {
                                            $errorMsg = "Failed to sent email.";
                                        }
                                    } catch (\Exception $e) {
                                        $errorMsg = $e->getMessage();

                                        $this->out('Error sending Report Card for Student ' . $recordToProcess['student_id']);
                                        $this->out($errorMsg);
                                    }
                                } else {
                                    $this->out('Student Report Cards email process is not saved');
                                    $this->out($emailProcessesEntity->errors());
                                }
                            }
                        }
                        $this->StudentReportCardEmailProcesses->updateAll([
                            'status' => $emailStatus,
                            'error_message' => $errorMsg
                        ], [
                            'student_profile_template_id' => $recordToProcess['student_profile_template_id'],
                            'institution_id' => $recordToProcess['institution_id'],
                            'academic_period_id' => $recordToProcess['academic_period_id'],
                            'student_id' => $recordToProcess['student_id']
                        ]);
                    } else {
                        $this->out('Student Report Cards not found');
                    }

                    $this->out('End sending report card for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
                } else {
                    $exit = true;
                    $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
                }
            }   // end while
            $this->out('End Email All Report Cards ('.Time::now().')');
        }
    }

    private function setRecipients(Entity $studentsReportCardEntity, ArrayObject $emailProcessesObj)
    {
        $recipientsArray = [];
        if ($studentsReportCardEntity->has('student') && $studentsReportCardEntity->student->has('email')) {
            $recipientsArray[] = $studentsReportCardEntity->student->email;
        }

        if (!empty($recipientsArray)) {
            $emailProcessesObj['recipients'] = implode(",", $recipientsArray);
        }
    }

    private function setSubject(Entity $studentsReportCardEntity, ArrayObject $emailProcessesObj)
    {
        $subject = '';

        $StudentReportCardEmailTable = TableRegistry::get('ReportCard.StudentReportCardEmail');
		$modelAlias = $StudentReportCardEmailTable->registryAlias();
        $availablePlaceholders = $StudentReportCardEmailTable->getPlaceholders();
        $reportCardId = $studentsReportCardEntity->student_profile_template_id;
        $emailTemplateEntity = $this->EmailTemplates->getTemplate($modelAlias, self::EMAIL_TEMPLATE);		
        $subject = $this->replacePlaceholders($emailTemplateEntity->subject, $availablePlaceholders, $studentsReportCardEntity);
        
		if (!empty($subject)) {
            $emailProcessesObj['subject'] = $subject;
        }
    }

    private function setMessage(Entity $studentsReportCardEntity, ArrayObject $emailProcessesObj)
    {
        $message = '';

        $StudentReportCardEmailTable = TableRegistry::get('ReportCard.StudentReportCardEmail');
        $modelAlias = $StudentReportCardEmailTable->registryAlias();
        $availablePlaceholders = $StudentReportCardEmailTable->getPlaceholders();
        $reportCardId = $studentsReportCardEntity->student_profile_template_id;
        $emailTemplateEntity = $this->EmailTemplates->getTemplate($modelAlias, self::EMAIL_TEMPLATE);
        $message = $this->replacePlaceholders($emailTemplateEntity->message, $availablePlaceholders, $studentsReportCardEntity);

        if (!empty($message)) {
            $emailProcessesObj['message'] = $message;
        }
    }

    private function setAttachments(Entity $studentsReportCardEntity, ArrayObject $emailProcessesObj)
    {        
		$attachments = [];
        if ($studentsReportCardEntity->has('file_name') && !empty($studentsReportCardEntity->file_name) && $studentsReportCardEntity->has('file_content_pdf') && !empty($studentsReportCardEntity->file_content_pdf)) {
			if(!empty($studentsReportCardEntity->student_id)) {
				$fileNameData = explode(".",$studentsReportCardEntity->file_name);
				$pdfFileName = $fileNameData[0].'.pdf';
				$file_content = NULL;
				$file_content = $studentsReportCardEntity->file_content_pdf;
				$attachments[] = [
					'file_name' => $pdfFileName,
					'file_content' => $file_content
				];
			} else {
				$attachments[] = [
					'file_name' => $studentsReportCardEntity->file_name,
					'file_content' => $studentsReportCardEntity->file_content
				];
			}
        }

        if (!empty($attachments)) {
            $emailProcessesObj['email_process_attachments'] = $attachments;
        }
    }

    private function replacePlaceholders($message, $availablePlaceholders, $vars) {
        $format = '${%s}';
        $strArray = explode('${', $message);
        array_shift($strArray); // first element will not contain the placeholder

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);

                if (!empty($availablePlaceholders)) {
                    $value = Hash::get($vars, $placeholder);                    
                    $message = str_replace($replace, $value, $message);
                }
            }
        }

        return $message;
    }
}
