<?php
namespace Webhook\Shell;

use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\Network\Http\Client;
use Cake\ORM\TableRegistry;
use Exception;

class WebhookShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
		$this->out('Initialize Webhook Shell ...');
		try {
			//POCOR-6804: START
			$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
			$apiToken = $ConfigItems->value('api_settings');
			//$http = new Client();
			$http = new Client([
                'headers' => ['Authorization' => $apiToken]
            ]);
			//POCOR-6804: END
			$url = $this->args[0];
			$method = strtolower($this->args[1]);
			$body = $this->args[2];
			$response = $http->$method($url, $body, ['type' => 'json']);
			$this->out('End Processing Webhook Shell');
		} catch (\Exception $e) {
			$this->out('Logout Shell > Exception : ');
			$this->out($e->getMessage());
		}
	}
}
