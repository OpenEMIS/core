<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="history" class="content_wrapper history">
	<h1>
		<span><?php echo __('Teacher History'); ?></span>
		<?php echo $this->Html->link(__('Details'), array('action' => 'view'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if(!empty($data2)) : ?>
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
        <div class="row">
            <div class="label"><?php echo __('OpenEMIS ID'); ?></div>
            <div class="value"><?php //pr($data); ?>
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['identification_no']; ?></span>
                            <?php if(@sizeof($data2['identification_no'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['identification_no'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['identification_no'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>

                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
            </div>
        </div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php //pr($data); ?>
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['first_name']; ?></span>
                            <?php if(@sizeof($data2['first_name'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['first_name'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['first_name'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
			</div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value"><?php //pr($data); ?>
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['middle_name']; ?></span>
                            <?php if(@sizeof($data2['middle_name'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['middle_name'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['middle_name'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['last_name']; ?></span>
                             <?php if(@sizeof($data2['last_name'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['last_name'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['last_name'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
			</div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['preferred_name']; ?></span>
                             <?php if(@sizeof($data2['preferred_name'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['preferred_name'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['preferred_name'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php //pr($data);?>
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['gender']; ?></span>
                            <?php if(@sizeof($data2['gender'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['gender'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['gender'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $this->Utility->formatDate($data['Teacher']['date_of_birth']); ?></span>
                            <?php if(@sizeof($data2['date_of_birth'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body"><?php //pr($data2);?>
                                    <?php $ctr = 1; foreach($data2['date_of_birth'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['date_of_birth'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $this->Utility->formatDate($val); ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                             <?php } ?>
			</div>
		</div>
	</fieldset>
	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['address']; ?></span>
                            <?php if(@sizeof($data2['address'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['address'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['address'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['postal_code']; ?></span>
                             <?php if(@sizeof($data2['postal_code'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['postal_code'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['postal_code'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
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
            <?php echo @$this->Utility->showAreaHistory($this->Form, 'address_area_id', array(), $data['Teacher']['address_area_id'],$data2['address_area_id']);  ?>
        </fieldset>
        <?php } ?>
        <?php if(@count($data2['birthplace_area_id'])>0){ ?>
        <fieldset class="section_break">
            <legend><?php echo __('Birth Place Area'); ?></legend>
            <?php echo @$this->Utility->showAreaHistory($this->Form, 'address_area_id', array(), $data['Teacher']['birthplace_area_id'],$data2['birthplace_area_id']);  ?>
        </fieldset>
        <?php } ?>

    <fieldset class="section_break">
        <legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['telephone']; ?></span>
                            <?php if(@sizeof($data2['telephone'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['telephone'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['telephone'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value">
                            <span style="margin-left: 8px;"><?php echo $data['Teacher']['email']; ?></span>
                            <?php if(@sizeof($data2['email'])>0){ ?>
                            <div class="table" style="margin-top: 10px;">
                                <div class="table_body">
                                    <?php $ctr = 1; foreach($data2['email'] as $val => $time):?>
                                    <?php if($ctr == 1 && $data['Teacher']['email'] == $val) continue; ?>
                                    <div class="table_row">
                                        <div class="table_cell cell_value"><?php echo $val; ?></div>
                                        <div class="table_cell"><?php echo $time; ?></div>
                                    </div>
                                    <?php $ctr++; endforeach;?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
		</div>
	</fieldset>
    <?php endif; ?>
</div>

<script type="text/javascript">
var values;
$('.table_body').each(function(i, obj){
    values = $(this).html().trim();
    if (values == null || values == '' || values == undefined) {
        $(this).parent().remove();
    }
});
</script>