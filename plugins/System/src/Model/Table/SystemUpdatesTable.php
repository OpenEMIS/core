<?php
namespace System\Model\Table;

use ArrayObject;

use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;

class SystemUpdatesTable extends ControllerActionTable {
    public function initialize(array $config) {
        parent::initialize($config);

        $this->belongsTo('Approver', ['className' => 'Security.Users', 'foreignKey' => 'approved_by']);

        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.updates'] = 'updates';
        $events['Restful.Model.onGetAllowedActions'] = 'onGetAllowedActions';
        return $events;
    }

    public function onGetAllowedActions(Event $event)
    {
        return ['index'];
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('approved_by', ['type' => 'select', 'after' => 'date_approved']);
        $this->field('status', ['options' => ['1' => __('Pending'), '2' => __('Approved')], 'after' => 'approved_by']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->order([$this->aliasField('created') => 'DESC', $this->aliasField('version') => 'DESC']);

        $extra['toolbarButtons']['update'] = [
            'type' => 'button',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Update')
            ],
            'label' => '<i class="fa fa-refresh"></i>',
            'url' => ['updates']
        ];
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'updates') {
            $name = str_replace('Save', 'Update', $buttons[0]['name']);
            $buttons[0]['name'] = $name;
        }
    }

    public function updates(Event $mainEvent, ArrayObject $extra)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $supportEmails = $ConfigItems->value('version_support_emailss');

        if ($this->request->is(['post', 'put'])) {
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $emails = explode(',', $ConfigItems->value('version_support_emails'));

            // pr($emails);die;

            $host = $this->request->env('HTTP_HOST');
            $subject = 'Core Upgrade Request - ' . $host;
            // die;

            $email = new Email('openemis');
            $email
            ->to($emails)
            ->subject($subject)
            ->send($subject);
            // die;

            $this->updateAll(['date_approved' => Time::now(), 'approved_by' => $this->Auth->user('id'), 'status' => 2], ['status' => 1]);
            // $this->Alert->success()
            $mainEvent->stopPropagation();
            return $this->controller->redirect(['action' => 'Updates', 'index']);
        } else {
            if (!$supportEmails) {

                // $mainEvent->stopPropagation();
                // $this->Alert->show('Support email has not been set up.', 'error');
                // return $this->controller->redirect(['action' => 'Updates', 'index']);
            }
            // pr(Time::now());die;
            $query = $this->find();
            // pr($query->select(['max' => $query->func()->max('id')])->first());

            $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $toolbarButtonsArray['back']['type'] = 'button';
            $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
            $toolbarButtonsArray['back']['attr']['title'] = __('Back');
            $toolbarButtonsArray['back']['url'] = $this->url('index');
            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

            $extra['config']['form'] = true;
            $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
            $this->fields = []; // reset all the fields

            // $this->fields['description'] = [
            //     'type' =>
            // ];

            $entity = $this->newEntity();

            $this->controller->set('data', $entity);
        }
        return $entity;
    }
}
