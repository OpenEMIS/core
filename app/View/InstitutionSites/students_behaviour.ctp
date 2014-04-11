<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'studentBehaviour');
$this->assign('contentHeader', __('List of Behaviour'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('controller' => 'InstitutionSites', 'action' => 'studentsView', $id), array('class' => 'divider'));
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'studentsBehaviourAdd', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->Form->create('InstitutionSite', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsBehaviour'),
	'inputDefaults' => array('label' => false, 'div' => false)
));
?>

<!--div class="table full_width allow_hover" action="InstitutionSites/studentsBehaviourView/"-->
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Date'); ?></th>
				<th><?php echo __('Category'); ?></th>
				<th><?php echo __('Title'); ?></th>
				<th><?php echo __('Insitution Site'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach($data as $id => $obj) { $i=0; ?>
			<tr row-id="<?php echo $obj['StudentBehaviour']['id']; ?>">
				<td class="center"><?php echo $this->Utility->formatDate($obj['StudentBehaviour']['date_of_behaviour']); ?></td>
				<td><?php echo $obj['StudentBehaviourCategory']['name']; ?></td>
				<td><?php echo $obj['StudentBehaviour']['title']; ?></td>
				<td><?php echo $obj['InstitutionSite']['name']; ?></td>
			</div>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
