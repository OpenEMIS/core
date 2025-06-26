<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Http\ServerRequest;
use Cake\Event\Event;

class FloorCustomFormsTable extends CustomFormsTable
{
    public function initialize(array $config): void
    {
        /*$config['extra'] = [
            'fieldClass' => [
                'className' => 'Infrastructure.FloorCustomFields',
                'joinTable' => 'infrastructure_custom_forms_fields',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_field_id',
                'through' => 'Infrastructure.InfrastructureCustomFormsFields',
                'dependent' => true
            ],
            'filterClass' => [
                'className' => 'Infrastructure.FloorTypes',
                'joinTable' => 'infrastructure_custom_forms_filters',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_filter_id',
                'through' => 'Infrastructure.FloorCustomFormsFilters',
                'dependent' => true
            ]
        ];*/

        $this->belongsToMany('FloorCustomFields', [
            'className' => 'Infrastructure.FloorCustomFields',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_form_id',
            'targetForeignKey' => 'infrastructure_custom_field_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);
        $this->setTable('infrastructure_custom_forms');
        parent::initialize($config);
        $this->addBehavior('Infrastructure.Pages', ['module' => 'Floor']);
        $this->setDeleteStrategy('restrict');
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $selectedModule = !is_null($request->getQuery('module')) ? $request->getQuery('module') : '';
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
        if (!empty($this->getModules())) {
            $query->where([$this->CustomModules->aliasField('code IN') => $this->getModules()]);
        }
        return $query;
    }
}
