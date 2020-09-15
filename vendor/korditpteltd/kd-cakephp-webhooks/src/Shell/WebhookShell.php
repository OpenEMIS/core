<?php
namespace Webhook\Shell;

use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\Network\Http\Client;
use Exception;

class WebhookShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
		$this->out('Initialize Webhook Shell ...');
		try {
			$http = new Client();
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
