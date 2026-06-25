<?php
//POCOR-9610: Institution Registrations tab — read-only view of institution_registrations, managed via API
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionRegistrationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Institutions', [
            'className'  => 'Institution.Institutions',
            'foreignKey' => 'institution_id',
        ]);
        $this->belongsTo('ModifiedUser', [
            'className'  => 'Security.Users',
            'foreignKey' => 'modified_user_id',
        ]);
        $this->belongsTo('CreatedUser', [
            'className'  => 'Security.Users',
            'foreignKey' => 'created_user_id',
        ]);

        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => [
                'Registrations' => ['id'],
            ],
        ]);

        // Read-only: data managed via API; HideButton removes add/edit/delete from UI
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('ControllerAction.HideButton');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        // visible:true required so isFieldVisible() returns true and onGet* listener is registered
        $this->field('status', ['label' => __('Status'), 'visible' => true]);
        $this->setFieldOrder(['valid_from', 'valid_to', 'status']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
        return $query;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('status', ['label' => __('Status'), 'visible' => true]);
        $this->setFieldOrder(['valid_from', 'valid_to', 'status']);
    }

    public function onGetStatus(EventInterface $event, Entity $entity): string
    {
        $validTo = $entity->valid_to;
        if (!$validTo) {
            return __('Valid');
        }
        return ($validTo < new Date()) ? __('Expired') : __('Valid');
    }

    // Excel: filter by institution_id, fix date type (force string → onExcelGet*), append Status column

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query): void
    {
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields): void
    {
        $keep = ['valid_from', 'valid_to'];
        $newFields = new ArrayObject();
        foreach ($fields->getArrayCopy() as $f) {
            $col = $f['field'] ?? '';
            if (!in_array($col, $keep, true)) {
                continue;
            }
            // Force string type so onExcelGet* is called instead of onExcelRenderDate (which returns blank)
            $f['type'] = 'string';
            $newFields[] = $f;
        }
        $newFields[] = [
            'key'        => 'InstitutionRegistrations.status',
            'field'      => 'status',
            'type'       => 'string',
            'label'      => __('Status'),
            'style'      => [],
            'formatting' => 'GENERAL',
        ];
        $fields->exchangeArray($newFields->getArrayCopy());
    }

    public function onExcelGetValidFrom(EventInterface $event, Entity $entity): string
    {
        return $entity->valid_from ? $entity->valid_from->format('Y-m-d') : '';
    }

    public function onExcelGetValidTo(EventInterface $event, Entity $entity): string
    {
        return $entity->valid_to ? $entity->valid_to->format('Y-m-d') : '';
    }

    public function onExcelGetStatus(EventInterface $event, Entity $entity): string
    {
        return $this->onGetStatus($event, $entity);
    }
}
