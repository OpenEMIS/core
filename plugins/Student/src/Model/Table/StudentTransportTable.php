<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class StudentTransportTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_trip_passengers');
        parent::initialize($config);

        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionTrips', ['className' => 'Institution.InstitutionTrips']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'integer']);
        $this->field('institution_trip_id', ['type' => 'integer']);
        $this->field('trip_type_id', ['type' => 'integer']);
        $this->field('provider_id', ['type' => 'integer']);
        $this->field('bus_id', ['type' => 'integer']);

        $this->setupTabElements();
    }

    public function viewAfterAction(Event $event)
    {
        $this->field('academic_period_id', ['type' => 'integer']);
        $this->field('institution_trip_id', ['type' => 'integer']);
        $this->field('trip_type_id', ['type' => 'integer']);
        $this->field('Provider_id', ['type' => 'integer']);
        $this->field('Bus_id', ['type' => 'integer']);
    }

    private function setupTabElements($entity = null)
    {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;
        $userId = !is_null($this->request->query('user_id')) ? $this->request->query('user_id') : 0;

        $options = [
            'userRole' => 'Student',
            'action' => $this->action,
            'id' => $id,
            'userId' => $userId
        ];

        $tabElements = $this->controller->getUserTabElements($options);

        if (!is_null($entity)) {
            $tabElements['StudentSurveys']['url'][0] = 'view';
            $tabElements['StudentSurveys']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['InstitutionTrips.TripTypes','InstitutionTrips.InstitutionBuses','InstitutionTrips.InstitutionTransportProviders']);
    }
    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

        $query
            ->contain(['InstitutionTrips.TripTypes','InstitutionTrips.InstitutionBuses','InstitutionTrips.InstitutionTransportProviders']);
    }

    public function onGetTripTypeId(Event $event, Entity $entity)
    {
        return $entity->institution_trip->trip_type->name;
    }

    public function onGetBusId(Event $event, Entity $entity)
    {
        return $entity->institution_trip->institution_bus->plate_number;
    }

    public function onGetProviderId(Event $event, Entity $entity)
    {
        return $entity->institution_trip->institution_transport_provider->name;
    }
}
