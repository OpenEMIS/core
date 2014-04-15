<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identity" class="content_wrapper">
	<h1>
		<span><?php echo __('Identities'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'identitiesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Students/identitiesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<div class="table_cell"><?php echo __('Issued'); ?></div>
			<div class="table_cell"><?php echo __('Expiry'); ?></div>
			<div class="table_cell"><?php echo __('Location'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StudentIdentity']['id']; ?>">
				<div class="table_cell"><?php echo $obj['IdentityType']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['StudentIdentity']['number']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StudentIdentity']['issue_date']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StudentIdentity']['expiry_date']); ?></div>
				<div class="table_cell"><?php echo $obj['StudentIdentity']['issue_location']; ?></div>
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
    echo $this->Html->link(__('Add'), array('action' => 'identitiesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Number'); ?></th>
                <th><?php echo __('Issued'); ?></th>
                <th><?php echo __('Expiry'); ?></th>
                <th><?php echo __('Location'); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php
            if (count($data) > 0) {
                foreach ($data as $obj) {
                    $id = $obj[$model]['id'];
                    echo '<tr>
                                <td>' . $obj['IdentityType']['name'] . '</td>
                                <td>' . $this->Html->link($obj[$model]['number'], array('action' => 'identitiesView', $id), array('escape' => false)) . '</td>
                                <td>' . $this->Utility->formatDate($obj['StudentIdentity']['issue_date']) . '</td>
                                <td>' . $this->Utility->formatDate($obj['StudentIdentity']['expiry_date']) . '</td>
                                <td>' . $obj['StudentIdentity']['issue_location'] . '</td>
                        </tr>';
                }
            }
            ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>
