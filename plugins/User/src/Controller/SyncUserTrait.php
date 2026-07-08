<?php
//POCOR-9590: shared SyncUser action — Students, Staff, Directories controllers differ only in the ACL gate
namespace User\Controller;

use Cake\ORM\TableRegistry;
use User\Model\Behavior\UserBehavior;

trait SyncUserTrait
{
    //POCOR-9590: public so Table::addSyncButton can delegate here instead of duplicating the triple
    abstract public function syncUserPermission(): array;

    //POCOR-9590: start - one-click sync of a user's General-tab fields from the external registry
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

        $result = $SecurityUsers->buildExternalUserDiff($userId); //POCOR-9590: OAuth + API + mapping in one call
        if (is_string($result)) {
            $this->Alert->error($result, ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }
        ['user' => $user, 'externalValues' => $externalValues, 'externalGenderId' => $externalGenderId, 'diff' => $diff] = $result;

        //POCOR-9590: no mappings resolved on the external payload — refuse to silently mark as Synced
        $hasExternalData = $externalGenderId !== null || array_filter($externalValues, fn($v) => $v !== null && $v !== '');
        if (!$hasExternalData) {
            $this->Alert->error(__('External source returned no mappable fields — check the source attribute mappings.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        if (empty($diff)) {
            if ((int)$user->sync_status !== UserBehavior::SYNC_STATUS_SYNCED) {
                $user->sync_status = UserBehavior::SYNC_STATUS_SYNCED;
                $SecurityUsers->save($user);
            }
            $this->Alert->success(__('Already in sync — registry data matches.'), ['type' => 'string', 'reset' => true]); //POCOR-9590: Alert->ok() is a no-op in AlertComponent — only success/error/warning/info render
            return $this->redirect($this->referer());
        }

        $SecurityUsers->applySyncToUser($user, $externalValues, $externalGenderId); //POCOR-9590
        if ($SecurityUsers->save($user)) {
            $this->Alert->success(__('User synced from external registry — ') . count($diff) . __(' field(s) updated.'), ['type' => 'string', 'reset' => true]); //POCOR-9590: Alert->ok() is a no-op in AlertComponent — only success/error/warning/info render
        } else {
            $errors = $user->getErrors();
            $detail = $errors ? json_encode($errors) : 'unknown';
            $this->Alert->error(__('Failed to save synced data: ') . $detail, ['type' => 'string', 'reset' => true]);
        }
        return $this->redirect($this->referer());
    }
    //POCOR-9590: end
}
