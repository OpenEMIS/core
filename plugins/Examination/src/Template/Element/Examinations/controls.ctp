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
        ?>
        <div class="input select">
            <div class="input-select-wrapper">
                <select class="form-control"
                    ng-options="item.value as item.text for item in ExaminationsResultsController.academicPeriodOptions"
                    ng-model="ExaminationsResultsController.academicPeriodId"
                    ng-change="ExaminationsResultsController.onChangeAcademicPeriod(ExaminationsResultsController.academicPeriodId);"
                ></select>
            </div>
        </div>
        <div class="input select">
            <div class="input-select-wrapper">
                <select class="form-control"
                    ng-options="item.value as item.text for item in ExaminationsResultsController.examinationOptions"
                    ng-model="ExaminationsResultsController.examinationId"
                    ng-change="ExaminationsResultsController.onChangeExamination(ExaminationsResultsController.academicPeriodId, ExaminationsResultsController.examinationId);"
                ></select>
            </div>
        </div>
        <div class="input select">
            <div class="input-select-wrapper">
                <select class="form-control"
                    ng-options="item.value as item.text for item in ExaminationsResultsController.examinationCentreOptions"
                    ng-model="ExaminationsResultsController.examinationCentreId"
                    ng-change="ExaminationsResultsController.onChangeExaminationCentre(ExaminationsResultsController.academicPeriodId, ExaminationsResultsController.examinationId, ExaminationsResultsController.examinationCentreId);"
                ></select>
            </div>
        </div>
    </div>
</div>
