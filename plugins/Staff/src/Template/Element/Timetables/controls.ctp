<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
    <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->params['plugin'],
            'controller' => $this->request->params['controller'],
            'action' => $this->request->params['action']
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template);

        
        echo $this->Form->input('intitution_id', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $selectedInstitutionOptions,
            'url' => $baseUrl,
            'data-named-key' => 'intitution_id',
            //'default' => $selectedInstitutionOptions,
        ));
        
        echo $this->Form->input('shift', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $shiftOptions,
            'url' => $baseUrl,
            'data-named-group' => 'intitution_id',
            'data-named-key' => 'shift',
            'default' => $shiftDefaultId
        ));
    ?>
    </div>
</div>  
