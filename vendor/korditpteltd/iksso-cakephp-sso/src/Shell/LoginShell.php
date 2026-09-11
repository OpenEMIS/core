<?php
namespace SSO\Shell;

use Cake\Console\Shell;
use Cake\Http\Client;

class LoginShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function main(): void
    {
        $this->out('Initialize Login Shell ...');

        try {
            $http = new Client();

            $url = $this->args[0] ?? null;
            $sourceUrl = $this->args[1] ?? null;
            $sessionId = $this->args[2] ?? null;
            $username = $this->args[3] ?? null;

            if (empty($url) || empty($sourceUrl) || empty($sessionId) || empty($username)) {
                $this->err('Login Shell > Missing required arguments.');
                return;
            }

            $http->put($url, [
                'url' => $sourceUrl,
                'session_id' => $sessionId,
                'username' => $username,
            ]);

            $this->out('End Processing Login Shell');
        } catch (\Throwable $e) {
            $this->err('Login Shell > Exception:');
            $this->err($e->getMessage());
        }
    }
}