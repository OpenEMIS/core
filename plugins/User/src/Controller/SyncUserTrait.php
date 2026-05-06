<?php
//POCOR-9590: shared SyncUser action — Students, Staff, Directories controllers differ only in the ACL gate
namespace User\Controller;

use Cake\ORM\TableRegistry;
use User\Model\Behavior\UserBehavior;

trait SyncUserTrait
{
    //POCOR-9590: public so Table::addSyncButton can delegate here instead of duplicating the triple
    abstract public function syncUserPermission(): array;

    //POCOR-9590: start - review/confirm sync of a user's General-tab fields from the external registry
    public function SyncUser()
    {
        if (!$this->AccessControl->check($this->syncUserPermission())) {
            $this->Alert->error(__('You do not have permission to sync this user.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        $pass          = $this->request->getAttribute('params')['pass'] ?? [];
        $decoded       = !empty($pass[0]) ? $this->ControllerAction->paramsDecode($pass[0]) : [];
        $userId        = $decoded['user_id'] ?? null;
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');

        $result = $SecurityUsers->buildExternalUserDiff($userId); //POCOR-9590: OAuth + API + diff in one call
        if (is_string($result)) {
            $this->Alert->error($result, ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }
        ['user' => $user, 'externalValues' => $externalValues, 'externalGenderId' => $externalGenderId, 'diff' => $diff] = $result;

        if (empty($diff) && !$this->request->is('post')) {
            if ((int)$user->sync_status !== UserBehavior::SYNC_STATUS_SYNCED) {
                $user->sync_status = UserBehavior::SYNC_STATUS_SYNCED;
                $SecurityUsers->save($user);
            }
            $this->Alert->ok(__('Already in sync — registry data matches.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        if ($this->request->is('post')) {
            $SecurityUsers->applySyncToUser($user, $externalValues, $externalGenderId); //POCOR-9590
            if ($SecurityUsers->save($user)) {
                $this->Alert->ok(__('User synced successfully.'), ['type' => 'string', 'reset' => true]);
            } else {
                $this->Alert->error(__('Failed to save synced data.'), ['type' => 'string', 'reset' => true]);
            }
            $originUrl = $this->request->getSession()->read('Sync.origin_url');
            $this->request->getSession()->delete('Sync.origin_url');
            return $this->redirect($originUrl ?: $this->referer());
        }

        $this->request->getSession()->write('Sync.origin_url', $this->referer());
        $encodedParams = !empty($pass[0]) ? $pass[0] : '';
        $this->set(compact('user', 'diff', 'externalValues', 'encodedParams'));
    }
    //POCOR-9590: end
}
