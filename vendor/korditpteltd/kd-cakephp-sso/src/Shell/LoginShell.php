<?php
namespace SSO\Shell;

use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\Http\Client;
use Exception;

class LoginShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Initialize Login Shell ...');
        try {
            $http = new Client();
            $url = $this->args[0];
            $sourceUrl = $this->args[1];
            $sessionId = $this->args[2];
            $username = $this->args[3];
            $response = $http->put($url, ['url' => $sourceUrl, 'session_id' => $sessionId, 'username' => $username]);
            $this->out('End Processing Login Shell');
        } catch (\Exception $e) {
            $this->out('Login Shell > Exception : ');
            $this->out($e->getMessage());
        }
    }
}
