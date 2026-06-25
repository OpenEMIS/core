<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use DateTime;
class SecurityUserPasswordRequestsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_user_password_requests');
        parent::initialize($config);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options) 
    {
        //POCOR-8609
        $now = new DateTime();
        $expiry = (new DateTime())->modify('+ 1hour');
        $expiryFormat = $expiry->format('Y-m-d H:i:s');
        $entity->expiry_date =  $expiry;
    }
}
