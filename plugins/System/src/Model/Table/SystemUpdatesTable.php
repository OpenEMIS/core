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
        $this->field('approved_by', ['type' => 'select', 'after' => 'date_approved']);
        $this->field('status', ['options' => ['1' => __('Pending'), '2' => __('Approved')], 'after' => 'approved_by']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $SystemPatches = TableRegistry::get('System.SystemPatches');
        $versions = $SystemPatches
            ->find()
            ->select(['version', 'created'])
            ->where([
                'NOT EXISTS (SELECT 1 FROM system_updates WHERE system_updates.version = SystemPatches.version AND system_updates.status = 2)'
            ])
            ->group(['version'])
            ->order(['created' => 'ASC', 'version' => 'ASC'])
            ->all();

        $query = $this->find();
        $maxId = $query->select(['max' => $query->func()->max('id')])->hydrate(false)->first();

        foreach ($versions as $version) {
            if ($this->exists(['version' => $version->version])) {
                $this->updateAll(['status' => 2], ['version' => $version->version]);
            } else {
                $entity = $this->newEntity([
                    'id' => ++$maxId['max'],
                    'version' => $version->version,
                    'date_released' => $version->created,
                    'date_approved' => $version->created,
                    'approved_by' => 1,
                    'status' => 2,
                    'created_user_id' => 1,
                    'created' => $version->created
                ]);
                $this->save($entity);
            }
        }

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
                        'date_approved' => NULL,
                        'approved_by' => NULL,
                        'status' => 1,
                        'created_user_id' => 1,
                        'created' => Time::now(),
                    ]);
                    $this->save($entity);
                }
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->order([$this->aliasField('date_released') => 'DESC', $this->aliasField('version') => 'DESC']);

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
                'url' => ['updates']
            ];
        }
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'updates') {
            $name = str_replace('Save', 'Upgrade', $buttons[0]['name']);
            $buttons[0]['name'] = $name;
        }
    }

    public function updates(Event $mainEvent, ArrayObject $extra)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $supportEmails = $ConfigItems->value('version_support_emails');

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
            $this->Alert->show('Your upgrade request has been submitted.', 'success');

            $mainEvent->stopPropagation();
            return $this->controller->redirect(['action' => 'Updates', 'index']);
        } else {
            if (!$supportEmails) {
                $mainEvent->stopPropagation();
                $this->Alert->show('Support email has not been set up.', 'error');
                return $this->controller->redirect(['action' => 'Updates', 'index']);
            }

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

            $this->field('description', [
                'type' => 'element',
                'element' => 'System.description'
            ]);

            $entity = $this->newEntity();

            $this->controller->set('data', $entity);
        }
        return $entity;
    }
}
