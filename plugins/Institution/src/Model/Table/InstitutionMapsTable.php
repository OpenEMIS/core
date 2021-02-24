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

class InstitutionMapsTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);

        $this->addBehavior('CustomField.Record', [
            'fieldKey' => 'institution_custom_field_id',
            'tableColumnKey' => 'institution_custom_table_column_id',
            'tableRowKey' => 'institution_custom_table_row_id',
            'fieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFields'],
            'formKey' => 'institution_custom_form_id',
            'filterKey' => 'institution_custom_filter_id',
            'formFieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFields'],
            'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
            'recordKey' => 'institution_id',
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
        $this->addBehavior('Year', ['date_opened' => 'year_opened', 'date_closed' => 'year_closed']);
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionActivities', 'key' => 'institution_id', 'session' => 'Institution.Institutions.id']);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('OpenEmis.Map');
        $this->addBehavior('Institution.LatLong');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator = $this->LatLongValidation();
        return $validator;
    }


    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($extra['toolbarButtons']['back']['url']['action'] == 'InstitutionMaps') {
            //hiding all unwanted data
            $this->field('security_group_id', ['visible' => false]);
            $this->field('name', ['visible' => false]);
            $this->field('code', ['visible' => false]);
            $this->field('address', ['visible' => false]);
            $this->field('alternative_name', ['visible' => false]);

            $this->field('postal_code', ['visible' => false]);
            $this->field('year_opened', ['visible' => false]);
            $this->field('year_closed', ['visible' => false]);
            $this->field('logo_name', ['visible' => false]);
            $this->field('logo_content', ['visible' => false]);
            $this->field('classification', ['visible' => false]);

            $this->field('date_opened', ['visible' => false]);
            $this->field('date_closed', ['visible' => false]);
            $this->field('modified', ['visible' => false]);
            $this->field('modified_user_id', ['visible' => false]);
            $this->field('created', ['visible' => false]);
            $this->field('created_user_id', ['visible' => false]);

            $this->field('institution_locality_id', ['visible' => false]);
            $this->field('institution_ownership_id', ['visible' => false]);
            $this->field('institution_status_id', ['visible' => false]);
            $this->field('institution_sector_id', ['visible' => false]);
            
            $this->field('institution_provider_id', ['visible' => false]);

            $this->field('institution_type_id', ['visible' => false]);
            $this->field('institution_gender_id', ['visible' => false]);
            $this->field('area_administrative_id', ['visible' => false]);
            $this->field('area_id', ['visible' => false]);
            $this->field('contact_section', ['visible' => false]);
            $this->field('contact_person', ['visible' => false]);
            $this->field('telephone', ['visible' => false]);
            $this->field('fax', ['visible' => false]);
            $this->field('email', ['visible' => false]);
            $this->field('website', ['visible' => false]);

            $this->field('shift_type', ['visible' => ['view' => false]]);

            $this->field('shift_details', [
                'type' => 'element',
                'element' => 'Institution.Shifts/details',
                'visible' => ['view'=>false],
            ]);
            //hiding all unwanted data

            $this->field('location_section', ['type' => 'section', 'title' => __('Location')]);

            $this->field('map_section', ['type' => 'section', 'title' => __('Map'), 'visible' => ['view'=>true]]);
            $this->field('google_maps', ['visible' => ['view'=>true]]);
            $this->field('map', ['type' => 'map', 'visible' => ['view'=>true]]);

            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $LatLongPermission = $ConfigItems->value("latitude_longitude");
            
            if ($LatLongPermission == LatLongOptions::EXCLUDED) {
                $this->field('longitude', ['visible' => false]);
                $this->field('latitude', ['visible' => false]);
            }
        }
    }

	public function beforeSave(Event $event, Entity $entity, ArrayObject $extra) {
		        
		$ConfigItems = TableRegistry::get('config_items');

		$latitudeData = $ConfigItems->find()
            ->select([
                $ConfigItems->aliasField('value'),
                $ConfigItems->aliasField('default_value'),
               ])
			   ->where([
					$ConfigItems->aliasField('code') => 'latitude_length',
				])
			->first();
			
		$longitudeData = $ConfigItems->find()
            ->select([
                $ConfigItems->aliasField('value'),
                $ConfigItems->aliasField('default_value'),
               ])
			   ->where([
					$ConfigItems->aliasField('code') => 'longitude_length',
				])
			->first();	
		
        if (!empty($entity->latitude)) {
			$latitude = explode(".",$entity->latitude);
			$latitude_length = strlen($latitude[1]);
			
			$default_length = 0;
			if (!empty($latitudeData->value)) {
				$default_length = $latitudeData->value;
			} else {
				$default_length = $latitudeData->default_value;
			}
			
			if($latitude_length > $default_length) {
				$latitude[1] = substr($latitude[1], 0, $default_length);
			}
			if($latitude_length < $default_length) {
				$latitude[1] = $latitude[1].str_repeat('0', $default_length-$latitude_length);
			}
			
			$latitude = implode(".",$latitude);
			$entity->latitude = $latitude;
        }  
		
        if (!empty($entity->longitude)) {
			$longitude = explode(".",$entity->longitude);
			$longitude_length = strlen($longitude[1]);
			
			$default_length = 0;
			if (!empty($longitudeData->value)) {
				$default_length = $longitudeData->value;
			} else {
				$default_length = $longitudeData->default_value;
			}
			
			if($longitude_length > $default_length) {
				$longitude[1] = substr($longitude[1], 0, $default_length);
			}
			if($longitude_length < $default_length) {
				$longitude[1] = $longitude[1].str_repeat('0', $default_length-$longitude_length);
			}
			
			$longitude = implode(".",$longitude);
			$entity->longitude = $longitude;
        }       

    } 
	
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([

            'location_section',
            'latitude', 'longitude',

            'map_section',
            'google_maps',
            'map',
        ]);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'location_section',
            'latitude', 'longitude',
        ]);
    }

    public function onGetGoogleMaps(Event $event, Entity $entity)
    {  
        $ControllerActionHelper = $event->subject();
        $htmlHelper = $event->subject()->Html;
        $longitude  = $entity->longitude;
        $latitude   = $entity->latitude;
        $address    = "https://maps.google.com/?q=". $latitude . ',' . $longitude;
        $url        = json_encode(trim($address), JSON_FORCE_OBJECT);
        
        return $htmlHelper->tag(__('a href='. $url .' target="_blank"> Open External Link</a'));
    }
}

