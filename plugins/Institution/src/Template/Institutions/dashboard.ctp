<?php
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('highchart/modules/exporting', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
?>
<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('panelBody');
?>

<div class="row institution-dashboard">
    <div id="dashboard-spinner" class="spinner-wrapper">
        <div class="spinner-text">
            <div class="spinner lt-ie9"></div>
            <p><?= __('Loading'); ?> ...</p>
        </div>
    </div>

	<?php foreach ($highChartDatas as $key => $highChartData) : ?>
		<div class="highchart col-md-6" style="visibility: hidden">
			<?php echo $highChartData; ?>
		</div>
	<?php endforeach ?>
</div>

<?php
$this->end();
?>
