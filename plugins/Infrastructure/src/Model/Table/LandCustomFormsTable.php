<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Http\ServerRequest;
use Cake\Event\Event;

class LandCustomFormsTable extends CustomFormsTable
{
    public function initialize(array $config): void
    {
        $config['extra'] = [
            /*'fieldClass' => [
                'className' => 'Infrastructure.LandCustomFields',
                'joinTable' => 'infrastructure_custom_forms_fields',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_field_id',
                'through' => 'Infrastructure.InfrastructureCustomFormsFields',
                'dependent' => true
            ],*/
            'filterClass' => [
                'className' => 'Infrastructure.LandTypes',
                'joinTable' => 'infrastructure_custom_forms_filters',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_filter_id',
                'through' => 'Infrastructure.LandCustomFormsFilters',
                'dependent' => true
            ]
        ];
        // InfrastructureCustomFormsFieldsTable.php
        $this->belongsToMany('CustomFields', [
            'className' => 'Infrastructure.LandCustomFields',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_form_id',
            'targetForeignKey' => 'infrastructure_custom_field_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);

        // LandCustomFormsTable.php
        $this->belongsToMany('CustomFields', [
            'className' => 'Infrastructure.LandCustomFields',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_form_id',
            'targetForeignKey' => 'infrastructure_custom_field_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);

        $this->setTable('infrastructure_custom_forms');
        parent::initialize($config);
        $this->addBehavior('Infrastructure.Pages', ['module' => 'Land']);
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'custom_module_id') {
            return __('Custom Module');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'selected_custom_field') {
            return __('Add Field');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'apply_to_all') {
            return __('Apply to All');
        } elseif ($field == 'custom_filter_id') {
            return __('Custom Filter'); 
        }elseif ($field == 'sectiontxt') {
            return __('Add Section');  
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'custom_module_id') {
            return __('Custom Module');
        } elseif ($field == 'staff_custom_field_id') {
            return __('Custom Fields');
        }elseif ($field == 'to_be_deleted') {
            return __('To be Deleted ');
        }elseif ($field == 'associated_records') {
            return __('Associated Records');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
