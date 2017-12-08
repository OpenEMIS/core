<?php
namespace App\Model\Table;

class CalendarTypesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('calendar_types');
        parent::initialize($config);

        $this->belongsTo('CalendarsTable', ['className' => 'CalendarsTable', 'foreignKey' => 'calendar_type_id']);
    }

    public function getInstitutionCalendarTypeList()
    {
        return $this->find('list')
            ->where([
                $this->aliasField('is_institution') => 1
            ])
            ->toArray()
        ;
    }

    public function getAdministrationCalendarTypeList()
    {
        return $this->find('list')
            ->where([
                $this->aliasField('is_institution') => 0
            ])
            ->toArray()
        ;
    }
}
