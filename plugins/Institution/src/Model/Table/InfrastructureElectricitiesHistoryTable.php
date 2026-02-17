<?php
namespace Institution\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

//POCOR-9475
class InfrastructureElectricitiesHistoryTable extends ControllerActionTable
{
    private $infrastructureTabsData = [0 => "Electricity", 1 => "Internet", 2 => "Telephone"];
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_electricities');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityElectricityTypes',   ['className' => 'Institution.UtilityElectricityTypes', 'foreign_key' => 'utility_electricity_type_id']);
        $this->belongsTo('UtilityElectricityConditions',   ['className' => 'Institution.UtilityElectricityConditions', 'foreign_key' => 'utility_electricity_condition_id']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);

    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityElectricities';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
        $this->field('start_date',['visible' => true]);
        $this->field('end_date',['visible' => true]);
        $this->field('is_current',['visible' => false]);
        $this->field('parent_id',['visible' => false]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('utility_electricity_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('utility_electricity_condition_id', ['attr' => ['label' => __('Condition')]]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment',['visible' => true]);

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InfrastructureUtilityElectricities',
            '0' => 'index',
            '1' => $encodedQueryString,
        ];
        $toolbarButtonsArray['back'] = $this->getButtonTemplate();
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $url;
 
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $recordId = $queryString['record_id'] ?? null;

        if (empty($recordId)) {
            // No record_id → show no data
            return $query->where(['1 = 0']);
        }

        $record = $this->find()
            ->select(['id', 'parent_id'])
            ->where(['id' => $recordId])
            ->first();

        if (!$record) {
            return $query->where(['1 = 0']);
        }
        $rootId = $record->parent_id ?? $record->id;
        $query
            ->where([
                'OR' => [
                    $this->aliasField('id') => $rootId,
                    $this->aliasField('parent_id') => $rootId
                ],
                $this->aliasField('is_current') => 0
            ])
            ->orderDesc($this->aliasField('created'));

        return $query;
    }

    
}
