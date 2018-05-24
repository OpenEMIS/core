<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;

class RecipientPaymentStructuresTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_payment_structures');
        parent::initialize($config);

        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // set header
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $recipientName = $this->Recipients->get($recipientId)->name;
        $this->controller->set('contentHeader', $recipientName . ' - ' . __('Payment Structures'));
        // set tabs
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'PaymentStructures');
        
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $title = __('Payment Structures');

        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $recipientName = $this->Recipients->get($recipientId)->name;

        $Navigation->addCrumb('Recipients', ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipients', 'action' => 'index']);
        $Navigation->addCrumb($recipientName);
        $Navigation->addCrumb($title);
    }

     
}
