<?php
namespace SSO\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\Utility\Text;

class SingleLogoutTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function afterLogout($user, array $autoLogoutUrl): void
    {
        $username = isset($user['username']) ? $user['username'] : null;

        if (!empty($username)) {
            $this->removeLogoutRecord($username, $autoLogoutUrl);
        }
    }

    public function afterLogin(
        $user,
        array $autoLogoutUrl,
        ServerRequest $request
    ): void {
        $sessionId = $request->getSession()->id();
        $username = isset($user['username']) ? $user['username'] : null;

        if (!empty($username) && !empty($sessionId)) {
            foreach ($autoLogoutUrl as $url) {
                if (!empty($url)) {
                    try {
                        // Workaround for the trailing slash caused by htaccess.
                        // Without the trailing slash, the request will return
                        // a redirect response.
                        $selfUrl = Router::url(
                            [
                                'plugin' => null,
                                'controller' => null,
                                'action' => 'index',
                                '_ext' => null,
                            ],
                            true
                        ) . '/';

                        $this->putLogin(
                            $url,
                            $selfUrl,
                            $sessionId,
                            $username
                        );
                    } catch (\Throwable $e) {
                        Log::write('error', $e);
                    }
                }
            }
        }
    }

    private function putLogin(
        $targetUrl,
        $sourceUrl,
        $sessionId,
        $username
    ): void {
        $cmd = ROOT . DS . 'bin' . DS . 'cake Login '
            . $targetUrl . ' '
            . $sourceUrl . ' '
            . $sessionId . ' '
            . $username;

        $logs = ROOT . DS . 'logs' . DS . 'Login.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            exec($shellCmd);
        } catch (\Throwable $ex) {
            Log::write(
                'error',
                __METHOD__ . ' exception when login : ' . $ex
            );
        }
    }

    private function postLogout(
        $targetUrl,
        $sessionId,
        $username
    ): void {
        $cmd = ROOT . DS . 'bin' . DS . 'cake Logout '
            . $targetUrl . ' '
            . $sessionId . ' '
            . $username;

        $logs = ROOT . DS . 'logs' . DS . 'Logout.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            exec($shellCmd);
        } catch (\Throwable $ex) {
            Log::write(
                'error',
                __METHOD__ . ' exception when logout : ' . $ex
            );
        }
    }

    public function addRecord(
        $url,
        $username,
        $sessionId
    ): void {
        $data = [
            'id' => Text::uuid(),
            'url' => $url,
            'username' => $username,
            'session_id' => $sessionId,
        ];

        $newEntity = $this->newEntity($data);
        $this->save($newEntity);
    }

    private function getLogoutRecords($username): array
    {
        return $this->find()
            ->where([
                $this->aliasField('username') => $username,
            ])
            ->toArray();
    }

    public function removeLogoutRecord(
        $username,
        array $autoLogoutUrl
    ): void {
        $entities = $this->getLogoutRecords($username);

        foreach ($entities as $entity) {
            $entity->autoLogoutUrl = $autoLogoutUrl;
            $this->delete($entity);
        }
    }

    public function afterDelete(
        EventInterface $event,
        Entity $entity,
        ArrayObject $options
    ): void {
        try {
            $url = $entity->url;
            $username = $entity->username;
            $sessionId = $entity->session_id;
            $autoLogoutUrl = $entity->autoLogoutUrl ?? [];

            if (in_array($url, $autoLogoutUrl, true)) {
                // Workaround for the trailing slash caused by htaccess.
                // Without the trailing slash, the request will return
                // a redirect response.
                $this->postLogout(
                    $url,
                    $sessionId,
                    $username
                );
            }
        } catch (\Throwable $e) {
            Log::write('error', 'post error');
            Log::write('error', $entity);
            Log::write('error', $e);
        }
    }
}