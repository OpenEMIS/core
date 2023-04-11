<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\I18n\Time;


//POCOR-7271
class SurveyFiltersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_module_id', ['type' => 'select']);
        $this->setFieldOrder(['name', 'custom_module_id']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_module_id', ['type' => 'select']);
        $this->field('survey_form_id', ['type' => 'select']);
        $this->field('name', ['visible' => true,]);

        $typeOptions = $this->getInstitutionTypeId();
        $this->fields['institution_type_id']['options'] = $typeOptions;
        $this->field('institution_type_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Institution Type')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $institutionProvider = $this->getInstitutionProviderId();
        $this->fields['institution_provider_id']['options'] = $institutionProvider;
        $this->field('institution_provider_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Institution Provider')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);

        $areaEducationId = $this->getAreaEducationId();
        $this->fields['area_education_id']['options'] = $areaEducationId;
        $this->field('area_education_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Area Education')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request)
    {
        $moduleQuery = $this->getModuleQuery();
        $moduleOptions = $moduleQuery->toArray();
        $selectedModule = $this->queryString('module', $moduleOptions);
        $this->advancedSelectOptions($moduleOptions, $selectedModule);
        if ($action == 'edit'){
            $attr['visible'] = true;
            $attr['type'] = 'readonly';
        }else{
            $attr['type'] = 'select';
            $attr['options'] = $moduleOptions;
            $attr['select'] = true;
            $attr['onChangeReload'] = 'changeModule';
            return $attr;
        }
    }

    public function getModuleQuery()
    {
        return $this->CustomModules
            ->find('list')
            ->find('visible');
    }

    public function onUpdateFieldSurveyFormId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit'){
            $attr['visible'] = true;
            $attr['type'] = 'readonly';
        }else{
            $formTable = TableRegistry::get('survey_forms');
            $formOptions = $formTable
                ->find('list')
                ->toArray();
            $attr['type'] = 'select';
            $attr['options'] = $formOptions;
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changeModule';
            return $attr;
        }   
    }

    public function getInstitutionTypeId()
    {
        $TypesTable = TableRegistry::get('Institution.Types');
        $typeOptions = $TypesTable
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();
        return $typeOptions;
    }

    public function getInstitutionProviderId()
    {
        $providerTable = TableRegistry::get('institution_providers');
        $providerOptions = $providerTable
            ->find('list')
            ->where(['visible' => 1])
            ->toArray();
        return $providerOptions;
    }

    public function getAreaEducationId()
    {
        $Areas = TableRegistry::get('Area.Areas');
        $AreasEducationOptions = $Areas
            ->find('list')
            ->where(['visible' => 1])
            ->toArray();
            return $AreasEducationOptions ;
    }


}
