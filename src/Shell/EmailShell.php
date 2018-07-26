<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Mailer\Email;

class EmailShell extends Shell {
	// for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

	public function initialize() {
		parent::initialize();
        $this->loadModel('Institution.InstitutionStudentsReportCards');
        $this->loadModel('User.Users');
        $this->loadModel('ReportCard.ReportCards');
        $this->loadModel('ReportCard.ReportCardEmail');

        $this->loadModel('ReportCard.EmailProcesses');
        $this->loadModel('ReportCard.EmailProcessAttachments');

        // pr('-0-');
        // pr($this->EmailProcesses);
        // pr($this->EmailProcessAttachments);
        // die;
	}

	public function main() {
	    if (!empty($this->args[0])) {
	    	switch ($this->args[0]) {
			    case "ReportCard.ReportCardEmail":
			        $this->ReportCardEmail();
			        break;
			    default:
			        // Do nothing
			}






    //     	$institutionId = $this->args[0];
    //     	$institutionClassId = $this->args[1];
    //     	$reportCardId = $this->args[2];		// Model Reference ID
    //     	$createdUserId = $this->args[3];
    //     	$modelAlias = $this->args[4];

    //         $pid = getmypid();

    //     	// Only email report cards with published status
    //     	// Getting the student email
    //     	$studentEmailEntity = $this->InstitutionStudentsReportCards
				// ->find()
	   //          ->contain(['Students', 'ReportCards'])
	   //          ->where([
	   //              $this->InstitutionStudentsReportCards->aliasField('institution_id') => $institutionId,
	   //              $this->InstitutionStudentsReportCards->aliasField('institution_class_id') => $institutionClassId,
	   //              $this->InstitutionStudentsReportCards->aliasField('report_card_id') => $reportCardId,
	   //              $this->InstitutionStudentsReportCards->aliasField('status IN ') => self::PUBLISHED,
	   //              $this->InstitutionStudentsReportCards->aliasField('file_name IS NOT NULL'),
	   //              $this->InstitutionStudentsReportCards->aliasField('file_content IS NOT NULL'),
	   //              $this->InstitutionStudentsReportCards->Students->aliasField('email IS NOT NULL'),
	   //              $this->InstitutionStudentsReportCards->Students->aliasField('email <> ""')

	   //          ])
	   //          ->select([
	   //                      // $this->StudentsReportCards->Students->aliasField('first_name'),
	   //                      // $this->StudentsReportCards->Students->aliasField('middle_name'),
	   //                      // $this->StudentsReportCards->Students->aliasField('third_name'),
	   //                      // $this->StudentsReportCards->Students->aliasField('last_name'),
	   //                      // $this->StudentsReportCards->Students->aliasField('preferred_name'),
	   //                      // $this->StudentsReportCards->Students->aliasField('email'),
	   //                      $this->InstitutionStudentsReportCards->aliasField('file_name'),
	   //                      $this->InstitutionStudentsReportCards->aliasField('file_content'),
	                    
	   //                      $this->InstitutionStudentsReportCards->Students->aliasField('email')
	   //                  ])
	   //          ->toArray();

				// // Get the subject and message template. 
				// $subjectMessageEntity = TableRegistry::get('email_templates');
				// $subjectMessageRecord = $subjectMessageEntity
				// 	->find()
				// 	->where([
				// 		$subjectMessageEntity->aliasField('model_alias') => $modelAlias,
				// 		$subjectMessageEntity->aliasField('model_reference') => $reportCardId
				// 	])
				// 	->select([
				// 		$subjectMessageEntity->aliasField('subject'),
				// 		$subjectMessageEntity->aliasField('message')
				// 	])
				// 	->first();

    //         	// Create recipients records to email_processes table && Create attachment records for each student to email_process_attachments table
				// $emailProcessEntity = TableRegistry::get('email_processes');
				// $emailProcessAtachmentEntity = TableRegistry::get('email_process_attachments');

    //             foreach ($studentEmailEntity as $studentEmail) {
				// 	$recipients->to = $studentEmail->Students->email;

				// 	$recipientsJSON = json_encode($recipients);

				// 	// Get current date time for each of the record generated
				// 	$now = Time::now();

				// 	// Create Record
    //                 $newEntity = $emailProcessEntity->newEntity();

    //                 $newEntity->recipients = $recipientsJSON;
    //                 $newEntity->subject = $subjectMessageRecord->subject;
    //                 $newEntity->message = $subjectMessageRecord->message;
    //                 $newEntity->created_user_id = $createdUserId;
    //                 $newEntity->created = $now;

    //                 $returnSavedRecordEntity = $emailProcessEntity->save($newEntity);

    //                 // Get the record ID and get the attachemnt for the student.
    //                 // pr($returnRecordEntity->id);
                    

    //             }


                

				// pr($subjectMessageRecord);die;

				

	    }
	}

