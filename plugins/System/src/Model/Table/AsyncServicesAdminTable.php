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
 * POCOR-9694 introduces a sidebar group that surfaces the OpenEMIS async
 * runtime — failed jobs, stuck processes, webhook failures, queue backlog,
 * and an at-a-glance overview. Each screen is a CakePHP {{ControllerAction}}
 * page; this class centralises the cross-cutting wiring so concrete pages
 * stay tiny and uniform:
 *
 * - Read-only by default (add / edit / remove toggles disabled).
 * - Optional placeholder banner for screens that are stubs while their
 *   data wiring is being built. Live screens leave {{describeScreen()}}
 *   returning the empty string and no banner is rendered.
 *
 * Subclasses provide {{setTable()}} pointing at the source data table.
 * Anything else is opt-in.
 *
 * @see src/Controller/Component/NavigationComponent.php
 *      ::getAdministrationAsyncServicesNav()
 * @see config/Migrations/20260508143848_POCOR9694.php
 */
abstract class AsyncServicesAdminTable extends ControllerActionTable
{
    /** Single canonical alert key so banner copy stays in one place. */
    protected const PLACEHOLDER_ALERT_KEY = 'asyncServicesPlaceholder';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->toggle('view', true);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    /**
     * Overrides the default {{Systems - StuckProcesses}} concatenation with
     * the agreed v4 label from {{pageTitle()}}. Fires for every CRUD action
     * so the heading is consistent across index / view.
     */
    public function beforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->controller->set('contentHeader', __($this->pageTitle()));
    }

    /**
     * Human-facing page title. Default is the auto-humanized alias — useful
     * for stub screens. Concrete pages override with the agreed v4 label
     * (Failed Background Tasks, Frozen Background Tasks, etc.).
     */
    protected function pageTitle(): string
    {
        return Inflector::humanize(Inflector::underscore($this->getAlias()));
    }

    /**
     * Banner copy for stub screens. Real screens return '' and no banner
     * is shown. Default is empty so a Table that doesn't override gets
     * a clean page.
     */
    protected function describeScreen(): string
    {
        return '';
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $banner = $this->describeScreen();
        if ($banner === '') {
            return;
        }
        $controller = $this->controller;
        if (isset($controller->Alert)) {
            $controller->Alert->info(
                $banner,
                ['type' => 'string', 'reset' => true, 'key' => self::PLACEHOLDER_ALERT_KEY]
            );
        }
    }
}
