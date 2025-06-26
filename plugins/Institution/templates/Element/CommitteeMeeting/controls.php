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
        $this->Form->templates($template);

        echo $this->Form->input('period', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $periodOptions,
            'url' => $baseUrl,
            'data-named-key' => 'period',
            'default' => $selectedPeriodOption,
        ));

        echo $this->Form->input('type', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $committeeTypeOption,
            'url' => $baseUrl,
            'data-named-group' => 'period',
            'data-named-key' => 'type',
            'default' => $selectedCommiteeTypeOption
        ));
    ?>
    </div>
</div>  
