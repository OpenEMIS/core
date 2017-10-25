<?php
namespace System\Model\Table;

use ArrayObject;
use InvalidArgumentException;

use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\I18n\Time;
use Cake\Http\Client;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class SystemUpdatesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
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

        if (array_key_exists('Restful.Model.onRenderDate', $events)) { // prevent renderDate logic
            unset($events['Restful.Model.onRenderDate']);
        }
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
        $latestVersion = $query
            ->order([$this->aliasField('id') => 'desc'])
            ->first();

        $maxId = $latestVersion->id;
        if ($latestVersion->status == 2) {
            $this->updateAll(
                [
                    'date_approved' => $latestVersion->date_approved,
                    'approved_by' => 1,
                    'status' => 2
                ], [
                    'id <' => $maxId,
                    'status' => 1
                ]
            );
        }

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $domain = $ConfigItems->value('version_api_domain');
        $api = $domain . '/restful/v2/System-SystemUpdates.json?_fields=id,version,date_released&_limit=50&_order=-id';

        $http = new Client();
        $response = $http->get($api);

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $supportEmails = $ConfigItems->value('version_support_emails');
        $emails = explode(',', $supportEmails);

        $host = $this->request->env('HTTP_HOST');
        $subject = 'Core Upgrade Request Failed - ' . $host;

        if ($response->getStatusCode() == 200) {
            $jsonResponse = json_decode($response->body(), true);
            $data = array_reverse($jsonResponse['data']);

            foreach ($data as $item) {
                if ($item['id'] > $maxId) {
                    $entity = $this->newEntity([
                        'id' => $item['id'],
                        'version' => $item['version'],
                        'date_released' => $item['date_released'],
                        'created' => Time::now(),
                    ]);
                    $result = $this->save($entity);
                    if ($result == false) {
                        try {
                            Log::write('error', $entity->toArray());
                            Log::write('error', $entity->errors());
                            $email = new Email('openemis');
                            $email->to($emails)->subject($subject)->send('Unable to update system versions');
                        } catch (InvalidArgumentException $ex) {
                            Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
                        }
                    }
                }
            }

            $version = trim(file_get_contents(WWW_ROOT . 'version'));
            $entity = $this->find()->where(['version' => $version])->first();

            if (!is_null($entity)) {
                $this->updateAll(
                    [
                        'date_approved' => $entity->date_released,
                        'approved_by' => 1,
                        'status' => 2
                    ], [
                        'id <=' => $entity->id,
                        'status' => 1
                    ]
                );
            }
        } else {
            try {
                Log::write('error', 'Unable to retrieve system versions (Status Code: ' . $response->getStatusCode() . ')');
                $email = new Email('openemis');
                $email->to($emails)->subject($subject)->send('Unable to retrieve system versions.');
            } catch (InvalidArgumentException $ex) {
                Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
            }
        }

        $changelogUrl = $domain . '/CHANGELOG.md';
        $changelogBtn = $this->getButtonTemplate();
        $changelogBtn['attr']['title'] = __('Changelog');
        $changelogBtn['attr']['target'] = '_blank';
        $changelogBtn['label'] = '<i class="fa fa-list"></i>';
        $changelogBtn['url'] = $changelogUrl;

        $extra['toolbarButtons']['changelog'] = $changelogBtn;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->query;
        if (!array_key_exists('sort', $queryParams)) {
            $query->order([$this->aliasField('date_released') => 'DESC', $this->aliasField('version') => 'DESC']);
        }

        if ($this->exists(['status' => 1])) {
            $updateBtn = $this->getButtonTemplate();

            $updateBtn['attr']['title'] = __('Update');
            $updateBtn['label'] = '<i class="fa fa-refresh"></i>';
            $updateBtn['url'] = ['controller' => $this->controller->name, 'action' => 'Updates', 'updates'];

            $extra['toolbarButtons']['update'] = $updateBtn;
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
