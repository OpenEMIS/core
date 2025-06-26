<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Cake\Event\Event;
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

class CredentialsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('api_credentials');
//        $this->toggle('view', false);
//        $this->toggle('add', false);
//        $this->toggle('edit', false);
//        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('message', ['sort' => true]);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }
}
