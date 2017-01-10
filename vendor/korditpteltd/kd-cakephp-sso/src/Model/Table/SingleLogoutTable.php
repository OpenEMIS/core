<?php
namespace SSO\Model\Table;

use Exception;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Http\Client;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\Routing\Router;
use ArrayObject;

class SingleLogoutTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
	}

    public function afterLogout($user)
    {
        $username = isset($user['username']) ? $user['username'] : null;
        if (!empty($username)) {
            $this->removeLogoutRecord($username);
        }
    }

    public function afterLogin($user, $autoLogoutUrl, Request $request)
    {
        $sessionId = $request->session()->id();
        $username = isset($user['username']) ? $user['username'] : null;
        if (!empty($username) && !empty($sessionId)) {
            $http = new Client();
            foreach ($autoLogoutUrl as $url) {
                if (!empty($url)) {
                    try {
                        $url = rtrim($url, '/');
                        $url = $url.'/';
                        $response = $http->put($url, ['url' => rtrim(Router::url([], true), '/'), 'session_id' => $sessionId, 'username' => $username]);
                    } catch (Exception $e) {
                        Log::write('error', $e);
                    }
                }
            }
        }
    }

    public function addRecord($url, $username, $sessionId)
    {
        $data = [
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

    public function removeLogoutRecord($username)
    {
        $entities = $this->getLogoutRecords($username);
        foreach ($entities as $entity) {
            $this->delete($entity);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        try {
            $http = new Client();
            $url = $entity->url;
            $username = $entity->username;
            $http->post($url, ['username' => $username]);
        } catch (Exception $e) {
            Log::write('error', $e);
        }

    }
}
