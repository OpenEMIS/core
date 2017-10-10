<?php
namespace Institution\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class InfrastructureWashWatersTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_waters');
        parent::initialize($config);

        $this->belongsTo('InfrastructureWashWaterTypes',   ['className' => 'Institution.InfrastructureWashWaterTypes', 'foreign_key' => 'infrastructure_wash_water_type_id']);
        $this->belongsTo('InfrastructureWashWaterFunctionalities',   ['className' => 'Institution.InfrastructureWashWaterFunctionalities', 'foreign_key' => 'infrastructure_wash_water_functionality_id']);
        $this->belongsTo('InfrastructureWashWaterProximities',   ['className' => 'Institution.InfrastructureWashWaterProximities', 'foreign_key' => 'infrastructure_wash_water_proximity_id']);
        $this->belongsTo('InfrastructureWashWaterQuantities',   ['className' => 'Institution.InfrastructureWashWaterQuantities', 'foreign_key' => 'infrastructure_wash_water_quantity_id']);
        $this->belongsTo('InfrastructureWashWaterQualities',   ['className' => 'Institution.InfrastructureWashWaterQualities', 'foreign_key' => 'infrastructure_wash_water_quality_id']);
        $this->belongsTo('InfrastructureWashWaterAccessibilities',   ['className' => 'Institution.InfrastructureWashWaterAccessibilities', 'foreign_key' => 'infrastructure_wash_water_accessibility_id']);
    }

    public function getWaterTypeOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $options = $this->InfrastructureWashWaterTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $options;
    }

    public function getWaterFunctionalityOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $options = $this->InfrastructureWashWaterFunctionalities
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $options;
    }

    public function getWaterProximityOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $options = $this->InfrastructureWashWaterProximities
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $options;
    }

    public function getWaterQuantityOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $options = $this->InfrastructureWashWaterQuantities
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $options;
    }

    public function getWaterQualityOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $options = $this->InfrastructureWashWaterQualities
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $options;
    }

    public function getWaterAccessibilityOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $options = $this->InfrastructureWashWaterAccessibilities
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $options;
    }
}
