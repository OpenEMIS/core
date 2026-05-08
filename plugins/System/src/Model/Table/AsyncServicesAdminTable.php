<?php
declare(strict_types=1);

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;

/**
 * Base class for every {{Administration → Async Services}} admin screen.
 *
 * POCOR-9694 introduces a sidebar group that surfaces the OpenEMIS async
 * runtime — failed jobs, stuck processes, webhook failures, queue backlog,
 * and an at-a-glance overview. Each surface lives behind its own
 * ControllerAction page; this class centralises the cross-cutting wiring
 * so concrete pages stay tiny and uniform:
 *
 * - Read-only by default (add / edit / remove toggles disabled).
 * - One source of truth for the placeholder banner that tells operators
 *   the screen is a stub until the F7 follow-up lands.
 *
 * Subclasses provide:
 *
 * 1. {{setTable()}} pointing at the source data table.
 * 2. {{describeScreen()}} returning the banner copy specific to the page.
 *
 * Anything else is shared. Sub-pages with bespoke aggregates
 * (e.g. Overview, Queue Backlog) can override {{indexBeforeAction()}}
 * to push their own data into {{$extra}} without re-implementing the
 * boilerplate above.
 *
 * @see src/Controller/Component/NavigationComponent.php
 *      ::getAdministrationAsyncServicesNav()
 * @see config/Migrations/20260508160000_POCOR9694Nav.php
 */
abstract class AsyncServicesAdminTable extends ControllerActionTable
{
    /** Single canonical alert key so the banner copy stays in one place. */
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
     * Returns the screen-specific banner copy shown on the index page.
     *
     * Concrete subclasses must implement this; it is intentionally not
     * defaulted so a developer adding a new Async Services screen has to
     * make a deliberate choice about what the operator sees.
     */
    abstract protected function describeScreen(): string;

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $controller = $this->controller;
        if (isset($controller->Alert)) {
            $controller->Alert->info(
                $this->describeScreen(),
                ['type' => 'string', 'reset' => true, 'key' => self::PLACEHOLDER_ALERT_KEY]
            );
        }
    }
}
