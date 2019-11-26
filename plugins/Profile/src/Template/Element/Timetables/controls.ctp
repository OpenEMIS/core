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
        
        if(!empty($shiftOptions)){
            echo $this->Form->input('shift', array(
                'class' => 'form-control',
                'label' => false,
                'options' => $shiftOptions,
                'url' => $baseUrl,
                'data-named-group' => 'intitution_id',
                'data-named-key' => 'shift',
                'default' => $shiftDefaultId
            ));
        }
        
        if(!empty($scheduleIntervals)){
            echo $this->Form->input('schedule_interval_id', array(
                'class' => 'form-control',
                'label' => false,
                'options' => $scheduleIntervals,
                'url' => $baseUrl,
                'data-named-group' => 'shift',
                'data-named-key' => 'schedule_interval_id',
                'default' => $scheduleIntervalDefaultId
            ));
        }
    ?>
    </div>
</div>  
