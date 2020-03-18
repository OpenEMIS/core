<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php        
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->params['plugin'],
            'controller' => $this->request->params['controller'],
            'action' => $this->request->params['action'],
            '0' => 'index'
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template);

        if (!empty($academicPeriodOptions)) {
            echo $this->Form->input('academic_period_id', array(
                'class' => 'form-control',
                'label' => false,
                'options' => $academicPeriodOptions,
                'url' => $baseUrl,
                'default' => $academicPeriodId,
                'data-named-key' => 'academic_period_id'
            ));
        }
        ?>
    </div>
</div>
