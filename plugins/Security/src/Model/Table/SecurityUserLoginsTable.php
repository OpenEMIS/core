<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Utility\Text;
use App\Model\Table\AppTable;

class SecurityUserLoginsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Users.afterLogin'] = 'afterLogin';

        return $events;
    }

    public function afterLogin(Event $event, $user)
    {
        $controller = $event->subject();
        $request = $controller->request;
        $request->trustProxy = true;
        $data = [
            'id' => Text::uuid(),
            'security_user_id' => $user['id'],
            'login_date_time' => Time::now(),
            'ip_address' => $request->clientIp(),
            'session_id' => $request->session()->id()
        ];

        $this->save($this->newEntity($data));
    }
}
