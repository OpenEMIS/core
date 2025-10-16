<?php
// POCOR-9225[START]
$this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min.css', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min.js', ['block' => true]);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.chosenSelect').chosen({
        width: '100%',
        allow_single_deselect: true,
        placeholder_text_single: 'Select an option'
    });
});
</script>
<style>
	.input-select-wrapper {
    background-color: #ffffff; /* white */
    font-weight: 390;     /* optional: rounded corners */
	border-radius: 4px !important;
	font-size: 12px !important;
	display: flex;
	line-height: 1.4;
	align-items: center;
	height: 25px !important:
}

.chosen-container-single {
    border-radius: 4px;
    font-size: 12px !important;
}
</style>
<!-- POCOR-9225[END] -->
<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => $this->request->getParam('action'),
        ]);

        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->setTemplates($template);

        echo $this->Form->control('config_item_type', [
            'class' => 'form-control chosenSelect', //POCOR-9225
            'label' => false,
            'type' => 'select',
            'options' => ['-1' => __('-- Select Type --')] + $typeOptions, // POCOR-9427
			'url' => $baseUrl,
            'data-named-key' => 'type',
        ]);

        // POCOR-8951 start
        if ($this->request->getParam('action') === 'Themes') {
            echo $this->Form->control('online_service', [
                'class' => 'form-control chosenSelect', //POCOR-9225
                'label' => false,
                'type' => 'select',
                'options' => $productThemes,
                'default' => $selectedProduct ?? 'openemis_core',
                'url' => $baseUrl,
                'empty' => 'Select Product',
                'data-named-key' => 'online_service',
                'data-named-group' => 'type',
            ]);
        }
        // POCOR-8951 end
        ?>
    </div>
</div>

