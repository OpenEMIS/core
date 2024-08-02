<?php
namespace Webhook\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Exception;

class WebhookEventsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }
}
