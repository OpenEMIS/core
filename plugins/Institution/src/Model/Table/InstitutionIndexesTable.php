<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\ControllerActionTable;

class InstitutionIndexesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Indexes', ['className' => 'Institution.Indexes', 'foreignKey' =>'index_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
    }

    public function findRecord(Query $query, array $options)
    {
        return $query->where([
            'index_id' => $options['index_id'],
            'institution_id' => $options['institution_id']
        ]);
    }

    public function getStatus($indexId, $institutionId)
    {
        $record = $this->find()
            ->where([
                'index_id' => $indexId,
                'institution_id' => $institutionId
            ])
            ->first();

        if (isset($record->status)) {
            return $record->status;
        }
    }
}
