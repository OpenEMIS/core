<?php
//POCOR-9610: start - CakePHP Table for institution_registrations — valid_from / valid_to per spec
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

        //POCOR-9610: start - associations
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
        //POCOR-9610: end

        //POCOR-9610: start - Excel export on index; InstitutionTab for back-button fix
        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => [
                'Registrations' => ['id'],
            ],
        ]);
        //POCOR-9610: end

        //POCOR-9610: start - read-only: data managed via API only
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('ControllerAction.HideButton');
        //POCOR-9610: end
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9610: start - index columns: Valid From | Valid To | Actions
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('status', ['label' => __('Status'), 'visible' => true]);
        $this->setFieldOrder(['valid_from', 'valid_to', 'status']);
        //POCOR-9610: end
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        //POCOR-9610: start - filter by institution_id from queryString
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
        return $query;
        //POCOR-9610: end
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9610: start - hide audit fields on view; show status virtual field
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('status', ['label' => __('Status'), 'visible' => true]);
        $this->setFieldOrder(['valid_from', 'valid_to', 'status']);
        //POCOR-9610: end
    }

    public function onGetStatus(EventInterface $event, Entity $entity): string
    {
        //POCOR-9610: Valid if valid_to is in future or null; Expired otherwise
        $validTo = $entity->valid_to;
        if (!$validTo) {
            return __('Valid');
        }
        return ($validTo < new Date()) ? __('Expired') : __('Valid');
    }

    //POCOR-9610: start - Excel export: filter by institution_id, fix date rendering, hide audit columns

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query): void
    {
        //POCOR-9610: filter Excel to current institution only (same as index)
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields): void
    {
        //POCOR-9610: keep only valid_from and valid_to; override type to string so onExcelGet* handles rendering
        $keep = ['valid_from', 'valid_to'];
        $newFields = new ArrayObject();
        foreach ($fields->getArrayCopy() as $f) {
            $col = $f['field'] ?? '';
            if (!in_array($col, $keep, true)) {
                continue;
            }
            $f['type'] = 'string'; //POCOR-9610: force string type so onExcelGet* is called instead of onExcelRenderDate
            $newFields[] = $f;
        }
        //POCOR-9610: append virtual Status column
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
        //POCOR-9610: format Date as Y-m-d string for Excel
        return $entity->valid_from ? $entity->valid_from->format('Y-m-d') : '';
    }

    public function onExcelGetValidTo(EventInterface $event, Entity $entity): string
    {
        //POCOR-9610: format Date as Y-m-d string for Excel
        return $entity->valid_to ? $entity->valid_to->format('Y-m-d') : '';
    }

    public function onExcelGetStatus(EventInterface $event, Entity $entity): string
    {
        return $this->onGetStatus($event, $entity);
    }

    //POCOR-9610: end
}
//POCOR-9610: end
