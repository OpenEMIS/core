<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
    <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => $this->request->getParam('action'),
            '0' => 'index',
            '1' => $encodedQueryString,
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template); ?>
        <?php echo $this->Form->input('period', array(
            'type' => 'select',
            'class' => 'form-control',
            'label' => false,
            'options' => $periodOptions,
            'url' => $baseUrl,
            'data-named-key' => 'period',
            'default' => $selectedPeriodOption,
        )); ?>
        <?php echo $this->Form->input('shift', array(
            'type' => 'select',
            'class' => 'form-control',
            'label' => false,
            'options' => $shiftOptions,
            'url' => $baseUrl,
            'data-named-group' => 'period',
            'data-named-key' => 'shift',
            'default' => $selectedShiftOption
        )); ?>
    </div>
</div>  
