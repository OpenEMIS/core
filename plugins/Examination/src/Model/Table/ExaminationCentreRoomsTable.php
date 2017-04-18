<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Controller\Component;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Traits\HtmlTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class ExaminationCentreRoomsTable extends ControllerActionTable {
    use HtmlTrait;

    private $examCentreId = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsToMany('Examinations', [
            'className' => 'Examination.Examinations',
            'joinTable' => 'examination_centre_rooms_examinations',
            'foreignKey' => 'examination_centre_room_id',
            'targetForeignKey' => 'examination_id',
            'through' => 'Examination.ExaminationCentreRoomsExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_centre_id']]],
                'provider' => 'table'
            ])
            ->allowEmpty('size')
            ->add('size', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ])
            ->add('size', 'ruleRoomSize',  [
                'rule'  => ['range', 0, 2147483647]
            ])
            ->allowEmpty('number_of_seats')
            ->add('number_of_seats', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ])
            ->add('number_of_seats', 'ruleSeatsNumber',  [
                'rule'  => ['range', 0, 2147483647]
            ])
            ->add('number_of_seats', 'ruleExceedRoomCapacity', [
                'rule' => 'validateRoomCapacity'
            ]);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $examCentreId = $entity->examination_centre_id;
        $listeners = [TableRegistry::get('Examination.ExaminationCentreRoomsExaminations')];
        $this->dispatchEventToModels('Model.ExaminationCentreRooms.afterSave', [$entity], $this, $listeners);
    }
}
