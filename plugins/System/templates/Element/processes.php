<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
            $baseUrl = $this->Url->build([
                'plugin' => $this->request->getParam('plugin'),
                'controller' => $this->request->getParam('controller'),
                'action' => $this->request->getParam('action'),
                0 => 'index',
            ]);

            $template = $this->ControllerAction->getFormTemplate();
            $this->Form->templates($template);

            echo $this->Form->control('status', [
                'class' => 'form-control',
                'label' => false,
                'options' => $statusOption,             
                'default' => $selectedStatus,            
                'url' => $baseUrl,
                'data-named-key' => 'status'
            ]);

            echo $this->Form->control('features', [
                'class' => 'form-control',
                'label' => false,
                'options' => $featuresOption,                    
                'default' => $selectedFeaturesOption,    
                'url' => $baseUrl,
                 'data-named-group' => 'status',
                'data-named-key' => 'features'
            ]);
        ?>
    </div>
</div>