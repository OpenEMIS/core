<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
	<h1>
		<span><?php echo __('Verifications'); ?></span>
		<?php 
		if($_execute) {
			if($allowVerify) {
				echo $this->Html->link(__('Verify'), array('action' => 'verifies', 1), array('class' => 'divider', 'onclick' => 'return Census.verify(this, "GET")'));
			}
			if($allowUnverify) {
				echo $this->Html->link(__('Unverify'), array('action' => 'verifies', 0), array('class' => 'divider', 'onclick' => 'return Census.verify(this, "GET")'));
			}
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="table full_width no_strips">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('By'); ?></div>
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Status'); ?></div>
		</div>
		
		<div class="table_body">
			<?php
			$bold = '<b>%s</b>';
			$counter = count($data) > 0 ? $data[0]['SchoolYear']['name'] : null;
			for($i=0; $i<count($data); $i++) {
				$highlight = '';
				$obj = $data[$i];
				$created = $obj['CensusVerification']['created'];
				$status = $obj['CensusVerification']['status'];
				$year = $obj['SchoolYear']['name'];
				$by = trim($obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['last_name']);
				$date = $this->Utility->formatDate($created, null, false) . ' ' . date('H:i:s', strtotime($created));
				$status = '<span class="' . ($status==1 ? 'green' : 'red') . '">' . ($status==1 ? __('Verified') : __('Unverified')) . '</span>';
				
				if($i==count($data)-1 || ($counter !== $data[$i+1]['SchoolYear']['name'])) {
					if($i!=count($data)-1) {
						$counter = $data[$i+1]['SchoolYear']['name'];
					}
					$highlight = 'selected';
				}
			?>
			<div class="table_row <?php echo $highlight ?>">
				<div class="table_cell"><?php echo $year; ?></div>
				<div class="table_cell"><?php echo $by; ?></div>
				<div class="table_cell"><?php echo $date; ?></div>
				<div class="table_cell"><?php echo $status; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>