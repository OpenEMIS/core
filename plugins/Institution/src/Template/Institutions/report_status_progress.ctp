<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');

$this->end();

$this->start('panelBody');


$tableHeaders = [
    __('Name'),
    __('Status'),
    __('Progress'),
    __('Action')
];

$params = $this->request->params;
$url = ['plugin' => $params['plugin'], 'controller' => $params['controller'],
    'action' => 'ajaxGetReportCardStatusProgress',
    'academic_period_id' => $this->request->query('academic_period_id'),
    'report_card_id' => $this->request->query('report_card_id'),
    'institution_id' => $institutionId
];
$url = $this->Url->build($url);
$table = $ControllerAction['table'];
?>
<?php if (!empty($academicPeriodOptions)) : ?>
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

            if (!empty($academicPeriodOptions)) {
                echo $this->Form->input('academic_period_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $academicPeriodOptions,
                    'default' => $selectedAcademicPeriod,
                    'url' => $baseUrl,
                    'data-named-key' => 'academic_period_id'
                ));
            }

            if (!empty($reportCardOptions)) {
                echo $this->Form->input('report_card_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $reportCardOptions,
                    'default' => $selectedReportCard,
                    'url' => $baseUrl,
                    'data-named-key' => 'report_card_id',
                    'data-named-group' => 'academic_period_id,class_id'
                ));
            }

            if (!empty($classOptions)) {
                echo $this->Form->input('class_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $classOptions,
                    'default' => $selectedClass,
                    'url' => $baseUrl,
                    'data-named-key' => 'class_id',
                    'data-named-group' => 'academic_period_id,report_card_id'
                ));
            }
            ?>
        </div>
    </div>
<?php endif ?>

<style type="text/css">
    .none { display: none !important; }
</style>
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table table-curved" id="ReportList" url="<?= $url ?>" data-downloadtext="<?= $downloadText ?>">
            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
            <tbody>
                <?php //echo '<pre>';print_r($data);die; ?>
                <?php
                foreach ($data as $obj) :

                    $progress = 0;
                    $current = $obj->inCompleted;
                    $total = $obj->inCompleted + $obj->inProcess;

                    if ($current > 0 && $total > 0) {
                        $progress = intval(($current / $total ) * 100);
                    }
                    
                    if($progress == 100){
                       ?>
                       <tr>
                        <?php
                    }else{
                     ?>
                       <tr row-id="<?= $obj->id ?>">
                        <?php   
                    }
                    ?>
                    
                        <td><?= $obj->name ?></td>
                        <td class="modified">
                            <?php
                            if ($progress == 100) {
                                echo __('Completed');
                            } else {
                                echo __('In Progress');
                            }
                            ?>

                        </td>
                        <td class="expiryDate">
                        <?php
                        if ($progress == 100) {
                            echo $progress . '%';
                        } else {
                            echo '<div class="progress progress-striped active" style="margin-bottom:0">';
                            echo '<div class="progress-bar progress-bar-striped" role="progressbar" data-transitiongoal="' . $progress . '"></div>';
                            echo '</div>';
                        }
                        ?>

                        </td>
                        <td class="rowlink-skip"><div class="dropdown">
                                <button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="false">
                                    Select<span class="caret-down"></span>
                                </button>
                                <?php
                                $paramsA = [
                                    'institution_id' => $obj->institution_id,
                                    'institution_class_id' => $obj->id,
                                    'report_card_id' => $this->request->query('report_card_id')
                                ];
                                $queryString = $this->Resource->paramsEncode($paramsA);
                                $generateAllUrl = ['plugin' => $params['plugin'],
                                    'controller' => $params['controller'],
                                    'action' => $params['action'],
                                    'generateAll',
                                    'institution_id' => $obj->institution_id,
                                    'academic_period_id' => $this->request->query('academic_period_id'),
                                    'class_id' => $obj->id,
                                    'report_card_id' => $this->request->query('report_card_id'),
                                    'queryString' => $queryString
                                ];
                                $viewUrl = ['plugin' => $params['plugin'],
                                    'controller' => $params['controller'],
                                    'action' => $params['action'],
                                    'class_id' => $obj->id,
                                    'academic_period_id' => $this->request->query('academic_period_id'),
                                    'report_card_id' => $this->request->query('report_card_id')
                                ];
                                ?>
                                <ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
                                    <div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>
                                    <li role="presentation">
                                        <a href="<?php echo $this->Url->build($viewUrl); ?>" role="menuitem" tabindex="-1"><i class="fa fa-eye"></i>View</a>			
                                    </li>
                                    <li role="presentation">
                                        <a href="<?php echo $this->Url->build($generateAllUrl); ?>" role="menuitem" tabindex="-1"><i class="fa fa-refresh"></i>Generate All</a>			
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$this->end();
