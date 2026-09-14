<?php
namespace SSO\Controller;

use Cake\Controller\Controller;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class LoginController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function login()
    {
        $request = $this->getRequest();

        Log::write('debug', $request);

        if ($request->is('post')) {
            $username = $request->getData('username');
            $sessionId = $request->getData('session_id');

            // Commit current session.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Store current session ID.
            session_start();
            $currentSessionId = session_id();
            session_write_close();

            // Hijack and destroy specified session ID.
            if (!empty($sessionId)) {
                session_id($sessionId);
                session_start();
                session_destroy();
                session_write_close();
            }

            // Restore existing session ID.
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
        $request = $this->getRequest();

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