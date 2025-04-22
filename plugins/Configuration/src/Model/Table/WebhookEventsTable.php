<?php

namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\AppTable;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Http\ServerRequest;

class WebhookEventsTable extends Table
{

    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Define the association with the Webhook table
        $this->belongsTo('Webhooks', [
            'foreignKey' => 'webhook_id',
            'joinType' => 'INNER',
            'className' => 'Webhook.Webhooks'
        ]);
    }
}
