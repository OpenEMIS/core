<?php 
namespace OpenEmis\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

/**
 * OpenEmis MapBehavior
 *
 * This file is to render input element as a map implemented as Embedded Google Map without using Google Map Api.
 * *Depends on ControllerAction Component
 *
 * Usage:
 * Firstly, add this behavior in model's initialize function.
 * 
 * public function initialize(array $config) {
 *      .............
 *      
 *      $this->addBehavior('OpenEmis.Map');
 *      
 *      .............
 * }
 *
 * 
 * Secondly, defines the field in model's beforeAction()
 *
 *  public function beforeAction($event) {
 *      .............
 *      
 *      $this->ControllerAction->field('map', ['type' => 'map']);
 *      
 *      .............
 *  }
 *
 * If the $entity does not have 'latitude' and 'longitude' properties,
 * you have to define it as the field $attr before ControllerAction events are called or do it during field declaration.
 * 
 *      $this->ControllerAction->field('map', ['type' => 'map', 'latitude' => $latitude_val, 'longitude' => $longitude_val]);
 *
 * If the $entity do have the required properties but 'latitude' and 'longitude' parameters are defined in the field declaration, 
 * values in the field declaration will be shown.
 * 
 */
class MapBehavior extends Behavior
{
    public function implementedEvents() 
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.view.beforeAction' => 'viewBeforeAction',
            'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
            'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
            'ControllerAction.Model.edit.beforeAction' => 'editBeforeAction'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function indexBeforeAction(Event $event)
    {
        $this->_fieldSetup();
    }

    public function viewBeforeAction(Event $event)
    {
        $this->_fieldSetup();
    }

    public function addBeforeAction(Event $event)
    {
        $this->_fieldSetup();
    }

    public function editBeforeAction(Event $event)
    {
        $this->_fieldSetup();
    }

    private function _fieldSetup()
    {
        foreach ($this->_table->fields as $key=>$value) {
            if ($value['type'] == 'map') {
                $this->_table->fields[$key]['override'] = true;
                $this->_table->fields[$key]['label'] = false;
            }
        }
    }

    public function onGetMapElement(Event $event, $action, Entity $entity, $attr, $options) 
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $mapZoom = $ConfigItems->value('map_zoom');

        // map configuration for kdx-map
        $mapConfig = [
            'zoom' => [
                'value' => $mapZoom,
                'isZoomButton' => true,
                'isScrollZoom' => true,
                'isTouchZoom' => true
            ],
            'attribution' => 'OpenEMIS',
            'type' => 'basic'
        ];

        if (array_key_exists('latitude', $attr) && array_key_exists('longitude', $attr)) {
            $mapPosition = [
                'lng' => $attr['longitude'],
                'lat' => $attr['latitude']
            ];
            $attr['mapConfig'] = json_encode($mapConfig);
            $attr['mapPosition'] = json_encode($mapPosition);

            return $event->subject()->renderElement('OpenEmis.map', ['attr' => $attr]);
        }

        if ($entity->latitude!='' && $entity->longitude!='') {
            $mapPosition = [
                'lng' => $entity->longitude,
                'lat' => $entity->latitude
            ];
            $attr['mapConfig'] = json_encode($mapConfig);
            $attr['mapPosition'] = json_encode($mapPosition);
            
            return $event->subject()->renderElement('OpenEmis.map', ['attr' => $attr]);
        }

        return '<span class="error-message">' . __('Both latitude and longitude value have to be set for map to render') . '<span>';
    }

}
