<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class BodyMassTable extends AppTable  
{
    public function initialize(array $config) 
    {
        $this->table('user_body_masses');
        parent::initialize($config);
        
        // Associations
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        
        // Behaviors
        $this->addBehavior('Excel', [
            'excludes' => [
                'academic_period_id', 'security_user_id'
            ],
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select([
                $this->aliasField('date'),                
                $this->aliasField('height'),
                $this->aliasField('weight'),
                $this->aliasField('body_mass_index'),
                $this->aliasField('comment'),  
                $this->aliasField('security_user_id'),                              
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'Users.preferred_name'
                    ]
                ]
            ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields = [];

        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'BodyMassIndex.full_name',
            'field' => 'security_user_id',
            'type' => 'integer',
            'label' => ''
        ];          

        $newFields = array_merge($extraFields,$cloneFields);
        $fields->exchangeArray($newFields);  
    }

}
