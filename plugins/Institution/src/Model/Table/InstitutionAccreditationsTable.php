<?php
//POCOR-9610: Institution Accreditations tab — read-only view of institution_accreditations, managed via API
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

        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => [
                'Accreditations' => ['id'],
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
        $this->field('education_programme_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        // visible:true required so isFieldVisible() returns true and onGet* listeners are registered
        $this->field('programme_code', ['label' => __('Programme Code'), 'visible' => true]);
        $this->field('programme_name', ['label' => __('Programme Name'), 'visible' => true]);
        $this->field('status',         ['label' => __('Status'),         'visible' => true]);

        $this->setFieldOrder(['programme_code', 'programme_name', 'valid_from', 'valid_to', 'status']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
        // Explicit field select required — ControllerActionTable beforeFind may strip non-default fields
        $query->contain([
            'EducationProgrammes' => function ($q) {
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
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        $query->contain(['EducationProgrammes' => ['EducationCycles' => ['EducationLevels' => ['EducationSystems' => ['AcademicPeriods']]]]]);
        return $query;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
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
    }

    public function onGetProgrammeCode(EventInterface $event, Entity $entity): string
    {
        $prog = $entity->education_programme ?? null;
        return $prog ? ((string) ($prog->code ?? '')) : '';
    }

    public function onGetProgrammeName(EventInterface $event, Entity $entity): string
    {
        // Full label: "Name (Level — System — Period)" — mirrors the HTML seed page format
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
        // null valid_to = no expiry = always valid
        $validTo = $entity->valid_to;
        if (!$validTo) {
            return __('Valid');
        }
        return ($validTo < new Date()) ? __('Expired') : __('Valid');
    }

    // Excel: contain full chain, fix date type (force string → onExcelGet*), custom column set

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query): void
    {
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
        $query->contain(['EducationProgrammes' => ['EducationCycles' => ['EducationLevels' => ['EducationSystems' => ['AcademicPeriods']]]]]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields): void
    {
        // Keep only date columns (renamed), prepend Programme Code/Name, append Status
        $keep = ['valid_from', 'valid_to'];
        $dateCols = new ArrayObject();
        foreach ($fields->getArrayCopy() as $f) {
            $col = $f['field'] ?? '';
            if (!in_array($col, $keep, true)) {
                continue;
            }
            // Force string type so onExcelGet* is called instead of onExcelRenderDate (which returns blank)
            $f['type'] = 'string';
            $dateCols[] = $f;
        }

        $newFields = new ArrayObject();
        $newFields[] = ['key' => 'InstitutionAccreditations.programme_code', 'field' => 'programme_code', 'type' => 'string', 'label' => __('Programme Code'), 'style' => [], 'formatting' => 'GENERAL'];
        $newFields[] = ['key' => 'InstitutionAccreditations.programme_name', 'field' => 'programme_name', 'type' => 'string', 'label' => __('Programme Name'), 'style' => [], 'formatting' => 'GENERAL'];
        foreach ($dateCols->getArrayCopy() as $f) {
            $newFields[] = $f;
        }
        $newFields[] = ['key' => 'InstitutionAccreditations.status', 'field' => 'status', 'type' => 'string', 'label' => __('Status'), 'style' => [], 'formatting' => 'GENERAL'];
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
}
