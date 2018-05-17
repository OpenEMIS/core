<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class ScholarshipRecipientsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->ScholarshipRecipients);
    }

    public function beforeFilter(Event $event)
    {
    	$page = $this->Page;
        parent::beforeFilter($event);

		$page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
		$page->addCrumb('Recipients');

		$page->setHeader(__('Scholarships') . ' - ' . __('Recipients'));

        $page->disable(['add', 'delete']);
    }
}
