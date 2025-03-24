<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
            $baseUrl = $this->Url->build([
                'plugin' => $this->request->getParam('plugin'),
                'controller' => $this->request->getParam('controller'),
                'action' => $this->request->getParam('action')
            ]);
            $template = $this->ControllerAction->getFormTemplate();
            $this->Form->templates($template);
        // POCOR-8919
            ?>

        <div class="input select">
            <div class="input-select-wrapper">
                <select class="form-control"
                    ng-options="item.value as item.text for item in ExaminationsResultsController.examinationOptions"
                    ng-model="ExaminationsResultsController.examinationId"
                    disabled="disabled"
                ></select>
            </div>
        </div>
        <div class="input select">
            <div class="input-select-wrapper">
                <select class="form-control"
                    ng-options="item.value as item.text for item in ExaminationsResultsController.examinationCentreOptions"
                    ng-model="ExaminationsResultsController.examinationCentreId"
                    disabled="disabled"
                ></select>
            </div>
        </div>
    </div>
</div>
