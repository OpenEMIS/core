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
        $list = $this->find('list')
            ->where([
                $this->aliasField('is_institution') => 1
            ])
            ->toArray()
        ;
        array_walk($list, [$this, 'translateArray']);
        return $list;
    }

    public function getAdministrationCalendarTypeList()
    {
        $list = $this->find('list')
            ->where([
                $this->aliasField('is_institution') => 0
            ])
            ->toArray()
        ;
        array_walk($list, [$this, 'translateArray']);
        return $list;
    }
}
