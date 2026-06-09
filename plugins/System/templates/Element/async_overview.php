<?php
/**
 * POCOR-9694 — Async Services overview KPI tiles + heartbeat.
 *
 * Receives:
 *   $tiles    — list of ['label', 'count', 'url' => array, 'severity' => ok|attention]
 *   $heartbeat — ['text' => string, 'severity' => ok|attention|stale]
 *
 * Rendered as a horizontal strip above the activity table on the
 * AsyncServicesOverview index page.
 *
 * Styling is intentionally minimal — uses standard OpenEMIS button
 * classes so the dashboard inherits the theme without bringing in new
 * CSS. {{btn-info}} = OK / quiet, {{btn-warning}} = attention.
 */
?>
<div class="toolbar-responsive panel-toolbar" style="margin-bottom: 12px;">
    <div class="toolbar-wrapper" style="display: flex; flex-wrap: wrap; gap: 12px;">
        <?php foreach ($tiles as $tile):
            $tileClass = $tile['severity'] === 'attention' ? 'btn-warning' : 'btn-info';
        ?>
            <a href="<?= $this->Url->build($tile['url']) ?>"
               class="btn <?= $tileClass ?>"
               style="min-width: 160px; padding: 12px 16px; text-align: center; text-decoration: none;">
                <div style="font-size: 24px; font-weight: bold; line-height: 1.2;"><?= $tile['count'] ?></div>
                <div style="font-size: 12px; opacity: 0.9;"><?= __($tile['label']) ?></div>
            </a>
        <?php endforeach; ?>

        <?php
        $hbClass = $heartbeat['severity'] === 'stale' ? 'text-danger'
                 : ($heartbeat['severity'] === 'attention' ? 'text-warning' : 'text-muted');
        ?>
        <div class="<?= $hbClass ?>"
             style="display: flex; align-items: center; padding: 0 16px; font-size: 13px;">
            <i class="fa fa-heartbeat" style="margin-right: 8px;"></i>
            <?= h($heartbeat['text']) ?>
        </div>
    </div>
</div>
