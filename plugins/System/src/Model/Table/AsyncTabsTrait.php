<?php
declare(strict_types=1);

namespace System\Model\Table;

//POCOR-9719: shared tab bar for the 6 Async Services screens.
//
//Renders one sidebar entry ("System Activities") and a horizontal tab bar at
//the top of the page with all 6 actions visible. The OpenEMIS Panel layout
//auto-renders {{Element/nav_tabs}} from the {{tabElements}} + {{selectedAction}}
//view vars set here — no template overrides needed.
//
//Pass the URL as a routing ARRAY (not a Router::url string) so {{Html->link}}
//handles base-path prefixing — otherwise the base gets doubled and every tab
//hits a 404.
//
//Replaces the POCOR-9719 v1 dropdown filter (AsyncFilterTrait + async_filter
//element) which (a) didn't render due to a $data variable collision in
//ControllerAction's element pipeline and (b) was the wrong UX pattern
//(dropdowns hide choices and look like data fields).
trait AsyncTabsTrait
{
    private const TABS = [
        'AsyncServicesOverview' => 'Overview',
        'SystemProcesses'       => 'Completed Jobs',
        'FailedJobs'            => 'Failed Jobs',
        'StuckProcesses'        => 'Stuck Tasks',
        'WebhookFailures'       => 'Webhook Failures',
        'QueueBacklog'          => 'Waiting Jobs',
    ];

    protected function setupAsyncTabs(): void
    {
        $tabElements = [];
        foreach (self::TABS as $action => $label) {
            $tabElements[$action] = [
                'text' => __($label),
                'url'  => [
                    'plugin'     => 'System',
                    'controller' => 'Systems',
                    'action'     => $action,
                ],
            ];
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }
}
