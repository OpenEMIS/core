<?php
namespace SSO\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class SLOComponent extends Component
{
    public function login()
    {
        $request = $this->getController()->getRequest();

        if ($request->is('post')) {
            $username = $request->getData('username');
            $sessionId = $request->getData('session_id');

            // Commit current session before switching session IDs.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Store current session ID.
            session_start();
            $currentSessionId = session_id();
            session_write_close();

            // Hijack and destroy the specified session ID.
            if (!empty($sessionId)) {
                session_id($sessionId);
                session_start();
                session_destroy();
                session_write_close();
            }

            // Restore the existing session ID.
            session_id($currentSessionId);
            session_start();
            session_write_close();

            if (!empty($username)) {
                $singleLogoutTable = TableRegistry::getTableLocator()
                    ->get('SSO.SingleLogout');

                $singleLogoutTable->removeLogoutRecord($username);
            }
        } elseif ($request->is('put')) {
            $this->captureLogin();
        }
    }

    private function captureLogin(): void
    {
        $request = $this->getController()->getRequest();

        $url = $request->getData('url');
        $sessionId = $request->getData('session_id');
        $username = $request->getData('username');

        if (!empty($url) && !empty($sessionId) && !empty($username)) {
            $singleLogoutTable = TableRegistry::getTableLocator()
                ->get('SSO.SingleLogout');

            $singleLogoutTable->addRecord(
                $url,
                $username,
                $sessionId
            );
        }
    }
}