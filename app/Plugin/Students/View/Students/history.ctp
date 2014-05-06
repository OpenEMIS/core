<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('history', 'stylesheet', array('inline' => false));


$this->extend('/Elements/layout/container');

$this->assign('contentId', 'student');
$this->assign('contentHeader', __('Student History'));
$this->assign('contentClass', 'edit add');
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.details'), array('action' => 'view'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

	
	<?php if(!empty($data2)) : ?>
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
            <div class="col-md-2"><?php echo __('OpenEMIS ID'); ?></div>
            <div class="col-md-6"><?php //pr($data); ?>
				<span><?php echo $data['Student']['identification_no']; ?></span>
				<?php if(@sizeof($data2['identification_no'])>0){ // && ( sizeof($data2['identification_no']) != 1 && array_key_exists($data['Student']['identification_no'], $data2['identification_no']) ) ) { ?>
				<div class="table">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['identification_no'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Student']['identification_no'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell cell_datetime"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
            </div>
        </div>
		<div class="row">
			<div class="col-md-2"><?php echo __('First Name'); ?></div>
			<div class="col-md-6"><?php //pr($data); ?>
				<span><?php echo $data['Student']['first_name']; ?></span>
				<?php //pr($data2['first_name']);?>
				<?php if(@sizeof($data2['first_name'])>0){ //) { ?>
				<div class="table">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['first_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Student']['first_name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell cell_datetime"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
                <div class="row">
			<div class="col-md-2"><?php echo __('Middle Name'); ?></div>
			<div class="col-md-6"><?php //pr($data); ?>
				<span><?php echo $data['Student']['middle_name']; ?></span>
				<?php //pr($data2['first_name']);?>
				<?php if(@sizeof($data2['middle_name'])>0){ //) { ?>
				<div class="table">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['middle_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Student']['middle_name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell cell_datetime"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Last Name'); ?></div>
			<div class="col-md-6">
				<span><?php echo $data['Student']['last_name']; ?></span>
				<?php if(@sizeof($data2['last_name'])>0){ // && ( sizeof($data2['last_name']) != 1 && array_key_exists($data['Student']['last_name'], $data2['last_name']) ) ) { ?>
				<div class="table">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['last_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Student']['last_name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell cell_datetime"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
                <div class="row">
			<div class="col-md-2"><?php echo __('Preferred Name'); ?></div>
			<div class="col-md-6">
				<span><?php echo $data['Student']['preferred_name']; ?></span>
				<?php if(@sizeof($data2['preferred_name'])>0){ // && ( sizeof($data2['last_name']) != 1 && array_key_exists($data['Student']['last_name'], $data2['last_name']) ) ) { ?>
				<div class="table">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['preferred_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Student']['preferred_name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell cell_datetime"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Gender'); ?></div>
			<div class="col-md-6"><?php //pr($data);?>
				<span><?php echo $data['Student']['gender']; ?></span>
				<?php if(@sizeof($data2['gender'])>0){ // && ( sizeof($data2['gender']) != 1 && array_key_exists($data['Student']['gender'], $data2['gender']) ) ) { ?>
				<div class="table">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['gender'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Student']['gender'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell cell_datetime"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Date of Birth'); ?></div>
			<div class="col-md-6">
                <span><?php echo $this->Utility->formatDate($data['Student']['date_of_birth']); ?></span>
                <?php if(@sizeof($data2['date_of_birth'])>0){ // && ( sizeof($data2['date_of_birth']) != 1 && array_key_exists($data['Student']['date_of_birth'], $data2['date_of_birth']) ) ) { ?>
                <div class="table">
                    <div class="table_body"><?php //pr($data2);?>
                        <?php $ctr = 1; foreach($data2['date_of_birth'] as $val => $time):?>
                        <?php if($ctr == 1 && $data['Student']['date_of_birth'] == $val) continue; ?>
                        <div class="table_row">
							<div class="table_cell cell_value"><?php echo $this->Utility->formatDate($val); ?></div>
                            <div class="table_cell cell_datetime"><?php echo $time; ?></div>
                        </div>
                        <?php $ctr++; endforeach;?>
                    </div>
                </div>
                <?php } ?>
			</div>
		</div>
               <?php /* <div class="row">
			<div class="col-md-2"><?php echo __('Date of Death'); ?></div>
			<div class="col-md-6">
                <span><?php echo $this->Utility->formatDate($data['Student']['date_of_death']); ?></span>
                <?php if(@sizeof($data2['date_of_death'])>0){ ?>
                <div class="table">
                    <div class="table_body"><?php //pr($data2);?>
                        <?php $ctr = 1; foreach($data2['date_of_death'] as $val => $time):?>
                        <?php if($ctr == 1 && $data['Student']['date_of_death'] == $val) continue; ?>
                        <div class="table_row">
							<div class="table_cell cell_value"><?php echo $this->Utility->formatDate($val); ?></div>
                            <div class="table_cell cell_datetime"><?php echo $time; ?></div>
                        </div>
                        <?php $ctr++; endforeach;?>
                    </div>
                </div>
                <?php } ?>
			</div>
		</div> */ ?>
	</fieldset>
	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="col-md-2"><?php echo __('Address'); ?></div>
			<div class="col-md-6">
                <span><?php echo $data['Student']['address']; ?></span>
                <?php if(@sizeof($data2['address'])>0){ // && ( sizeof($data2['address']) != 1 && array_key_exists($data['Student']['address'], $data2['address']) ) ) { ?>
                <div class="table">
                    <div class="table_body">
                        <?php $ctr = 1; foreach($data2['address'] as $val => $time):?>
                        <?php if($ctr == 1 && $data['Student']['address'] == $val) continue; ?>
                        <div class="table_row">
                            <div class="table_cell cell_value"><?php echo $val; ?></div>
                            <div class="table_cell cell_datetime"><?php echo $time; ?></div>
                        </div>
                        <?php $ctr++; endforeach;?>
                    </div>
                </div>
                <?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Postal Code'); ?></div>
			<div class="col-md-6">
                <span><?php echo $data['Student']['postal_code']; ?></span>
                <?php if(@sizeof($data2['postal_code'])>0){ // && ( sizeof($data2['postal_code']) != 1 && array_key_exists($data['Student']['postal_code'], $data2['postal_code']) ) ) { ?>
                <div class="table">
                    <div class="table_body">
                        <?php $ctr = 1; foreach($data2['postal_code'] as $val => $time):?>
                        <?php if($ctr == 1 && $data['Student']['postal_code'] == $val) continue; ?>
                        <div class="table_row">
                            <div class="table_cell cell_value"><?php echo $val; ?></div>
                            <div class="table_cell cell_datetime"><?php echo $time; ?></div>

                        </div>
                        <?php $ctr++; endforeach;?>
                    </div>
                </div>
                <?php } ?>
			</div>
		</div>
    </fieldset>

    <?php if(@count($data2['address_area_id'])>0){ ?>
    <fieldset class="section_break">
        <legend><?php echo __('Address Area'); ?></legend>
        <?php echo @$this->Utility->showAreaHistory($this->Form, 'address_area_id', array(), $data['Student']['address_area_id'],$data2['address_area_id']);  ?>
    </fieldset>
    <?php } ?>
    <?php if(@count($data2['birthplace_area_id'])>0){ ?>
    <fieldset class="section_break">
        <legend><?php echo __('Birth Place Area'); ?></legend>
        <?php echo @$this->Utility->showAreaHistory($this->Form, 'address_area_id', array(), $data['Student']['birthplace_area_id'],$data2['birthplace_area_id']);  ?>
    </fieldset>
    <?php } ?>

    <?php endif; ?>
<?php $this->end(); ?>
<script type="text/javascript">
var values;
$('.table_body').each(function(i, obj){
    values = $(this).html().trim();
    if (values == null || values == '' || values == undefined) {
        $(this).parent().remove();
    }
});
</script>
