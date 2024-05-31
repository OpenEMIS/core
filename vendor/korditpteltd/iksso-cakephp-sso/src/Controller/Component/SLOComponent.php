<?php
namespace SSO\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class SLOComponent extends Component
{
    public function login()
    {
        Log::write('debug', $this->request);
        if ($this->request->is('post')) {
            $username = $this->request->data('username');
            $sessionId = $this->request->data('session_id');
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
            if (!empty($username)) {
                $SingleLogoutTable = TableRegistry::get('SSO.SingleLogout');
                $SingleLogoutTable->removeLogoutRecord($username);
            }
        } else if ($this->request->is('put')) {
            $this->captureLogin();
        }
    }

    private function captureLogin()
    {
        $url = $this->request->data('url');
        $sessionId = $this->request->data('session_id');
        $username = $this->request->data('username');
        if (!empty($url) && !empty($sessionId) && !empty($username)) {
            TableRegistry::get('SSO.SingleLogout')->addRecord($url, $username, $sessionId);
        }
    }
}
