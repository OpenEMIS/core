<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('home', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="user_details" class="content_wrapper">
	<h1><?php echo __($subTitle); ?></h1>
		<div class="table help">
			<div class="table_body">
				<div class="table_row">
					<div class="table_cell cell_item_name"><?PHP echo __('Telephone'); ?></div>
					<div class="table_cell">
					<?php
						if(isset($supportInformation['phone']) && !is_null($supportInformation['phone']) && !empty($supportInformation['phone'])) {
							echo $supportInformation['phone']; 
						} else {
							echo __('Data not available.');
						}
					?>
					</div>
				</div>
				<div class="table_row">
					<div class="table_cell cell_item_name"><?PHP echo __('Email'); ?></div>
					<div class="table_cell">
					<?php 
						if(isset($supportInformation['email']) && !is_null($supportInformation['email']) && !empty($supportInformation['email'])) {
							echo '<a href="mailto:'. $supportInformation['email'] . '">'.$supportInformation['email'] .'</a>';
						} else {
							echo __('Data not available.');
						}
					?>
					</div>
				</div>
				<div class="table_row">
					<div class="table_cell cell_item_name"><?PHP echo __('Address'); ?></div>
					<div class="table_cell">
					<?php 
						if(isset($supportInformation['address']) && !is_null($supportInformation['address']) && !empty($supportInformation['address'])) {
							echo $supportInformation['address'];
						} else {
							echo __('Data not available.');
						}
					?>
					<!-- 18 Sin Ming Lane <br/> -->
					<!-- #06-38 Midview City <br/> -->
					<!-- Singapore 573960 -->
					</div>
				</div>
			</div>
		</div>
</div>
