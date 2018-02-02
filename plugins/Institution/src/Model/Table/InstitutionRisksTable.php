<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\ControllerActionTable;

class InstitutionRisksTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Risks', ['className' => 'Risk.Risks', 'foreignKey' =>'risk_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
    }

    public function findRecord(Query $query, array $options)
    {
        return $query->where([
            'risk_id' => $options['risk_id'],
            'institution_id' => $options['institution_id']
        ]);
    }

    public function getStatus($riskId, $institutionId)
    {
        $record = $this->find()
            ->where([
                'risk_id' => $riskId,
                'institution_id' => $institutionId
            ])
            ->first();

        if (isset($record->status)) {
            return $record->status;
        }
    }
}
