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
        $title = __($this->pageTitle());
        $this->controller->set('contentHeader', $title);

        //POCOR-9719: align breadcrumbs with the sidebar label.
        //beforeFilter() seeds crumbs as "Systems > Queue Backlog"; we
        //correct both to "System Activities > Waiting Background Tasks".
        $nav = $this->controller->Navigation ?? null;
        if ($nav && !empty($nav->breadcrumbs)) {
            $nav->breadcrumbs[0]['title'] = __('System Activities');
            $nav->breadcrumbs[array_key_last($nav->breadcrumbs)]['title'] = $title;
            $this->controller->set('_breadcrumbs', $nav->breadcrumbs);
        }
    }

    protected function pageTitle(): string
    {
        return Inflector::humanize(Inflector::underscore($this->getAlias()));
    }
}
