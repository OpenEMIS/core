<?php
//POCOR-9610: start - CakePHP Table for institution_registrations — valid_from / valid_to per spec
namespace Institution\Model\Table;

use ArrayObject;
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

        //POCOR-9610: start - read-only: data is pushed from OpenEMIS Accreditations via API
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        //POCOR-9610: end

        $this->setDeleteStrategy('restrict');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9610: start - index columns: Valid From | Valid To | Actions
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->setFieldOrder(['valid_from', 'valid_to']);
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
        //POCOR-9610: start - hide audit fields on view
        $this->field('institution_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        //POCOR-9610: end
    }
}
//POCOR-9610: end
