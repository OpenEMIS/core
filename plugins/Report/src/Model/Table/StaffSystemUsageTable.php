<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffSystemUsageTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->addBehavior('Excel', [
            'excludes' => ['preferred_name', 'email', 'address', 'postal_code', 'address_area_id', 'birthplace_area_id', 'gender_id', 'date_of_birth', 'date_of_death', 'nationality_id', 'identity_type_id', 'identity_number', 'external_reference', 'super_admin', 'status', 'photo_name', 'photo_content', 'preferred_language', 'is_student', 'is_staff', 'is_guardian'],
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedUsageType = !empty($requestData->system_usage) ? $requestData->system_usage : null;

        $query
            ->where([$this->aliasField('is_staff') => 1])
            ->order([$this->aliasField('first_name'), $this->aliasField('last_name')]);

        if ($selectedUsageType == 1) {
            $query->where([$this->aliasField('last_login') . ' IS NULL']);

        } else if ($selectedUsageType == 2) {
            $lastSevenDays = new Date('-6 days');
            $formattedDate = $lastSevenDays->format('Y-m-d H:i:s');
            $query->where([$this->aliasField('last_login').' >= ' => $formattedDate]);
        }
    }
}
