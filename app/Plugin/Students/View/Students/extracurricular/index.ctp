<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="extracurricular" class="content_wrapper">
	<h1>
		<span><?php echo __('Extracurricular'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'extracurricularAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Students/extracurricularView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('Start Date'); ?></div>
            <div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Title'); ?></div>
		</div>
		
		<div class="table_body">
			<?php  foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StudentExtracurricular']['id']; ?>">
				<div class="table_cell"><?php echo $obj['SchoolYears']['name']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StudentExtracurricular']['start_date']); ?></div>
				<div class="table_cell"><?php echo $obj['ExtracurricularType']['name']; ?></div>
                <div class="table_cell"><?php echo $obj['StudentExtracurricular']['name']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
*/ ?>
<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'extracurricularAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered" action="Students/extracurricularView/">
        <thead>
            <tr>
                <th><?php echo __('Year'); ?></th>
                <th><?php echo __('Start Date'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Title'); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php
            if (count($data) > 0):
                foreach ($data as $obj):
                    $id = $obj[$model]['id'];
            ?>
                    <tr>
                        <td><?php echo $obj['SchoolYears']['name']; ?></td>
                        <td ><?php echo $this->Utility->formatDate($obj['StudentExtracurricular']['start_date']); ?></td>
                        <td><?php echo $obj['ExtracurricularType']['name']; ?></td>
                        <td><?php echo $this->Html->link($obj['StudentExtracurricular']['name'], array('action' => 'extracurricularView', $id), array('escape' => false)) ?></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>
