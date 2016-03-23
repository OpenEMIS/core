<?= $this->Html->script('app/components/institution/result/service', ['block' => true]); ?>
<?= $this->Html->script('app/components/institution/result/controller', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>

	<div ng-controller="ResultController">
		<div class="scrolltabs sticky-content">
			<scrollable-tabset show-tooltips="false" show-drop-down="false">
				<uib-tabset justified="true">

					<uib-tab heading="English">
						<div class="table-wrapper">
							<div class="table-responsive">
								<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh"></div>
							</div>
						</div>
					</uib-tab>

					<uib-tab heading="Mathematics">
						<div class="table-wrapper">
							<div class="table-responsive">
								<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh"></div>
							</div>
						</div>
					</uib-tab>

					<uib-tab heading="Science">
						<div class="table-wrapper">
							<div class="table-responsive">
								<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh"></div>
							</div>
						</div>
					</uib-tab>

				</uib-tabset>
				<div class="tabs-divider"></div>
			</scrollable-tabset>
		</div>
	</div>

<?php
$this->end();
?>
