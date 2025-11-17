<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Utility\Text;
use App\Model\Table\AppTable;

class InstitutionTripDaysTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionTrips', ['className' => 'Institution.InstitutionTrips']);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
    	if ($entity->isNew()) {
			$entity->id = Text::uuid();
    	}
    }
}
