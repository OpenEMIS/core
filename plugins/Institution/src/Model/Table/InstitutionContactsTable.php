<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionContactsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);

        /**
         * fieldOption tables
         */
        $this->belongsTo('Localities', ['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
        $this->belongsTo('Types', ['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
        $this->belongsTo('Ownerships', ['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
        $this->belongsTo('Statuses', ['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Genders', ['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
        $this->belongsTo('NetworkConnectivities', ['className' => 'Institution.NetworkConnectivities', 'foreignKey' => 'institution_network_connectivity_id']);
        /**
         * end fieldOption tables
         */

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.SystemGroups']);

        $this->behaviors()->get('ControllerAction')->config('fields.excludes', ['area_id', 'institution_provider_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_gender_id', 'institution_network_connectivity_id']);

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('email')
            ->add('email', [
                    'ruleValidEmail' => [
                        'rule' => 'email'
                    ]
            ]);
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldVisible(['view', 'edit'], [
            'contact_person', 'telephone', 'fax', 'email', 'website'
        ]);

        if (isset($extra['toolbarButtons']['list']))
        {
            unset($extra['toolbarButtons']['list']);
        }

    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $url = $this->url('view');
        return $this->controller->redirect($url);
    }

}