<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class AuditTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->hasMany('Identities',        ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities',     ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SpecialNeeds',      ['className' => 'User.SpecialNeeds',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts',          ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Attachments',       ['className' => 'User.Attachments',     'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('BankAccounts',      ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Comments',          ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Languages',         ['className' => 'User.UserLanguages',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Awards',            ['className' => 'User.Awards',          'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->addBehavior('Excel', [
            'excludes' => ['username', 'address', 'postal_code',
                            'address_area_id', 'birthplace_area_id', 'gender_id', 'date_of_birth', 'date_of_death', 'super_admin',
                            'photo_name', 'photo_content',  'photo_name', 'is_student', 'is_staff', 'is_guardian'],
            'pages' => false,
            'autoFields' => false
        ]);

        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onExcelGetStatus(Event $event, Entity $entity)
    {
        if ($entity->status == 1) {
            return __('Active');
        } else {
            return __('Inactive');
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onGetReportName(Event $event, ArrayObject $data)
    {
        return __('Overview');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select([
                'openemis_no' => 'Audit.openemis_no',
                'first_name' => 'Audit.first_name',
                'middle_name' => 'Audit.middle_name',
                'third_name' => 'Audit.third_name',
                'last_name' => 'Audit.last_name',
                'preferred_name' => 'Audit.preferred_name',
                'email' => 'Audit.email',
                'nationality_name' => 'MainNationalities.name',
                'identity_type' => 'MainIdentityTypes.name',
                'identity_number' => 'Audit.identity_number',
                'external_reference' => 'Audit.external_reference',
                'status' => 'Audit.status',
                'last_login' => 'Audit.last_login',
                'preferred_language' => 'Audit.preferred_language'
            ])
            ->contain(['MainNationalities', 'MainIdentityTypes'])
            ->where([$this->aliasField('is_student') => 1]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        foreach ($fields as $key => $field) { 
            if ($field['field'] == 'identity_type_id') { 
                $fields[$key] = [
                    'key' => 'MainIdentityTypes.name',
                    'field' => 'identity_type',
                    'type' => 'string',
                    'label' => __('Main Identity Type')
                ];
            }

            if ($field['field'] == 'nationality_id') { 
                $fields[$key] = [
                    'key' => 'MainNationalities.name',
                    'field' => 'nationality_name',
                    'type' => 'string',
                    'label' => __('Main Nationality')
                ];
            }
        }
    }
}
