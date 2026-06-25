<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\Http\Client; 
use Cake\ORM\TableRegistry;
use Exception;

class WebhookShell extends Shell {
    public function initialize():void 
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Initialize Webhook Shell ('.FrozenTime::now().')...');
        try {
            //POCOR-6804: START
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
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
            print_r($this->args);
            $body = $this->args[2];$this->out(print_r($body));
            $this->out($url);
            $this->out($method);
            $response = $http->$method($url, $body, $options);
            $this->out('Response code: ' . $response->getStatusCode());
            $this->out('End Processing Webhook Shell '.FrozenTime::now().')...');
        } catch (\Exception $e) {
            $this->out('Logout Shell > Exception : ');
            $this->out($e->getMessage());
            $this->out('Time: '.FrozenTime::now().'...');
        }
    }
}
