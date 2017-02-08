<?php
namespace SSO\Model\Table;

use ArrayObject;
use Exception;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Http\Client;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Cake\Log\Log;

class SingleLogoutTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function afterLogout($user, array $autoLogoutUrl)
    {
        Log::write($user);
        $username = isset($user['username']) ? $user['username'] : null;
        if (!empty($username)) {
            Log::write($username);
            Log::write($autoLogoutUrl);
            $this->removeLogoutRecord($username, $autoLogoutUrl);
        }
    }

    public function afterLogin($user, $autoLogoutUrl, Request $request)
    {
        $sessionId = $request->session()->id();
        $username = isset($user['username']) ? $user['username'] : null;
        if (!empty($username) && !empty($sessionId)) {
            foreach ($autoLogoutUrl as $url) {
                if (!empty($url)) {
                    try {
                        // The following two lines are work around code to fix the trailing slash cause by the htaccess, without the trailing slash it will always be a redirect response
                        $url = rtrim($url, '/');
                        $url = $url.'/';
                        $this->putLogin($url, rtrim(Router::url(['plugin' => null, 'controller' => null, 'action' => 'index', '_ext' => null], true), '/'), $sessionId, $username);
                    } catch (Exception $e) {
                        Log::write('error', $e);
                    }
                }
            }
        }
    }

    private function putLogin($targetUrl, $sourceUrl, $sessionId, $username)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake Login ' . $targetUrl . ' ' . $sourceUrl . ' ' . $sessionId . ' ' . $username;
        $logs = ROOT . DS . 'logs' . DS . 'Login.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch(\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when login : '. $ex);
        }
    }

    private function postLogout($targetUrl, $sessionId, $username)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake Logout ' . $targetUrl . ' ' . $sessionId . ' ' . $username;
        $logs = ROOT . DS . 'logs' . DS . 'Logout.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch(\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when login : '. $ex);
        }
    }

    public function addRecord($url, $username, $sessionId)
    {
        $data = [
            'id' => Text::uuid(),
            'url' => $url,
            'username' => $username,
            'session_id' => $sessionId
        ];
        $newEntity = $this->newEntity($data);
        $this->save($newEntity);
    }

    private function getLogoutRecords($username)
    {
        return $this->find()->where([$this->aliasField('username') => $username])->toArray();
    }

    public function removeLogoutRecord($username, array $autoLogoutUrl)
    {
        $entities = $this->getLogoutRecords($username);
        foreach ($entities as $entity) {
            $entity->autoLogoutUrl = $autoLogoutUrl;
            Log::write('debug', 'delete record');
            $this->delete($entity);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        try {
            $http = new Client();

            // The following two lines are work around code to fix the trailing slash cause by the htaccess, without the trailing slash it will always be a redirect response
            $url = $entity->url;
            $username = $entity->username;
            $sessionId = $entity->session_id;
            $autoLogoutUrl = $entity->autoLogoutUrl;
            Log::write('debug', 'afterDelete');
            if (in_array($url, $autoLogoutUrl)) {
                Log::write('debug', 'post logout');
                $this->postLogout($url, $entity->session_id, $username);
            }
        } catch (Exception $e) {
            Log::write('debug', 'post error');
            Log::write('debug', $entity);
            Log::write('error', $e);
        }

    }
}
