<?php
namespace Institution\Model\Table;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class InstitutionCommitteeAttachmentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_id']);

        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }
}
