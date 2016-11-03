<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class CompetencySetsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('competency_sets');
        parent::initialize($config);
        $this->hasMany('StaffAppraisals', ['className' => 'Staff.StaffAppraisals']);

        $this->belongsToMany('Competencies', [
            'className' => 'Staff.Competencies',
            'joinTable' => 'competency_set_competencies',
            'foreignKey' => 'competency_set_id',
            'targetForeignKey' => 'competency_id',
            'through' => 'Staff.CompetencySetsCompetencies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        //this will exclude checking during remove restrict
        $extra['excludedModels'] = [
            $this->Competencies->alias()
        ];
    }

    public function beforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
        $this->field('competencies', ['after' => 'visible']);
    }

    public function indexBeforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Competencies']);
    }

    public function onUpdateFieldCompetencies(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'add':
            case 'edit':
                $competencyOptions = $this->Competencies
                    ->find('list')
                    ->select([$this->Competencies->aliasField($this->Competencies->primaryKey()), $this->Competencies->aliasField('name')])
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $competencyOptions;
                break;
            default:
                # code...
                break;
        }

        return $attr;
    }

    public function onGetCompetencies(Event $event, Entity $entity)
    {
        if (!$entity->has('competencies')) {
            $query = $this->find()
            ->where([$this->aliasField($this->primaryKey()) => $entity->id])
            ->contain(['Competencies'])
            ;
            $data = $query->first();
        }
        else {
            $data = $entity;
        }

        $competency = [];
        if ($data->has('competencies')) {
            foreach ($data->competencies as $key => $value) {
                $competency[] = $value->name;
            }
        }

        return (!empty($competency))? implode(', ', $competency): ' ';
    }

    public function findCompetencySetsOptions(Query $query, array $options)
    {
        return $query
            ->find('list')
            ->find('visible')
            ->order([$this->aliasField('order')])
        ;
    }
}
