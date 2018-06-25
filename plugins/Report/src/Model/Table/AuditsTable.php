<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

use Directory\Model\Table\DirectoriesTable as UserTypeOption;

class AuditsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);
        $this->hasMany('Identities', ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Attachments', ['className' => 'User.Attachments',     'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Languages', ['className' => 'User.UserLanguages',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Awards', ['className' => 'User.Awards',          'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Logins', ['className' => 'SSO.SecurityUserLogins', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Counsellings', ['className' => 'Counselling.Counsellings', 'foreignKey' => 'counselor_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('BodyMasses', ['className' => 'User.UserBodyMasses', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        
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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        if (array_key_exists('feature', $context['data'])) {
                            $feature = $context['data']['feature'];
                            return in_array($feature, [
                                'Report.AuditsLogin',
                                'Report.AuditInstitution',
                                'Report.AuditUser'
                            ]);         
                        }
                        return true;
                    }
                ],
            ]);

        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('user_type', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date');
        $this->ControllerAction->field('report_end_date');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.AuditUser'])) {
                $userTypeOptions = [
                    UserTypeOption::ALL => __('All Type'),
                    UserTypeOption::STUDENT => __('Student'),
                    UserTypeOption::STAFF => __('Staff'),
                    UserTypeOption::GUARDIAN => __('Guardian')
                ];

                $attr['options'] = $userTypeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
            }

            return $attr;
        }
    }

    public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'date';
        return $attr;
    }

    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'date';
        $attr['value'] = Time::now();
        return $attr;
    }
}
