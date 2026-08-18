<?php
namespace Counselling\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;

use App\Model\Table\AppTable;

class CounsellingsTable extends AppTable
{
    const ASSIGNED = 1;

    public function initialize(array $config): void
    {
       
        $this->setTable('counsellings');
        parent::initialize($config);

        // POCOR-9771: a counselling record can have multiple guidance types, via the
        // counselling_guidance_types join table (replaces the old single guidance_type_id FK).
        $this->belongsToMany('GuidanceTypes', [
            'className' => 'Student.GuidanceTypes',
            'joinTable' => 'counselling_guidance_types',
            'foreignKey' => 'counselling_id',
            'targetForeignKey' => 'guidance_type_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->belongsTo('Counselors', ['className' => 'Security.Users', 'foreign_key' => 'counselor_id']);
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
        $this->Users = TableRegistry::getTableLocator()->get('Security.Users');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(EventInterface $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }

    //POCOR-9771: the Page component's autoContains() only picks up belongsTo (manyToOne)
    //associations, so GuidanceTypes (belongsToMany) needs to be contained here instead — this
    //runs on every find() and merges with whatever contain the Page component already set.
    public function beforeFind(EventInterface $event, Query $query, \ArrayObject $options, $primary)
    {
        $query->contain(['GuidanceTypes']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator->allowEmpty('file_content');
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function getGuidanceTypesOptions($institutionId)
    {
        // should be auto, if auto the reorder and visible not working
        $guidanceTypesOptions = $this->GuidanceTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $guidanceTypesOptions;
    }

    public function getCounselorOptions()
    {
        $counselorOptions = $this->Users
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->where([
                $this->Users->aliasField('status') => 1
            ])
            ->order([
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('last_name')
            ])
            ->toArray();

        return $counselorOptions;
    }

}
