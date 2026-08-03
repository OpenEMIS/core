<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);
echo $this->Html->css('https://cdn.datatables.net/v/dt/dt-1.10.23/datatables.min.css', ['block' => true]);
echo $this->Html->script('https://cdn.datatables.net/v/dt/dt-1.10.23/datatables.min.js', ['block' => true]);
echo $this->Html->script('Report.report.view', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if (!isset($btn['type']) || $btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();
$this->start('panelBody');
$rowHeaderData = [];
if (!empty($rowHeader)) {
	foreach ($rowHeader as $key => $val) {
		foreach ($val as $kay1 => $val1) {
			if (isset($val1)) {
				$rowHeaderData[] = $val1;
			}
		}
	}
}
$params = $this->request->getAttribute('params');
$url = ['plugin' => $params['plugin'], 'controller' => $params['controller'], 'action' => 'ajaxGetReportProgress'];
$url = $this->Url->build($url);
$table = $ControllerAction['table'];
$downloadText = __('Downloading...');
$reportSections = isset($reportSections) ? $reportSections : [];
$lazyLoadReportView = !empty($lazyLoadReportView);
$viewReportConfig = isset($viewReportConfig) ? $viewReportConfig : [];
$initialRows = !empty($reportSections) ? ($reportSections[0]['rows'] ?? []) : ($newArr2 ?? []);
$initialHeaders = !empty($reportSections) ? ($reportSections[0]['headers'] ?? $rowHeaderData) : $rowHeaderData;
?>
<style type="text/css">
.none { display: none !important; }
#report-lazy-loading { display: none; margin: 10px 0; font-style: italic; }
</style>

<div class="table-wrapper">
	<?php if ($lazyLoadReportView) : ?>
		<div id="report-lazy-view" data-config='<?= json_encode($viewReportConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
			<div id="report-lazy-loading"><?= __('Loading...') ?></div>
			<div class="table-responsive">
				<table class="table table-curved report-table" id="lazyReportTable">
					<thead>
						<tr id="lazyReportHead">
							<?php foreach ($initialHeaders as $headerCell) : ?>
								<th><?= h($headerCell) ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody id="lazyReportBody">
						<?php foreach ($initialRows as $val) : ?>
						<tr>
							<?php foreach ($val as $val1) : ?>
							<td><?= h($val1) ?></td>
							<?php endforeach; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="dataTables_wrapper no-footer" id="report-row-pagination">
				<div class="dataTables_info" id="report-row-info"></div>
				<div class="dataTables_paginate paging_simple_numbers">
					<a class="paginate_button previous disabled" href="#"><?= __('Previous') ?></a>
					<span id="report-row-page-info"></span>
					<a class="paginate_button next" href="#"><?= __('Next') ?></a>
				</div>
			</div>
			<div class="dataTables_wrapper no-footer" id="report-section-pagination">
				<div class="dataTables_info" id="report-section-info"></div>
				<div class="dataTables_paginate paging_simple_numbers">
					<a class="paginate_button previous disabled" href="#"><?= __('Previous') ?></a>
					<span class="section-pages"></span>
					<a class="paginate_button next" href="#"><?= __('Next') ?></a>
				</div>
			</div>
		</div>
	<?php elseif (!empty($reportSections) && is_array($reportSections)) : ?>
		<?php foreach ($reportSections as $section) : ?>
			<?php if (empty($section['headers']) || empty($section['rows'])) { continue; } ?>
			<div class="table-responsive report-section" style="margin-bottom: 20px;" data-section-label="<?= h($section['title'] ?? '') ?>">
				<?php if (!empty($section['title']) && count($reportSections) <= 1) : ?>
					<h4 class="section-title" style="margin: 10px 0;"><?= h($section['title']) ?></h4>
				<?php endif; ?>
				<table class="table table-curved report-table">
				<thead>
					<tr>
					<?php foreach ($section['headers'] as $headerCell) : ?>
						<th><?= h($headerCell) ?> </th>
					<?php endforeach; ?>
					</tr>
					</thead>
					<tbody>
						<?php foreach ($section['rows'] as $val) :?>
						<tr>
							<?php foreach ($val as $val1) :?>
							<td><?= h($val1) ?></td>
							<?php endforeach; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>
		<?php if (count($reportSections) > 1) : ?>
			<div class="dataTables_wrapper no-footer">
				<div class="dataTables_info" id="report-page-info"></div>
				<div class="dataTables_paginate paging_simple_numbers" id="report-page-pagination">
					<a class="paginate_button previous disabled" href="#"><?= __('Previous') ?></a>
					<span>
						<?php
						$pIdx = 0;
						foreach ($reportSections as $pageIndex => $pageSection) :
							if (empty($pageSection['headers']) || empty($pageSection['rows'])) {
								continue;
							}
							?>
							<a class="paginate_button page-number<?= $pIdx === 0 ? ' current' : '' ?>" href="#" data-page-index="<?= (int)$pIdx ?>"><?= h($pageSection['title'] ?? (string)($pIdx + 1)) ?></a>
						<?php
							$pIdx++;
						endforeach;
						?>
					</span>
					<a class="paginate_button next<?= count($reportSections) <= 1 ? ' disabled' : '' ?>" href="#"><?= __('Next') ?></a>
				</div>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div class="table-responsive">
			<table class="table table-curved" id="myTable">
			<thead>
				<tr>
				<?php foreach ($rowHeaderData as $newArrdata) : ?>
					<th><?= h($newArrdata) ?> </th>
				<?php endforeach; ?>
				</tr>
				</thead>
				<tbody>
					<?php foreach ($newArr2 as $val) :?>
					<tr>
						<?php foreach ($val as $val1) :?>
						<td><?= h($val1) ?></td>
						<?php endforeach; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
<?php
$this->end();
