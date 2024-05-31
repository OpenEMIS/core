<?php
namespace SSO\Shell;

use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\Http\Client;
use Exception;

class LogoutShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Initialize Logout Shell ...');
        try {
            $http = new Client();
            $url = $this->args[0];
            $sessionId = $this->args[1];
            $username = $this->args[2];
            $response = $http->post($url, ['session_id' => $sessionId, 'username' => $username]);
            $this->out($response->getStatusCode());
            $this->out('End Processing Logout Shell');
        } catch (\Exception $e) {
            $this->out('Logout Shell > Exception : ');
            $this->out($e->getMessage());
        }
    }
}
