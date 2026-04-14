<?php
namespace System\Model\Table;

use ArrayObject;
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
use Cake\Mailer\Mailer;
use App\Model\Table\ControllerActionTable;

//POCOR-9396
class SystemProcessesTable extends ControllerActionTable
{
    protected $statusMap = [
        1  => 'New',
        2  => 'Running',
        3  => 'Completed',
        -1 => 'Abort',
        -2 => 'Error'
    ];

    public function initialize(array $config): void
    {
        $this->setTable('system_processes');
        parent::initialize($config);
        $this->toggle('view', true);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('created_user_id', ['visible' => false, 'sort' => false]);
        $this->field('params',          ['visible' => false, 'sort' => true]);
        $this->field('process',         ['visible' => false, 'sort' => true]);
        $this->field('executed_count',  ['visible' => false, 'sort' => true]);
        $this->field('callable_event',  ['visible' => false, 'sort' => true]);
        $this->field('created',         ['visible' => false, 'sort' => true]);

        $statusOption = [
             0 => 'All',
             1 => 'New',
             2 => 'Running',
             3 => 'Completed',
            -1 => 'Abort',
            -2 => 'Error'
        ];

        $featuresListOption = $this->find()
            ->select(['name'])
            ->group('name')
            ->order(['name' => 'ASC'])
            ->toArray();

        $options = [];
        foreach ($featuresListOption as $val) {
            $options[$val['name']] = $val['name'];
        }
        $allFeaturesOption = array_merge([0 => 'All Features'], $options);

        $selectedStatus         = $this->request->getQuery('status')   ?? 0;
        $selectedFeaturesOption = $this->request->getQuery('features') ?? 0;

        $extra['status']   = $selectedStatus;
        $extra['features'] = $selectedFeaturesOption;

        $extra['elements']['control'] = [
            'name' => 'System.processes',
            'data' => [
                'statusOption'          => $statusOption,
                'featuresOption'        => $allFeaturesOption,
                'selectedFeaturesOption'=> $extra['features'],
                'selectedStatus'        => $extra['status'],
            ],
            'options' => [],
            'order'   => 1
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setFieldOrder(['name', 'status', 'start_date', 'end_date', 'model']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $request  = $this->request->getQuery();
        $status   = $request['status']   ?? 0;
        $features = $request['features'] ?? 0;

        if ($status != 0) {
            $query->where([$this->getAlias() . '.status' => $status]);
        }

        if ($features != 0) {
            $query->where([$this->getAlias() . '.name' => $features]);
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'name':            return __('Feature');
            case 'callable_event':  return __('Callable Event');
            case 'executed_count':  return __('Executed Count');
            case 'created_user_id': return __('Created By');
            default:                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        if (!$entity->has('status')) {
            return null;
        }
        return $this->statusMap[$entity->status] ?? 'Unknown';
    }
}
