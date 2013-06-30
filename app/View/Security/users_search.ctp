<?php 
if($data !== false) {
	if(empty($data)) { ?>
	
		<div class="alert" type="2"><?php echo __("Your search returns no result."); ?></div>
		
<?php
	} else {
		foreach($data as $user) {
			$obj = $user['SecurityUser'];
			$id_no = $this->Utility->highlight($search, $obj['identification_no']);
			$firstName = $this->Utility->highlight($search, $obj['first_name']);
			$lastName = $this->Utility->highlight($search, $obj['last_name']);
			$name = $firstName . ' ' . $lastName;
?>
		
		<div class="table_row">
			<div class="table_cell cell_id_no"><?php echo $id_no; ?></div>
			<div class="table_cell"><?php echo $name; ?></div>
			<div class="table_cell cell_role">
				<?php
				echo $this->Form->input('security_role_id', array(
					'label' => false,
					'div' => false,
					'class' => 'full_width',
					'options' => !empty($obj['roles']) ? $obj['roles'] : array('-- ' . __('No roles available') . ' --')
				));
				
				?>
			</div>
			<div class="table_cell cell_icon_action">
				<?php if(!empty($obj['roles'])) { ?>
				<span class="icon_plus" user-id="<?php echo $obj['id']; ?>" onClick="Security.addGroupUser(this)"></span>
				<?php } ?>
			</div>
		</div>
	
<?php 
		}
	}
} else {
?>
	<div class="alert" type="0"><?php echo __("Your search returned too many results. Please refine your search criteria."); ?></div>

<?php } ?>