<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use Cake\Network\Session;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Institution\Model\Behavior\LatLongBehavior as LatLongOptions;

class InstitutionStatusTable extends ControllerActionTable
{ 
	 use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
    	$this->field('modified', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('security_group_id', ['visible' => false]);
        $this->field('institution_locality_id', ['visible' => false]);
        $this->field('institution_ownership_id', ['visible' => false]);
        $this->field('institution_provider_id', ['visible' => false]);
        $this->field('institution_sector_id', ['visible' => false]);
        $this->field('institution_type_id', ['visible' => false]);
        $this->field('institution_gender_id', ['visible' => false]);
        $this->field('shift_type', ['visible' => false]);
        $this->field('area_administrative_id', ['visible' => false]);
        $this->field('area_id', ['visible' => false]);
        $this->field('contact_section', ['visible' => false]);
        $this->field('contact_person', ['visible' => false]);
        $this->field('telephone', ['visible' => false]);
        $this->field('fax', ['visible' => false]);
        $this->field('email', ['visible' => false]);
        $this->field('website', ['visible' => false]);
        $this->field('longitude', ['visible' => ['view' => false]]);
        $this->field('latitude', ['visible' => ['view' => false]]);
        $this->field('postal_code', ['visible' => false]);
        $this->field('alternative_name', ['visible' => false]);
        $this->field('year_opened', ['visible' => false]);
        $this->field('year_closed', ['visible' => ['view' => false]]);
        $this->field('address', ['visible' => ['view' => false]]);
        $this->field('logo_name', ['visible' => false]);
        $this->field('logo_content', ['visible' => ['view' => false]]);
        $this->field('classification', ['visible' => ['view' => false]]);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'date_opened', 'date_closed', 'institution_status_id',
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
		$this->setFieldOrder([
           'name', 'code', 'date_opened', 'date_closed', 'institution_status_id',
        ]);
    }

    public function onUpdateFieldInstitutionStatusId(Event $event, array $attr, $action, Request $request)
    {die("DDD");
        if ($action == 'add') {
            $attr['visible'] = false;
        }

        if ($action == 'edit') {
            $attr['visible'] = true;
        }

        return $attr;
    }

}