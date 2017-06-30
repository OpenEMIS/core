<?php
namespace SSO\Model\Table;

use Cake\I18n\Time;
use Cake\Utility\Text;
use Cake\ORM\Table;

class SecurityUserLoginsTable extends Table
{
    public function addLoginEntry($userId, $clientIp, $sessionId)
    {
        $now = Time::now();
        $data = [
            'security_user_id' => $userId,
            'login_date_time' => $now,
            'login_period' => $now->format('Ym'),
            'ip_address' => $clientIp,
            'session_id' => $sessionId
        ];

        $this->save($this->newEntity($data));
    }
}
