<?php
//POCOR-9610: start - CakePHP Table for institution_accreditations — education_programme_id FK per spec
namespace Institution\Model\Table;

use ArrayObject;
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

        //POCOR-9610: start - read-only: data is pushed from OpenEMIS Accreditations via API
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        //POCOR-9610: end

        $this->setDeleteStrategy('restrict');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9610: start - index columns: Programme Code | Programme Name | Valid From | Valid To | Actions
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        // Virtual columns displaying programme data from the FK relation
        $this->field('programme_code', [
            'label'    => __('Programme Code'),
            'virtual'  => true,
            'override' => true,
        ]);
        $this->field('programme_name', [
            'label'    => __('Programme Name'),
            'virtual'  => true,
            'override' => true,
        ]);

        $this->setFieldOrder(['programme_code', 'programme_name', 'valid_from', 'valid_to']);
        //POCOR-9610: end
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): Query
    {
        //POCOR-9610: start - filter by institution_id; contain EducationProgrammes for display
        $institutionId = $this->getQueryString('institution_id');
        if ($institutionId) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
        $query->contain(['EducationProgrammes']);
        return $query;
        //POCOR-9610: end
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9610: start - hide audit fields; show education_programme_id as label on view
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('education_programme_id', [
            'type'  => 'select',
            'label' => __('Education Programme'),
        ]);
        //POCOR-9610: end
    }

    public function onGetProgrammeCode(EventInterface $event, Entity $entity): string
    {
        //POCOR-9610: start - display programme code from related EducationProgrammes
        return ($entity->has('education_programme') && $entity->education_programme)
            ? $entity->education_programme->code
            : '';
        //POCOR-9610: end
    }

    public function onGetProgrammeName(EventInterface $event, Entity $entity): string
    {
        //POCOR-9610: start - display programme name from related EducationProgrammes
        return ($entity->has('education_programme') && $entity->education_programme)
            ? $entity->education_programme->name
            : '';
        //POCOR-9610: end
    }
}
//POCOR-9610: end
