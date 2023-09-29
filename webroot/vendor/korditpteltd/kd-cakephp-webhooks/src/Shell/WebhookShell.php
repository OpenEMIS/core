<?php
namespace Webhook\Shell;

use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Http\Client;
use Cake\ORM\TableRegistry;
use Exception;

class WebhookShell extends Shell {
    public function initialize() {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Initialize Webhook Shell ('.Time::now().')...');
        try {
            //POCOR-6804: START
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $apiToken = $ConfigItems->value('api_settings');
            //$http = new Client();
            $http = new Client();
            $options = [
                'timeout' => 60,
                'headers' => ['Authorization' => $apiToken],
                'type' => 'json'
            ];
            //POCOR-6804: END
            $url = $this->args[0];
            $method = strtolower($this->args[1]);
            $body = $this->args[2];
            $this->out($url);
            $this->out($method);
            $response = $http->$method($url, $body, $options);
            $this->out('Response code: ' . $response->getStatusCode());
            $this->out('End Processing Webhook Shell '.Time::now().')...');
        } catch (\Exception $e) {
            $this->out('Logout Shell > Exception : ');
            $this->out($e->getMessage());
            $this->out('Time: '.Time::now().'...');
        }
    }
}
