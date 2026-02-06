<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Query;

class LandCustomFormsTable extends CustomFormsTable
{
    public function initialize(array $config): void
    {
        $config['extra'] = [
            'fieldClass' => [
                'className' => 'Infrastructure.LandCustomFields',
                'joinTable' => 'infrastructure_custom_forms_fields',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_field_id',
                'through' => 'Infrastructure.LandCustomFormsFields',
                'dependent' => true
            ],
            'filterClass' => [
                'className' => 'Infrastructure.LandTypes',
                'joinTable' => 'infrastructure_custom_forms_filters',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_filter_id',
                'through' => 'Infrastructure.LandCustomFormsFilters',
                'dependent' => true
            ]
        ];
        $this->setTable('infrastructure_custom_forms');
        parent::initialize($config);
        $this->addBehavior('Infrastructure.Pages', ['module' => 'Land']);
        $this->setDeleteStrategy('restrict');
    }

    public function onUpdateFieldCustomModuleId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $selectedModule = !is_null($this->request->getQuery('module')) ? $this->request->getQuery('module') : '';
        $tableLocator = new TableLocator();
        $InfrastructureCustomForms = $tableLocator->get('InfrastructureCustomForms');
        if($selectedModule == null){
            $paramsPass = $this->request->getAttribute('params')['pass'][1];
            $ModuleId = $this->paramsDecode($paramsPass)['id'];
            $selectedModule = $InfrastructureCustomForms->find()->where([$InfrastructureCustomForms->aliasField('id') => $ModuleId])->first()->custom_module_id;
        }
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

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //echo "<pre>"; print_r($query->toArray());die;
    }

}
