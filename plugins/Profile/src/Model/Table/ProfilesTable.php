<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ProfilesTable extends ControllerActionTable {
    // public $InstitutionStudent;

    // these constants are being used in AdvancedPositionSearchBehavior as well
    // remember to check AdvancedPositionSearchBehavior if these constants are being modified
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

    private $dashboardQuery;

    public function initialize(array $config) {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->hasMany('Identities',        ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities',     ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SpecialNeeds',      ['className' => 'User.SpecialNeeds', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts',          ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('User.User');

        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;
        $BaseUsers = TableRegistry::get('User.Users');
        return $BaseUsers->setUserValidation($validator, $this);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $session = $this->request->session();
        $loginUserId = $session->read('Auth.User.id');
        $this->controller->redirect(['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $this->ControllerAction->paramsEncode(['id' => $loginUserId])]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities',
            'MainIdentityTypes',
            'Genders'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Remove back toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupTabElements($entity);
    }

    private function setupTabElements($entity) {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

        $options = [
            // 'userRole' => 'Student',
            // 'action' => $this->action,
            // 'id' => $id,
            // 'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
