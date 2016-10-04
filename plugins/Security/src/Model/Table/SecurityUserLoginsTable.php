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

    public function afterLogin(Event $event, Entity $userEntity)
    {
        $data = [
            'id' => Text::uuid(),
            'security_user_id' => $userEntity->id,
            'login_date_time' => Time::now()
        ];

        $this->save($this->newEntity($data));
    }
}
