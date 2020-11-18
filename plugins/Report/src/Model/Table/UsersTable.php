<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;

class UsersTable extends AppTable
{
    const NO_FILTER = 0;
    const STUDENT = 1;
    const STAFF = 2;
    
    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('UsersExcel', [
            'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event) 
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) 
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelGetUserTypeStudent(Event $event, Entity $entity)
    {
        return 'Student';
    }

    public function onExcelGetUserTypeStaff(Event $event, Entity $entity)
    {
        return 'Staff';
    }

    public function onExcelGetUserTypeGuardian(Event $event, Entity $entity)
    {
        return 'Guardian';
    }

    public function onExcelGetUserTypeOthers(Event $event, Entity $entity)
    {
        return 'Others';
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {  
        $cloneFields = $fields->getArrayCopy();
        $extraFields = [];
        $fields->exchangeArray($extraFields);
    }
}
