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

class InfrastructureInternetsTable extends AppTable
{

    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_internets');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'joinType' => 'INNER']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'joinType' => 'INNER']);
        $this->belongsTo('UtilityInternetBandwidths', [
            'className' => 'Institution.UtilityInternetBandwidths',
            'foreignKey' => 'utility_internet_bandwidth_id'
        ]);

        $this->belongsTo('UtilityInternetTypes', [
            'className' => 'Institution.UtilityInternetTypes',
            'foreignKey' => 'utility_internet_type_id'
        ]);

        $this->belongsTo('UtilityInternetConditions', [
            'className' => 'Institution.UtilityInternetConditions',
            'foreignKey' => 'utility_internet_condition_id'
        ]);
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
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        $selectedArea = $requestData->area_education_id;

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if ($institutionId != 0) {
            $conditions['Institutions.id'] = $institutionId;
        }
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
                $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }

         $query
        ->select([
            'academic_period' => 'AcademicPeriods.name',
            'institution_code' => 'Institutions.code',
            'institution_name' => 'Institutions.name',
            'internet_purpose' => $this->aliasField('internet_purpose'),
            'internet_bandwidth' => 'UtilityInternetBandwidths.name',
            'internet_type' => 'UtilityInternetTypes.name',
            'internet_condition' => 'UtilityInternetConditions.name',
            'comment' => $this->aliasField('comment')
        ])
        ->contain([
            'AcademicPeriods',
            'Institutions',
            'UtilityInternetBandwidths',
            'UtilityInternetTypes',
            'UtilityInternetConditions'
        ])
        ->where($conditions);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($type) {
            return $results->map(function ($row) {
                if($row['internet_purpose'] == 1){
                    $row['internet_purpose'] = 'Teaching';
                }elseif($row['internet_purpose'] == 2){
                    $row['internet_purpose'] = 'Non-Teaching';
                }

            return $row;
            });
        });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $newFields = [];

         $newFields[] = [
            'key' => 'academic_period',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newFields[] = [
            'key' => 'internet_purpose',
            'field' => 'internet_purpose',
            'type' => 'string',
            'label' => __('Internet Purpose')
        ];

        $newFields[] = [
            'key' => 'internet_bandwidth',
            'field' => 'internet_bandwidth',
            'type' => 'string',
            'label' => __('Internet Bandwidth')
        ];

        $newFields[] = [
            'key' => 'internet_type',
            'field' => 'internet_type',
            'type' => 'string',
            'label' => __('Internet Types')
        ];

        $newFields[] = [
            'key' => 'internet_condition',
            'field' => 'internet_condition',
            'type' => 'string',
            'label' => __('Internet Condition')
        ];
        $newFields[] = [
            'key' => 'comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
      
        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ])
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

}
