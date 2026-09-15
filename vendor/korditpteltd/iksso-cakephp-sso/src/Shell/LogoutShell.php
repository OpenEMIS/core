<?php
namespace SSO\Shell;

use Cake\Console\Shell;
use Cake\Http\Client;

class LogoutShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function main(): void
    {
        $this->out('Initialize Logout Shell ...');

        try {
            $http = new Client();

            $url = $this->args[0] ?? null;
            $sessionId = $this->args[1] ?? null;
            $username = $this->args[2] ?? null;

            if (empty($url) || empty($sessionId) || empty($username)) {
                $this->err('Logout Shell > Missing required arguments.');
                return;
            }

            $response = $http->post($url, [
                'session_id' => $sessionId,
                'username' => $username,
            ]);

            $this->out((string)$response->getStatusCode());
            $this->out('End Processing Logout Shell');
        } catch (\Throwable $e) {
            $this->err('Logout Shell > Exception:');
            $this->err($e->getMessage());
        }
    }
}