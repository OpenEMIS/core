<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;//POCOR-6255 
use App\Model\Table\AppTable;

class UserInsurancesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('user_insurances');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('InsuranceProviders', ['className' => 'Health.InsuranceProviders', 'foreignKey' => 'insurance_provider_id']);
        $this->belongsTo('InsuranceTypes', ['className' => 'Health.InsuranceTypes', 'foreignKey' => 'insurance_type_id']);
        //POCOR-6255 start
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);//POCOR-6255 end
    }
    //POCOR-6255 start
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }//POCOR-6255 end

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
        ;
    }
}
