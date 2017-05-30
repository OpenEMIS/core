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

            if (!empty($academicPeriodList)) {
                echo $this->Form->input('academic_period', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $academicPeriodList,
                    'url' => $baseUrl,
                    'default' => $selectedAcademicPeriod,
                    'data-named-key' => 'academic_period',
                ));
            }
        ?>
    </div>
</div>

