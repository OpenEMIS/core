<?php
declare(strict_types=1);

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

/**
 * Base class for every {{Administration → Async Services}} admin screen.
 *
 * Read-only by default. Subclasses provide {{setTable()}} pointing at the
 * source data table and override {{pageTitle()}} for the v4 label.
 *
 * @see src/Controller/Component/NavigationComponent.php
 *      ::getAdministrationAsyncServicesNav()
 */
abstract class AsyncServicesAdminTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->toggle('view', true);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->controller->set('contentHeader', __($this->pageTitle()));
    }

    protected function pageTitle(): string
    {
        return Inflector::humanize(Inflector::underscore($this->getAlias()));
    }
}
