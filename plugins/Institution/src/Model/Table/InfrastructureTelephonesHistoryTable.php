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
class InfrastructureTelephonesHistoryTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_telephones');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityTelephoneTypes',   ['className' => 'Institution.UtilityTelephoneTypes', 'foreign_key' => 'utility_telephone_type_id']);
        $this->belongsTo('UtilityTelephoneConditions',   ['className' => 'Institution.UtilityTelephoneConditions', 'foreign_key' => 'utility_telephone_condition_id']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);

    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityTelephones';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
        $this->field('start_date',['visible' => true]);
        $this->field('end_date',['visible' => true]);
        $this->field('is_current',['visible' => false]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('utility_telephone_type_id ', ['attr' => ['label' => __('Type')]]);
        $this->field('utility_telephone_condition_id', ['attr' => ['label' => __('Condition')]]);
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('comment',['visible' => true]);

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InfrastructureUtilityTelephones',
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
        $academicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        if($academicPeriodId == null){
           $academicPeriodId =  $academicPeriod->getCurrent();
        }
        $query
            ->where([
                $this->aliasField('academic_period_id IS') => $academicPeriodId,
                $this->aliasField('is_current') => 0
            ])->orderDesc($this->aliasField('created'));

        return $query;
    }

}
