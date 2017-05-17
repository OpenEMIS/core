<?php
namespace SSO\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class SecurityUserSessionsTable extends Table
{
    public function getSessions($username)
    {
        return $this
            ->find()
            ->select([$this->aliasField('id')])
            ->where([$this->aliasField('username') => $username])
            ->hydrate(false)
            ->toArray();
    }

    public function addEntry($username, $sessionId)
    {
        $data = [
            'id' => $sessionId,
            'username' => $username
        ];

        $newEntity = $this->newEntity($data);
        return $this->save($newEntity);
    }

    public function deleteEntry($username, $sessionId)
    {

        $entities = $this->find()
            ->where([
                $this->aliasField('username') => $username,
                $this->aliasField('id') => $sessionId
            ])
            ->toArray();

        foreach($entities as $entity) {
            $this->delete($entity);
        }
    }

    public function deleteEntries($username)
    {
        $entities = $this->find()
            ->where([
                $this->aliasField('username') => $username
            ])
            ->toArray();

        foreach($entities as $entity) {
            $this->delete($entity);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $sessionId = $entity->id;
        // Commit session
        if (session_id()) {
            // Same as session_write_close()
            session_commit();
        }

        // Store current session id
        session_start();
        $currentSessionId = session_id();
        session_commit();

        // Hijack and destroy specified session id
        session_id($sessionId);
        session_start();
        session_destroy();
        session_commit();

        // Restore existing session id
        session_id($currentSessionId);
        session_start();
        session_commit();
    }
}
