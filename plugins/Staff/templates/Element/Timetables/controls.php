<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
    <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => $this->request->getParam('action'),
            '0'=>'index',
            '1' => $encodedQueryString,
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template);

        
        echo $this->Form->input('institution_id', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $selectedInstitutionOptions,
            'url' => $baseUrl,
            'data-named-key' => 'institution_id',
            //'default' => $selectedInstitutionOptions,
        ));
        
        echo $this->Form->input('shift', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $shiftOptions,
            'url' => $baseUrl,
            'data-named-group' => 'institution_id',
            'data-named-key' => 'shift',
            'default' => $shiftDefaultId
        ));
    ?>
    </div>
</div>  
