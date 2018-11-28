<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;
use Directory\Model\Table\DirectoriesTable as UserTable;

class StudentUserTable extends UserTable
{
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $extra['toolbarButtons']['back']['url']['action'] = 'ProfileStudents';
    }
}
