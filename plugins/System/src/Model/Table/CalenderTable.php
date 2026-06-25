<?php

namespace System\Model\Table;

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

class CalenderTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'message'];
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->toggle('add', true);
        $this->toggle('search', true);
        $this->toggle('edit', false);
        $this->toggle('view', true);
        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderStartDate'] = 'onRenderStartDate';
        $event['Controller.Page.onRenderEndDate'] = 'onRenderEndDate';
        $event['Controller.Page.onRenderCalendarTypeId'] = 'onRenderCalendarTypeId';
        $event['Controller.Page.getEntityDisabledActions'] = 'getEntityDisabledActions';

        return $event;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('message', ['sort' => true]);

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {

    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }
}
