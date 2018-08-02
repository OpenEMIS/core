<?php
namespace Email\Model\Table;

use Cake\Mailer\Email;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;

use App\Model\Table\AppTable;

class EmailProcessesTable extends AppTable
{
	private $emailFolder = 'export';
	private $emailSubfolder = 'email';

	public function initialize(array $config)
    {
		parent::initialize($config);

		$this->hasMany('EmailProcessAttachments', ['className' => 'Email.EmailProcessAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->initializeFolders();
    }

    public function sendEmail($id = 0)
    {
    	if ($this->exists([$this->primaryKey() =>$id])) {
    		$entity = $this->find()
	 			->contain([
	 				'EmailProcessAttachments'
	 			])
	 			->where([
	 				$this->aliasField('id') => $id
	 			])
	 			->first();

	 		$email = new Email('openemis');

	 		$recipients = explode(",", $entity->recipients);
	 		$subject = $entity->subject;
	 		$message = $entity->message;

	        $email
	        	->to($recipients)
	            ->subject($subject);
			
			$attachments = $this->getAttachments($entity);
			if (!empty($attachments)) {
				$email->attachments($attachments);
			}

	        if ($email->send($message)) {
	        	if (!empty($attachments)) {
	        		foreach ($attachments as $filepath) {
	        			unlink($filepath);
	        		}
	        	}
	        	$result = $this->delete($entity);

	        	return $result;
	        }
    	}

    	return false;
    }

    private function getAttachments($entity)
    {
    	$attachments = [];

        // Server file path that the file will be saved in.
        $path = WWW_ROOT . $this->emailFolder . DS . $this->emailSubfolder . DS;

		foreach ($entity->email_process_attachments as $key => $obj) {
			$filepath = '';
			if (!empty($obj->file_name) && !empty($obj->file_content)) {
                $filepath = $path.$obj->file_name;

                // Create the file into the system.
                $studentReportCardFile = new File($filepath);
                $studentReportCardFile->write($this->getFile($obj->file_content));
			}

			if (!empty($filepath)) {
				$attachments[] = $filepath;
			}
		}

		return $attachments;
    }

    private function initializeFolders()
	{
		$model = $this->_table;

		$emailFolder = WWW_ROOT . $this->emailFolder;
		$emailSubfolder = WWW_ROOT . $this->emailFolder . DS . $this->emailSubfolder;

		new Folder($emailFolder, true, 0777);
		new Folder($emailSubfolder, true, 0777);
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
