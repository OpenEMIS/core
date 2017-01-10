<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Http\Client;
use ArrayObject;

class SingleLogoutTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
	}

    public function addRecord($url, $username, $sessionId)
    {
        $data = [
            'url' => $url,
            'username' => $username,
            'session_id' => $sessionId
        ];
        $this->save($data);
    }

	public function getLogoutRecords($username)
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
        $http = new Client();
        $url = $entity->url;
        $username = $entity->username;
        $http->post($url, ['username' => $username]);
    }
}
