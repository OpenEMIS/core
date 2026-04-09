<?php
//POCOR-9610: start - CakePHP Table for institution_accreditations — education_programme_id FK per spec
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use App\Model\Table\ControllerActionTable;

class InstitutionAccreditationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        //POCOR-9610: start - associations
        $this->belongsTo('Institutions', [
            'className'  => 'Institution.Institutions',
            'foreignKey' => 'institution_id',
        ]);
        $this->belongsTo('EducationProgrammes', [
            'className'  => 'Education.EducationProgrammes',
            'foreignKey' => 'education_programme_id',
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
                'Accreditations' => ['id'],
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
        //POCOR-9610: start - index columns: Programme Code | Programme Name | Valid From | Valid To | Actions
        $this->field('institution_id', ['visible' => false]);
        $this->field('education_programme_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        //POCOR-9610: virtual columns — visible:true required so isFieldVisible() returns true and onGet* listeners are registered
        $this->field('programme_code', [
            'label'   => __('Programme Code'),
            'visible' => true,
        ]);
        $this->field('programme_name', [
            'label'   => __('Programme Name'),
            'visible' => true,
        ]);
        //POCOR-9610: also add Status virtual column — Expired vs Valid based on valid_to vs today
        $this->field('status', [
            'label'   => __('Status'),
            'visible' => true,
        ]);

        $this->setFieldOrder(['programme_code', 'programme_name', 'valid_from', 'valid_to', 'status']);
        //POCOR-9610: end
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        //POCOR-9610: start - filter by institution_id; contain full programme chain for display label
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
        $query->contain([
            'EducationProgrammes' => function ($q) {
                //POCOR-9610: explicitly select code+name — ControllerActionTable beforeFind may strip non-default fields
                return $q->select(['EducationProgrammes.id', 'EducationProgrammes.code', 'EducationProgrammes.name', 'EducationProgrammes.education_cycle_id'])
                    ->contain(['EducationCycles' => function ($q2) {
                        return $q2->select(['EducationCycles.id', 'EducationCycles.name', 'EducationCycles.education_level_id'])
                            ->contain(['EducationLevels' => function ($q3) {
                                return $q3->select(['EducationLevels.id', 'EducationLevels.name', 'EducationLevels.education_system_id'])
                                    ->contain(['EducationSystems' => function ($q4) {
                                        return $q4->select(['EducationSystems.id', 'EducationSystems.name', 'EducationSystems.academic_period_id'])
                                            ->contain(['AcademicPeriods' => function ($q5) {
                                                return $q5->select(['AcademicPeriods.id', 'AcademicPeriods.name']);
                                            }]);
                                    }]);
                            }]);
                    }]);
            },
        ]);
        return $query;
        //POCOR-9610: end
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        //POCOR-9610: start - contain full programme chain so onGetProgrammeCode/Name work on view
        $query->contain(['EducationProgrammes' => ['EducationCycles' => ['EducationLevels' => ['EducationSystems' => ['AcademicPeriods']]]]]);
        return $query;
        //POCOR-9610: end
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9610: start - hide audit fields; show programme code, name, status as virtual fields (same as index)
        $this->field('institution_id', ['visible' => false]);
        $this->field('education_programme_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        $this->field('programme_code', ['label' => __('Programme Code'), 'visible' => true]);
        $this->field('programme_name', ['label' => __('Programme Name'), 'visible' => true]);
        $this->field('status',         ['label' => __('Status'),         'visible' => true]);

        $this->setFieldOrder(['programme_code', 'programme_name', 'valid_from', 'valid_to', 'status']);
        //POCOR-9610: end
    }

    //POCOR-9610: start - onGet* handlers for virtual display fields

    public function onGetProgrammeCode(EventInterface $event, Entity $entity): string
    {
        $prog = $entity->education_programme ?? null;

        return $prog ? ((string) ($prog->code ?? '')) : '';
    }

    public function onGetProgrammeName(EventInterface $event, Entity $entity): string
    {
        //POCOR-9610: full label "Name (Level — System — Period)" mirroring the HTML seed page
        $prog = $entity->education_programme ?? null;
        if (!$prog) {
            return '';
        }
        $name   = (string) ($prog->name ?? '');
        $cycle  = $prog->education_cycle  ?? null;
        $level  = $cycle  ? ($cycle->education_level  ?? null) : null;
        $system = $level  ? ($level->education_system ?? null) : null;
        $period = $system ? ($system->academic_period ?? null) : null;

        $parts = array_filter([
            $level  ? (string) ($level->name  ?? '') : '',
            $system ? (string) ($system->name ?? '') : '',
            $period ? (string) ($period->name ?? '') : '',
        ]);

        return $parts ? $name . ' (' . implode(' — ', $parts) . ')' : $name;
    }

    public function onGetStatus(EventInterface $event, Entity $entity): string
    {
        //POCOR-9610: compare valid_to against today; null valid_to = perpetually valid
        $validTo = $entity->valid_to;
        if (!$validTo) {
            return __('Valid');
        }
        $today = new Date();
        return ($validTo < $today) ? __('Expired') : __('Valid');
    }

    //POCOR-9610: start - Excel export customisation

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query): void
    {
        //POCOR-9610: contain full chain so onExcelGetEducationProgrammeId can build the full label
        $query->contain(['EducationProgrammes' => ['EducationCycles' => ['EducationLevels' => ['EducationSystems' => ['AcademicPeriods']]]]]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields): void
    {
        //POCOR-9610: remove raw FK/audit columns; keep programme_id (renamed), valid_from, valid_to, status virtual
        $keep = ['education_programme_id', 'valid_from', 'valid_to'];
        $newFields = new ArrayObject();
        foreach ($fields->getArrayCopy() as $f) {
            $col = $f['field'] ?? '';
            if (!in_array($col, $keep, true)) {
                continue;
            }
            if ($col === 'education_programme_id') {
                $f['label'] = __('Education Programme');
            }
            if (in_array($col, ['valid_from', 'valid_to'], true)) {
                $f['type'] = 'string'; //POCOR-9610: force string so onExcelGet* is called instead of onExcelRenderDate
            }
            $newFields[] = $f;
        }
        //POCOR-9610: prepend virtual Programme Code and Programme Name columns before the date columns
        $dateAndStatus = $newFields->getArrayCopy();
        $newFields = new ArrayObject();
        $newFields[] = [
            'key'        => 'InstitutionAccreditations.programme_code',
            'field'      => 'programme_code',
            'type'       => 'string',
            'label'      => __('Programme Code'),
            'style'      => [],
            'formatting' => 'GENERAL',
        ];
        $newFields[] = [
            'key'        => 'InstitutionAccreditations.programme_name',
            'field'      => 'programme_name',
            'type'       => 'string',
            'label'      => __('Programme Name'),
            'style'      => [],
            'formatting' => 'GENERAL',
        ];
        foreach ($dateAndStatus as $f) {
            if (($f['field'] ?? '') !== 'education_programme_id') {
                $newFields[] = $f; // valid_from, valid_to
            }
        }
        //POCOR-9610: append virtual Status column
        $newFields[] = [
            'key'        => 'InstitutionAccreditations.status',
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

    public function onExcelGetProgrammeCode(EventInterface $event, Entity $entity): string
    {
        return $this->onGetProgrammeCode($event, $entity);
    }

    public function onExcelGetProgrammeName(EventInterface $event, Entity $entity): string
    {
        return $this->onGetProgrammeName($event, $entity);
    }

    public function onExcelGetStatus(EventInterface $event, Entity $entity): string
    {
        return $this->onGetStatus($event, $entity);
    }

    //POCOR-9610: end
}
//POCOR-9610: end