	private function ReportCardEmail()
	{
		$modelAlias = $this->args[0];
    	$reportCardId = $this->args[1];
    	$institutionId = $this->args[2];		
    	$institutionClassId = $this->args[3];
    	$createdUserId = $this->args[4];

        $pid = getmypid();

    	// Only email report cards with published status
    	// Getting the student email
    	$studentEmailEntity = $this->InstitutionStudentsReportCards
			->find()
            ->contain(['Students', 'ReportCards'])
            ->where([
                $this->InstitutionStudentsReportCards->aliasField('institution_id') => $institutionId,
                $this->InstitutionStudentsReportCards->aliasField('institution_class_id') => $institutionClassId,
                $this->InstitutionStudentsReportCards->aliasField('report_card_id') => $reportCardId,
                $this->InstitutionStudentsReportCards->aliasField('status IN ') => self::PUBLISHED,
                $this->InstitutionStudentsReportCards->aliasField('file_name IS NOT NULL'),
                $this->InstitutionStudentsReportCards->aliasField('file_content IS NOT NULL'),
                $this->InstitutionStudentsReportCards->Students->aliasField('email IS NOT NULL'),
                $this->InstitutionStudentsReportCards->Students->aliasField('email <> ""')

            ])
            ->select([
                        // $this->StudentsReportCards->Students->aliasField('first_name'),
                        // $this->StudentsReportCards->Students->aliasField('middle_name'),
                        // $this->StudentsReportCards->Students->aliasField('third_name'),
                        // $this->StudentsReportCards->Students->aliasField('last_name'),
                        // $this->StudentsReportCards->Students->aliasField('preferred_name'),
                        // $this->StudentsReportCards->Students->aliasField('email'),
                        $this->InstitutionStudentsReportCards->aliasField('file_name'),
                        $this->InstitutionStudentsReportCards->aliasField('file_content'),
                    
                        $this->InstitutionStudentsReportCards->Students->aliasField('email')
                    ])
            ->toArray();

			// Get the subject and message template. 
		$subjectMessageEntity = TableRegistry::get('email_templates');
		$subjectMessageRecord = $subjectMessageEntity
			->find()
			->where([
				$subjectMessageEntity->aliasField('model_alias') => $modelAlias,
				$subjectMessageEntity->aliasField('model_reference') => $reportCardId
			])
			->select([
				$subjectMessageEntity->aliasField('subject'),
				$subjectMessageEntity->aliasField('message')
			])
			->first();

    	// Create recipients records to email_processes table && Create attachment records for each student to email_process_attachments table
		// $emailProcessEntity = TableRegistry::get('email_processes');
		// $emailProcessAtachmentEntity = TableRegistry::get('email_process_attachments');		

        foreach ($studentEmailEntity as $studentEmail) {
			$recipients->to = $studentEmail->student->email;
			$recipientsJSON = json_encode($recipients);

			// Get current date time for each of the record generated
			$now = Time::now();

			// Create Record
            // $newEntity = $emailProcessEntity->newEntity();
            $newEntity = $this->EmailProcesses->newEntity();
            $newEntity->recipients = $recipientsJSON;
            $newEntity->subject = $subjectMessageRecord->subject;
            $newEntity->message = $subjectMessageRecord->message;
            $newEntity->created_user_id = $createdUserId;
            $newEntity->created = $now;
            // $returnSavedRecordEntity = $emailProcessEntity->save($newEntity);
            $returnSavedRecordEntity = $this->EmailProcesses->save($newEntity);

            // Get the record ID and get the attachemnt for the student.
            // $newEntity = $emailProcessAtachmentEntity->newEntity();
            $newEntity = $this->EmailProcessAttachments->newEntity();
            $newEntity->file_name = $studentEmail->file_name;
            $newEntity->file_content = $studentEmail->file_content;
            $newEntity->email_processes_id = $returnSavedRecordEntity->id;
            $newEntity->created_user_id = $createdUserId;
            $newEntity->created = $now;
            // $emailProcessAtachmentEntity->save($newEntity);
            $this->EmailProcessAttachments->save($newEntity);
        }

            // Once all the records are generated. It time to create a temp file and start to email to each of the student.
        // pr($this->EmailProcessAttachments);
			pr($this->EmailProcessAttachments->find()->contain(['email_processes'])->toArray());die;    
        die;

			// pr($this->EmailProcessAttachments->find()->contain(['email_processes'])->toArray());die;    

        $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;

        if($this->EmailProcessAttachments->find()->count() > 0) {
			foreach ($this->EmailProcessAttachments->find()->contain(['email_processes']) as $studentAttachment) {

                $filepath = $path.$studentAttachment->file_name;

                // Create the file into the system so that it can attach to the email.
                $studentReportFile = new File($filepath);
                $studentReportFile->write($this->getFile($studentAttachment->file_content));

                $email = new Email('openemis');
                $email
                    ->to(json_decode($studentAttachment->report_card_email->recipients)->to)
                    ->subject($studentAttachment->report_card_email->subject)
                    ->attachments($filepath)
                    ->send($studentAttachment->report_card_email->message);

                    // Delete file after sending the email to the student
                    unlink($filepath);
        	}
        }
	}

	private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
