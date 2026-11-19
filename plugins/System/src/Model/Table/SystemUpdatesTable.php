<?php
namespace System\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use App\Model\Table\ControllerActionTable;

class SystemUpdatesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Approver', ['className' => 'Security.Users', 'foreignKey' => 'approved_by']);

        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.updates'] = 'updates';
        $events['Restful.Model.onGetAllowedActions'] = 'onGetAllowedActions';

        if (array_key_exists('Restful.Model.onRenderDate', $events)) { // prevent renderDate logic
            unset($events['Restful.Model.onRenderDate']);
        }
        return $events;
    }

    public function onGetAllowedActions(EventInterface $event)
    {
        return ['index'];
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('approved_by', ['after' => 'date_approved']);
        $this->field('status', ['options' => ['1' => __('Pending'), '2' => __('Approved')], 'after' => 'approved_by']);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $query = $this->find();
        $latestVersion = $query
            ->order([$this->aliasField('id') => 'desc'])
            ->first();

        $maxId = $latestVersion->id ?? 0;

        if (!is_null($latestVersion) && $latestVersion->status == 2) {
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

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $domain = $ConfigItems->value('version_api_domain');
        // POCOR-9927: the remote version check must never break the page. On servers
        // that cannot reach version_api_domain (DNS/firewall/proxy/SSL) or that get a
        // non-JSON reply, the uncaught exception was rendered as a 404.
        $this->syncVersionsFromApi($domain, $maxId);

        $changelogUrl = $domain . '/CHANGELOG.md';
        $changelogBtn = $this->getButtonTemplate();
        $changelogBtn['attr']['title'] = __('Changelog');
        $changelogBtn['attr']['target'] = '_blank';
        $changelogBtn['label'] = '<i class="fa fa-list"></i>';
        $changelogBtn['url'] = $changelogUrl;

        $extra['toolbarButtons']['changelog'] = $changelogBtn;

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Updates','Updates');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    /**
     * POCOR-9927: Pull the latest released versions from version_api_domain and store
     * any new ones as pending. Every failure (unreachable host, timeout, non-200,
     * non-JSON reply) is logged and reported as a warning; the page always renders
     * with the versions already stored locally.
     */
    private function syncVersionsFromApi($domain, $maxId)
    {
        $api = $domain . '/restful/v2/System-SystemUpdates.json?_fields=id,version,date_released&_limit=50&_order=-id';
        $failMessage = __('Unable to retrieve the latest versions. The list below may not be up to date.');

        try {
            $http = new Client(['timeout' => 15]);
            $response = $http->get($api);
        } catch (\Throwable $ex) {
            Log::write('error', __METHOD__ . ': Unable to retrieve system versions from ' . $api . ': ' . $ex->getMessage());
            $this->Alert->show($failMessage, 'warning');
            return;
        }

        $jsonResponse = $response->isOk() ? json_decode($response->getStringBody(), true) : null;
        if (!is_array($jsonResponse) || !isset($jsonResponse['data']) || !is_array($jsonResponse['data'])) {
            Log::write('error', __METHOD__ . ': Unable to retrieve system versions from ' . $api . ' (Status Code: ' . $response->getStatusCode() . ')');
            $this->Alert->show($failMessage, 'warning');
            return;
        }

        foreach (array_reverse($jsonResponse['data']) as $item) {
            if (!isset($item['id'], $item['version']) || $item['id'] <= $maxId) {
                continue;
            }
            $entity = $this->newEntity([
                'id' => $item['id'],
                'version' => $item['version'],
                'date_released' => $item['date_released'] ?? null,
                'status' => 1, //POCOR-8891
                'created' => Time::now(),
            ]);
            if ($this->save($entity) == false) {
                Log::write('error', __METHOD__ . ': Unable to save system version ' . $item['version'] . ': ' . json_encode($entity->getErrors()));
                $this->notifySupport('Core Upgrade Request Failed - ', 'Unable to update system versions');
            }
        }

        $versionFile = WWW_ROOT . 'version';
        $version = is_file($versionFile) ? trim((string)file_get_contents($versionFile)) : '';
        $entity = $version !== '' ? $this->find()->where(['version' => $version])->first() : null;
        if (!is_null($entity)) {
            $this->updateAll(
                [
                    'date_approved' => null, //POCOR-8940
                    'approved_by' => 0, //POCOR-8940
                    'status' => 1
                ], [
                    'id <=' => $entity->id,
                    'status' => 1
                ]
            );
        }
    }

    /**
     * Email version_support_emails. Mail misconfiguration on a server must not break
     * the page, so failures are only logged. Returns true when the mail was sent.
     */
    private function notifySupport($subjectPrefix, $message)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $supportEmails = $ConfigItems->value('version_support_emails');
        if (empty($supportEmails)) {
            return false;
        }
        try {
            $email = new Mailer('openemis');
            $email
                ->setTo(array_map('trim', explode(',', $supportEmails)))
                ->setSubject($subjectPrefix . $this->request->getEnv('HTTP_HOST'))
                ->deliver($message);
            return true;
        } catch (\Throwable $ex) {
            Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
            return false;
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order([$this->aliasField('date_released') => 'DESC', $this->aliasField('version') => 'DESC']);
        }

        if ($this->exists(['status' => 1])) {
            $updateBtn = $this->getButtonTemplate();

            $updateBtn['attr']['title'] = __('Update');
            $updateBtn['label'] = '<i class="fa fa-refresh"></i>';
            $updateBtn['url'] = ['controller' => $this->controller->getName(), 'action' => 'Updates', 'updates'];

            $extra['toolbarButtons']['update'] = $updateBtn;
        }
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if ($this->action == 'updates') {
            $name = str_replace('Save', 'Update', $buttons[0]['name']);
            $buttons[0]['name'] = $name;

            $buttons[1]['url'] = ['controller' => $this->controller->getName(), 'action' => 'Updates', 'index'];
        }
    }

    public function updates(EventInterface $mainEvent, ArrayObject $extra)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $supportEmails = $ConfigItems->value('version_support_emails');

        if (!$this->exists(['status' => 1])) { // if there are nothing to update
            $mainEvent->stopPropagation();
            return $this->controller->redirect(['action' => 'Updates', 'index']);
        }

        if ($this->request->is(['post', 'put'])) {
            $subject = 'Core Upgrade Request - ' . $this->request->getEnv('HTTP_HOST');

            // POCOR-9927: a mail failure used to throw and render as a 404; only mark the
            // versions approved once support has actually been notified.
            if (!$this->notifySupport('Core Upgrade Request - ', $subject)) {
                $this->Alert->show('Unable to send the update request. Please check the email configuration and try again.', 'error');
                $mainEvent->stopPropagation();
                return $this->controller->redirect(['action' => 'Updates', 'index']);
            }

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

            $entity =$this->newEntity([]); //POCOR-8783

            $this->controller->set('data', $entity);
            return $entity;
        }
    }

     public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'version':
                return __('Version');
            case 'date_released':
                return __('Date Released');
            case 'date_approved':
                return __('Date Approved');
            case 'status':
                return __('Status');
            case 'approved_by':
                return __('Approved By');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
