<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InfrastructureCustomFormsTable extends CustomFormsTable
{
    private $modules = ['Infrastructure', 'Room'];

    public function initialize(array $config)
    {
        $config['extra'] = [
            'fieldClass' => [
                'className' => 'Infrastructure.InfrastructureCustomFields',
                'joinTable' => 'infrastructure_custom_forms_fields',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_field_id',
                'through' => 'Infrastructure.InfrastructureCustomFormsFields',
                'dependent' => true
            ],
            'filterClass' => [
                'className' => 'Infrastructure.InfrastructureTypes',
                'joinTable' => 'infrastructure_custom_forms_filters',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_filter_id',
                'through' => 'Infrastructure.InfrastructureCustomFormsFilters',
                'dependent' => true
            ]
        ];
        parent::initialize($config);
        $this->addBehavior('Infrastructure.Pages', ['module' => 'infrastructure']);
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request)
    {
        $selectedModule = !is_null($request->query('module')) ? $request->query('module') : '';
        $module = $this->CustomModules
            ->find()
            ->where([$this->CustomModules->aliasField('id') => $selectedModule])
            ->first();

        $attr['type'] = 'readonly';
        $attr['value'] = $selectedModule;
        $attr['attr']['value'] = $module->name;

        return $attr;
    }

    public function getModuleQuery()
    {
        $query = parent::getModuleQuery();
        if (!empty($this->modules)) {
            $query->where([$this->CustomModules->aliasField('code IN') => $this->modules]);
        }

        return $query;
    }
}
