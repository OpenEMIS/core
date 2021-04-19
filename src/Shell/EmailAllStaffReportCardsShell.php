<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Console\Shell;

class EmailAllStaffReportCardsShell extends Shell
{
	CONST EMAIL_TEMPLATE = 1;
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('SystemProcesses');
        $this->loadModel('ReportCard.StaffReportCardEmailProcesses');
        $this->loadModel('Institution.StaffReportCards');
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

            $this->out('Initialize Email All Staff Report Cards ('.Time::now().')');

            $exit = false;
            while (!$exit) {
                $recordToProcess = $this->StaffReportCardEmailProcesses->find()
                    ->select([
                        $this->StaffReportCardEmailProcesses->aliasField('staff_profile_template_id'),
                        $this->StaffReportCardEmailProcesses->aliasField('staff_id'),
                        $this->StaffReportCardEmailProcesses->aliasField('institution_id'),
                        $this->StaffReportCardEmailProcesses->aliasField('academic_period_id')
                    ])
                    ->where([
                        $this->StaffReportCardEmailProcesses->aliasField('status') => $this->StaffReportCardEmailProcesses::SENDING
                    ])
                    ->order([
                        $this->StaffReportCardEmailProcesses->aliasField('created'),
                        $this->StaffReportCardEmailProcesses->aliasField('staff_id')
                    ])
                    ->hydrate(false)
                    ->first();
				
                if (!empty($recordToProcess)) {
                    $this->out('Sending report card for Staff '.$recordToProcess['staff_id'].' ('. Time::now() .')');

                    $staffsReportCardEntity = $this->StaffReportCards
                        ->find()
                        ->select([
                            $this->StaffReportCards->aliasField('file_name'),
                            $this->StaffReportCards->aliasField('file_content'),
                            $this->StaffReportCards->aliasField('file_content_pdf'),
                            $this->StaffReportCards->aliasField('staff_id'),
                            $this->StaffReportCards->aliasField('institution_id'),
                            $this->StaffReportCards->aliasField('staff_profile_template_id'),
                            $this->StaffReportCards->aliasField('academic_period_id'),
                        ])
                        ->contain([
                            'Staffs' => [
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
                            'StaffTemplates' => [
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
                            $this->StaffReportCards->aliasField('staff_profile_template_id') => $recordToProcess['staff_profile_template_id'],
                            $this->StaffReportCards->aliasField('staff_id') => $recordToProcess['staff_id'],
                            $this->StaffReportCards->aliasField('institution_id') => $recordToProcess['institution_id'],
                            $this->StaffReportCards->aliasField('academic_period_id') => $recordToProcess['academic_period_id'],
                            $this->StaffReportCards->aliasField('status') => $this->StaffReportCards::PUBLISHED
                        ])
                        ->first();
					
                    if (!empty($staffsReportCardEntity)) {
                        $emailProcessesObj = new ArrayObject([
                            'recipients' => '',
                            'subject' => '',
                            'message' => '',
                            'email_process_attachments' => []
                        ]);

                        $this->setRecipients($staffsReportCardEntity, $emailProcessesObj);
                        $this->setSubject($staffsReportCardEntity, $emailProcessesObj);
                        $this->setMessage($staffsReportCardEntity, $emailProcessesObj);
                        $this->setAttachments($staffsReportCardEntity, $emailProcessesObj);

                        $emailProcessesData = $emailProcessesObj->getArrayCopy();
						// default email status is error
                        $emailStatus = $this->StaffReportCardEmailProcesses::ERROR;
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
                                            $emailStatus = $this->StaffReportCardEmailProcesses::SENT;
                                        } else {
                                            $errorMsg = "Failed to sent email.";
                                        }
                                    } catch (\Exception $e) {
                                        $errorMsg = $e->getMessage();

                                        $this->out('Error sending Report Card for Staff ' . $recordToProcess['staff_id']);
                                        $this->out($errorMsg);
                                    }
                                } else {
                                    $this->out('Staff Report Cards email process is not saved');
                                    $this->out($emailProcessesEntity->errors());
                                }
                            }
                        }
                        $this->StaffReportCardEmailProcesses->updateAll([
                            'status' => $emailStatus,
                            'error_message' => $errorMsg
                        ], [
                            'staff_profile_template_id' => $recordToProcess['staff_profile_template_id'],
                            'institution_id' => $recordToProcess['institution_id'],
                            'academic_period_id' => $recordToProcess['academic_period_id'],
                            'staff_id' => $recordToProcess['staff_id']
                        ]);
                    } else {
                        $this->out('Staff Report Cards not found');
                    }

                    $this->out('End sending report card for Staff '.$recordToProcess['staff_id'].' ('. Time::now() .')');
                } else {
                    $exit = true;
                    $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
                }
            }   // end while
            $this->out('End Email All Report Cards ('.Time::now().')');
        }
    }

    private function setRecipients(Entity $staffsReportCardEntity, ArrayObject $emailProcessesObj)
    {
        $recipientsArray = [];
        if ($staffsReportCardEntity->has('staff') && $staffsReportCardEntity->staff->has('email')) {
            $recipientsArray[] = $staffsReportCardEntity->staff->email;
        }

        if (!empty($recipientsArray)) {
            $emailProcessesObj['recipients'] = implode(",", $recipientsArray);
        }
    }

    private function setSubject(Entity $staffsReportCardEntity, ArrayObject $emailProcessesObj)
    {
        $subject = '';

        $StaffReportCardEmailTable = TableRegistry::get('ReportCard.StaffReportCardEmail');
		$modelAlias = $StaffReportCardEmailTable->registryAlias();
        $availablePlaceholders = $StaffReportCardEmailTable->getPlaceholders();
        $reportCardId = $staffsReportCardEntity->staff_profile_template_id;
        $emailTemplateEntity = $this->EmailTemplates->getTemplate($modelAlias, self::EMAIL_TEMPLATE);		
        $subject = $this->replacePlaceholders($emailTemplateEntity->subject, $availablePlaceholders, $staffsReportCardEntity);
        
		if (!empty($subject)) {
            $emailProcessesObj['subject'] = $subject;
        }
    }

    private function setMessage(Entity $staffsReportCardEntity, ArrayObject $emailProcessesObj)
    {
        $message = '';

        $StaffReportCardEmailTable = TableRegistry::get('ReportCard.StaffReportCardEmail');
        $modelAlias = $StaffReportCardEmailTable->registryAlias();
        $availablePlaceholders = $StaffReportCardEmailTable->getPlaceholders();
        $reportCardId = $staffsReportCardEntity->staff_profile_template_id;
        $emailTemplateEntity = $this->EmailTemplates->getTemplate($modelAlias, self::EMAIL_TEMPLATE);		
        $message = $this->replacePlaceholders($emailTemplateEntity->message, $availablePlaceholders, $staffsReportCardEntity);

        if (!empty($message)) {
            $emailProcessesObj['message'] = $message;
        }
    }

    private function setAttachments(Entity $staffsReportCardEntity, ArrayObject $emailProcessesObj)
    {        
		$attachments = [];
        if ($staffsReportCardEntity->has('file_name') && !empty($staffsReportCardEntity->file_name) && $staffsReportCardEntity->has('file_content_pdf') && !empty($staffsReportCardEntity->file_content_pdf)) {
			if(!empty($staffsReportCardEntity->staff_id)) {
				$fileNameData = explode(".",$staffsReportCardEntity->file_name);
				$pdfFileName = $fileNameData[0].'.pdf';
				$file_content = NULL;
				$file_content = $staffsReportCardEntity->file_content_pdf;
				$attachments[] = [
					'file_name' => $pdfFileName,
					'file_content' => $file_content
				];
			} else {
				$attachments[] = [
					'file_name' => $staffsReportCardEntity->file_name,
					'file_content' => $staffsReportCardEntity->file_content
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
