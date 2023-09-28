<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
    <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => $this->request->getParam('action')
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template); ?>

        <div class="input select required">
            <div class="input-select-wrapper">
            <?php echo $this->Form->input('period', array(
                'type' => 'select',
                'class' => 'form-control',
                'label' => false,
                'options' => $periodOptions,
                'url' => $baseUrl,
                'data-named-key' => 'period',
                'default' => $selectedPeriodOption,
            )); ?>
            </div>
        </div>
    </div>
</div>  
