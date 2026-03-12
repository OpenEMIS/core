<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Database\Expression\QueryExpression;

/**
 * Data processing for generating 
 * POCOR-9562
 */

class InfrastructureElectricitiesTable extends AppTable
{

    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_electricities');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'joinType' => 'INNER']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'joinType' => 'INNER']);
        $this->belongsTo('UtilityElectricityTypes', ['className' => 'Institution.UtilityElectricityTypes']);
        $this->belongsTo('UtilityElectricityConditions', ['className' => 'Institution.UtilityElectricityConditions']);
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Education','required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $options = $this->controller->getFeatureOptions($this->getAlias());
        $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($options);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
        return $attr;
    }

     public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id; //POCOR-7794
    }
       
    

}
