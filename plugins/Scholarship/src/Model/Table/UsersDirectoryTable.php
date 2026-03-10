<?php
namespace Scholarship\Model\Table;
//POCOR-9435 upgraded to cakephp4
use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;
use Cake\ORM\ResultSetInterface;

class UsersDirectoryTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);
       
        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->setEntityClass('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('OpenEmis.Section');
    }
    
    public function findIndex(Query $query, array $options)
    {
        return $query->where([$this->aliasField('super_admin') => 0]);
    }

    public function findSearch(Query $query, array $options)
    {
        $searchOptions = $options['search'];
        $searchOptions['defaultSearch'] = false; // turn off defaultSearch function in page

        $search = $searchOptions['searchText'];
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['searchTerm' => $search]);
        }
        return $query;
    }
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('name', ['displayFrom' => 'name']);
        $this->field('date_of_birth');
        $this->field('gender_id');
        $this->field('identity_type_id');
        $this->field('identity_number');
        $this->field('failed_logins');

        $this->setFieldOrder([
            'openemis_no',
            'name',
            'date_of_birth',
            'gender_id',
            'identity_type_id',
            'identity_number',
            'failed_logins'
        ]);
        $this->setFieldVisible(['index'], [
            'openemis_no',
            'name',
            'date_of_birth',
            'gender_id',
            'identity_type_id',
            'identity_number',
            'failed_logins'
        ]);

        // Toolbar: Back to Applications list (preserve queryString if present)
        $extra['toolbarButtons']['back'] = [
            'url' => [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Applications',
                'index'
            ],
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => [
                'class' => 'btn btn-default icon-big',
                'escape' => false,
                'title' => __('Back')
            ]
        ];
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // Section header
        $this->field('information_section', [
            'type' => 'section',
            'title' => __('Information')
        ]);

        $this->field('photo_content', ['type' => 'image']);
        $this->field('openemis_no');
        $this->field('username');
        $this->field('first_name');
        $this->field('middle_name');
        $this->field('third_name');
        $this->field('last_name');
        $this->field('preferred_name');
        $this->field('gender_id');
        $this->field('date_of_birth');
        $this->field('email');
        $this->field('mobile_number');
        $this->field('address');
        $this->field('address_area_id');
        $this->field('postal_code');
        $this->field('birthplace_area_id');
        $this->field('nationality_id', ['displayFrom' => 'main_nationality.name']);
        $this->field('identity_type_id');
        $this->field('identity_number');
        $this->field('failed_logins');

        $this->setFieldOrder([
            'information_section',
            'photo_content',
            'openemis_no',
            'username',
            'first_name',
            'middle_name',
            'third_name',
            'last_name',
            'preferred_name',
            'gender_id',
            'date_of_birth',
            'email',
            'mobile_number',
            'address',
            'address_area_id',
            'postal_code',
            'birthplace_area_id',
            'nationality_id',
            'identity_type_id',
            'identity_number',
            'failed_logins'
        ]);
        $this->setFieldVisible(['view'], [
            'information_section',
            'photo_content',
            'openemis_no',
            'username',
            'first_name',
            'middle_name',
            'third_name',
            'last_name',
            'preferred_name',
            'gender_id',
            'date_of_birth',
            'email',
            'mobile_number',
            'address',
            'address_area_id',
            'postal_code',
            'birthplace_area_id',
            'nationality_id',
            'identity_type_id',
            'identity_number',
            'failed_logins'
        ]);

        // Add Apply button to the toolbar on view screen
        $ids = [];
        if ($this->paramsPass(0)) {
            $ids = $this->paramsDecode($this->paramsPass(0));
        }
        $applicantId = $ids['id'] ?? $ids['security_user_id'] ?? null;

        if (!is_null($applicantId)) {
            // Skip Apply for super admin
            if (!empty($ids['super_admin']) && $ids['super_admin']) {
                return;
            }

            $applyUrl = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Applications',
                'add'
            ];
            $extra['toolbarButtons']['apply'] = [
                'url' => $this->ControllerAction->setQueryString($applyUrl, ['applicant_id' => $applicantId]),
                'type' => 'button',
                'label' => '<i class="fa kd-add"></i>',
                'attr' => [
                    'class' => 'btn btn-default icon-big',
                    'escape' => false,
                    'title' => __('Apply')
                ]
            ];
        }

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Genders',
            'AddressAreas',
            'BirthplaceAreas',
            'MainNationalities',
            'MainIdentityTypes'
        ]);

        // Exclude super admins explicitly (some records may have null/legacy values)
        $query->where([$this->aliasField('super_admin') => 0]);
        
        return $query;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'openemis_no':
                return __('OpenEMIS ID');
            case 'first_name':
                return __('First Name');
            case 'middle_name':
                return __('Middle Name');
            case 'third_name':
                return __('Third Name');
            case 'last_name':
                return __('Last Name');
            case 'preferred_name':
                return __('Preferred Name');
            case 'date_of_birth':
                return __('Date Of Birth');
            case 'gender_id':
                return __('Gender');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // Only show view + apply (no edit/remove) and carry applicant id in query string.
        unset($buttons['edit'], $buttons['remove']);

        $queryParams = ['applicant_id' => $entity->id];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $queryParams);
        }

        // Hide Apply for super admins
        if (empty($entity->super_admin)) {
            $applyUrl = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Applications',
                'add'
            ];

            $buttons['apply'] = [
                'label' => '<i class="fa kd-add"></i>' . __('Apply'),
                'url' => $this->ControllerAction->setQueryString($applyUrl, $queryParams),
                'attr' => [
                    'role' => 'menuitem',
                    'tabindex' => '-1',
                    'escape' => false
                ]
            ];
        }

        return $buttons;
    }
}
