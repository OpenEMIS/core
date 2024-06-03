<?php
namespace ProfileTemplate\Model\Table;

use ArrayObject;
use ZipArchive;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;
/**
 * 
 * This class is used to generate report from profile tabs
 * We can generate/download report and trigger event from this class
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 * 
 */
class ClassProfileTemplatesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    { 

    }

}
