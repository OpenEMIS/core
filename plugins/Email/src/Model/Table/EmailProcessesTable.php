<?php
namespace Email\Model\Table;

use Cake\Mailer\Email;
use App\Model\Table\AppTable;

class EmailProcessesTable extends AppTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);

		$this->hasMany('EmailProcessAttachments', ['className' => 'Email.EmailProcessAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
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
		foreach ($entity->email_process_attachments as $key => $obj) {
			// to-do: create temporary file path
			$filepath = '';

			$attachments[] = $filepath;
		}

		return $attachments;
    }
}
