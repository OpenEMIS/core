<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;

class CountriesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('countries');
        parent::initialize($config);

        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices']);
        
        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('cascade');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->notEmpty('name', 'Please enter a name.');

        return $validator;
    }

    public function findOptionList(Query $query, array $options)
    {
        if(array_key_exists('location_type', $options['querystring'])) {
            
            $locationType = $options['querystring']['location_type'];
            
            $AreaAdministratives = TableRegistry::get('Area.AreaAdministratives');
            $domesticCountry = $AreaAdministratives
                ->find()
                ->where([$AreaAdministratives->aliasField('is_main_country') => 1])
                ->extract('name')
                ->first();

            if($locationType == 'DOMESTIC') {
                $query->where([$this->aliasField('name') => $domesticCountry]);
            } elseif ($locationType == 'INTERNATIONAL') {
                $query->where([$this->aliasField('name <>') => $domesticCountry]);
            }
        }
        
        return parent::findOptionList($query, $options);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
