<?php if (!empty($examinationOptions) || !empty($roomOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $url = [
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action'],
                    'index'
                ];

                if (!empty($queryString)) {
                    $url['queryString'] = $queryString;
                }

                $baseUrl = $this->Url->build($url);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($examinationOptions)) {
                    echo $this->Form->input('examination', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $examinationOptions,
                        'default' => $selectedExamination,
                        'url' => $baseUrl,
                        'data-named-key' => 'examination_id'
                    ));
                }

                if (!empty($roomOptions)) {
                    echo $this->Form->input('examination_centre_room', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $roomOptions,
                        'default' => $selectedRoom,
                        'url' => $baseUrl,
                        'data-named-key' => 'examination_centre_room_id',
                        'data-named-group' => 'examination_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
