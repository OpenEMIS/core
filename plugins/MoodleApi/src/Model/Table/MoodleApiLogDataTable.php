<?php

namespace MoodleApi\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\I18n\Time;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use Cake\Http\Response;

use App\Model\Table\ControllerActionTable;

class MoodleApiLogDataTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('moodle_api_log');
        parent::initialize($config);
        $this->toggle('view', true);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', 'Moodle Api Log');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('action', ['visible' => true, 'sort' => true]);
        $this->field('params', ['visible' => true, 'sort' => true]);
        $this->field('response', ['visible' => true, 'sort' => true]);
        $this->field('status', ['visible' => true, 'sort' => true]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('callback_param', ['visible' => false]);
        $this->field('callback', ['visible' => false]);

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        

    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {

    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    /*public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }*/

    public function onGetResponse(EventInterface $event, Entity $entity)
    {
        if ($entity->status == 1) {
            // Success response
            return json_encode(['success' => true, 'message' => 'created successfully.']);
        } else {
            // Error response
            return json_encode(['success' => false, 'message' => 'An unknown error occurred', 'error' => $entity->response]);
        }
    }

}
