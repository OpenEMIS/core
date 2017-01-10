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
use Cake\Network\Http\Client;

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
        $this->field('approved_by', ['after' => 'date_approved']);
        $this->field('status', ['options' => ['1' => __('Pending'), '2' => __('Approved')], 'after' => 'approved_by']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->find();
        $maxId = $query->select(['max' => $query->func()->max('id')])->hydrate(false)->first();

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $domain = $ConfigItems->value('version_api_domain');
        $api = $domain . '/restful/v1/System-SystemUpdates.json?_fields=id,version,date_released&_limit=0';

        $http = new Client();
        $response = $http->get($api);

        if ($response->statusCode() == 200) {
            $data = array_reverse(json_decode($response->body(), true)['data']);

            foreach ($data as $item) {

                if ($item['id'] > $maxId['max']) {
                    $entity = $this->newEntity([
                        'id' => $item['id'],
                        'version' => $item['version'],
                        'date_released' => $item['date_released'],
                        'created' => Time::now(),
                    ]);
                    $this->save($entity);
                }
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->query;
        if (!array_key_exists('sort', $queryParams)) {
            $query->order([$this->aliasField('date_released') => 'DESC', $this->aliasField('version') => 'DESC']);
        }

        if ($this->exists(['status' => 1])) {
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
                'url' => ['controller' => $this->controller->name, 'action' => 'Updates', 'updates']
            ];
        }
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'updates') {
            $name = str_replace('Save', 'Update', $buttons[0]['name']);
            $buttons[0]['name'] = $name;

            $buttons[1]['url'] = ['controller' => $this->controller->name, 'action' => 'Updates', 'index'];
        }
    }

    public function updates(Event $mainEvent, ArrayObject $extra)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $supportEmails = $ConfigItems->value('version_support_emails');

        if (!$this->exists(['status' => 1])) { // if there are nothing to update
            $mainEvent->stopPropagation();
            return $this->controller->redirect(['action' => 'Updates', 'index']);
        }

        if ($this->request->is(['post', 'put'])) {
            $emails = explode(',', $supportEmails);

            $host = $this->request->env('HTTP_HOST');
            $subject = 'Core Upgrade Request - ' . $host;

            $email = new Email('openemis');
            $email
            ->to($emails)
            ->subject($subject)
            ->send($subject);

            $this->updateAll(['date_approved' => Time::now(), 'approved_by' => $this->Auth->user('id'), 'status' => 2], ['status' => 1]);
            $this->Alert->show('Your update request has been submitted.', 'success');

            $mainEvent->stopPropagation();
            return $this->controller->redirect(['action' => 'Updates', 'index']);
        } else {
            if (!$supportEmails) {
                $mainEvent->stopPropagation();
                $this->Alert->show('Support email has not been set up.', 'error');
                return $this->controller->redirect(['action' => 'Updates', 'index']);
            }

            $extra['config']['form'] = true;
            $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
            $this->fields = []; // reset all the fields

            $this->field('description', [
                'type' => 'element',
                'element' => 'System.description'
            ]);

            $entity = $this->newEntity();

            $this->controller->set('data', $entity);
            return $entity;
        }
    }
}
