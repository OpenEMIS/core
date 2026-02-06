<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Directory.angular/directoryadd/directory.directoryadd.svc', ['block' => true]); ?>
<?= $this->Html->script('Directory.angular/directoryadd/directory.directoryadd.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min.js', ['block' => true]); ?>

<style type="text/css">
.breadcrumb {
  padding: 8px 15px !important;
  margin-bottom: 0px !important;
  list-style: none !important;
  background-color: white !important;
  border-radius: 4px !important;
}
.breadcrumb.panel-breadcrumb li {
  direction: ltr !important;
}

.panel-breadcrumb {
  background-color: #FFF!important;
  border-bottom: 1px solid #DDD!important;
  border-radius: 0!important;
  padding: 8px 0!important;
  margin: 0!important;
}
.page-header {
    padding-bottom: 9px!important;
    margin: 0px !important;
    border-bottom: 1px solid #DDD !important;
}

.h2, h2 {
    font-size: 20px!important;
    font-weight: 400!important;
}

.page-header h2 {
    display: inline-block!important;
    position: relative!important;
    padding: 8px 24px 8px 0!important;
    margin: 0!important;
    max-width: 350px!important;
    white-space: nowrap!important;
    overflow: hidden!important;
    text-overflow: ellipsis!important;
}

.breadcrumb > li + li::before {
    font-family: 'FontAwesome'!important;
    content: "\f054"!important;
    font-size: 0.6em!important;
    color: #999!important;
    font-weight: normal!important;
    margin: 4px!important;
    line-height: 12px!important;
    display: inline!important;
    float: left!important;
}

.breadcrumb > li + li::before {
    padding: 0 5px!important;
    content: "/\00a0";
}
.content-wrapper {
    float: none!important;
    clear: both!important;
    height: auto !important;
}

.alert_warn{
    color: #8a6d3b !important;
    border-color: #faebcc !important;
    background-color: #E6BA64 !important;
    border: 1px solid #E6BA64 !important;
}
</style>

<!--<link data-require="bootstrap@3.3.2" data-semver="3.3.2" rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" />-->
<!--<script data-require="bootstrap@3.3.2" data-semver="3.3.2" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>-->
<!--<script data-require="angularjs@1.4.9" data-semver="1.4.9" src="https://code.angularjs.org/1.4.9/angular.min.js"></script>-->
<!--<script data-require="ui-bootstrap@*" data-semver="1.3.2" src="https://cdn.rawgit.com/angular-ui/bootstrap/gh-pages/ui-bootstrap-tpls-1.3.2.js"></script>-->
<?php
    $baseUrl = $this->Url->build([
        'plugin' => $this->request->getParam('plugin'),
        'controller' => $this->request->getParam('controller'),
        'action' => $this->request->getParam('action'),
    ]);
    if (empty($homeUrl)) {
        $homeUrl = [];
    }
    $backUrl = [
        'plugin' => $this->request->getParam('plugin'),
        'controller' => $this->request->getParam('controller'),
        'action' => $this->request->getParam('action'),
        'index'
    ];
?>

<div class="content-wrapper">
    <?= $this->element('OpenEmis.breadcrumbs') ?>

    <div class="page-header">
        <h2 id="main-header"><?= __('Directory') ?></h2>
        <div class="toolbar ">
            <?= $this->Html->link(
                '<i class="fa kd-back"></i>',
                $backUrl,
                [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'data-container' => 'body',
                    'title' => __('Back'),
                    'escape' => false
                ]
            ); ?>
        </div>
    </div>
</div>

<div class="pd-10" ng-controller = 'DirectoryAddCtrl'>
    <div class="alert {{messageClass}}" ng-if="message">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
    </div>
    <div class="stepper-content-wrapper">
        <div class="steps-container">
            <ul class="steps" style="margin-left: 0">
                <li ng-class="{'active': step === 'user_details'}">
                    <div class="stepper-steps-wrapper">
                        <?= __('User Details') ?>
                        <span class="chevron"></span></div>
                </li>
                <li ng-class="{'active': step === 'internal_search'}">
                    <div class="stepper-steps-wrapper">
                        <?= __('Internal Search') ?>
                        <span class="chevron"></span></div>
                </li>
                <li ng-if="isExternalSearchEnable" ng-class="{'active': step === 'external_search'}">
                    <div class="stepper-steps-wrapper">
                        <?= __('External Search') ?>
                        <span class="chevron"></span></div>
                </li>
                <li ng-class="{'active': step === 'confirmation'}">
                    <div class="stepper-steps-wrapper">
                        <?= __('Confirmation') ?>
                        <span class="chevron"></span></div>
                </li>
                <li ng-class="{'active': step === 'summary'}">
                    <div class="stepper-steps-wrapper"><?= __('Summary') ?><span class="chevron"></span></div>
                </li>
            </ul>
        </div>
        <div class="actions top">
            <button ng-if="(step=='user_details') || isNextButtonShouldDisable()"
                    type="button"
                    class="btn close-btn"
                    ng-click="cancelProcess()"
                    style="font-size: 12px;"><?= __('Cancel') ?></button>
            <button ng-if="(step!=='user_details' && step!=='summary')"
                    type="button"
                    class="btn btn-prev close-btn"
                    ng-click="goToPrevStep()" style="font-size: 12px;"><?= __('Back') ?></button>
            <button ng-disabled="(error | json) != '{}' || isNextButtonShouldDisable()"
                    type="button"
                    class="btn btn-default btn-next"
                    ng-if="step!=='confirmation' && step!=='summary'"
                    ng-click="goToNextStep()" style="font-size: 12px;"><?= __('Next') ?></button>
            <button ng-if="(step=='confirmation' && step!=='summary')"
                    type="button"
                    class="btn btn-default"
                    ng-click="validateConfirmDetails()"
                    ng-disabled="(error | json) != '{}'"
                    style="font-size: 12px;"><?= __('Confirm') ?></button>
            <button ng-if="(step=='summary')"
                    type="button"
                    class="btn close-btn"
                    ng-click="cancelProcess()"
                    style="font-size: 12px;"><?= __('Close') ?></button>
            <button type="button"
                    class="btn btn-default btn-next"
                    ng-if="step==='summary' && redirectToGuardian"
                    ng-click="addGuardian()"
                    style="font-size: 12px;"><?= __('Add Guardian') ?></button>
        </div>
        <div class="step-content">
            <div class="step-pane sample-pane" ng-show="step === 'user_details'">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                    <?= $this->element('Directory.user_details_user_type_id') ?>
                    <?= $this->element('Directory.user_details_openemis_no') ?>
                    <?= $this->element('Directory.user_details_identity') ?>
                    <?= $this->element('Directory.user_details_basic_information') ?>
                </form>
            </div>
            <div class="step-pane sample-pane" ng-if="step === 'internal_search'">
                <div class="table-wrapper">
                    <div>
                        <div class="scrolltabs">
                            <div id="institution-student-table" class="table-wrapper">
                                <div ng-if="internalGridOptions" kd-ag-grid="internalGridOptions" ag-selection-type="radio" class="ag-height-fixed"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane sample-pane" ng-if="step === 'external_search'">
                <div class="table-wrapper">
                    <div>
                        <div class="scrolltabs sticky-content">
                            <div id="institution-student-table" class="table-wrapper">
                                <div ng-if="externalGridOptions" kd-ag-grid="externalGridOptions" ag-selection-type="radio" class="ag-height-fixed"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane sample-pane" ng-show="step === 'confirmation'">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                    <div class="row section-header header-space-lg"><?= __('Information') ?></div>
                    <?= $this->element('Directory.confirmation_photo_content') ?>
                    <?= $this->element('Directory.confirmation_basic_user_fields') ?>
                    <?= $this->element('Directory.confirmation_address_fields') ?>
                    <?= $this->element('Directory.confirmation_directory_additional') ?>
                    <?= $this->element('Directory.confirmation_username_password') ?>
                    <?= $this->element('Directory.confirmation_custom_fields') ?>
                </form>
            </div>
            <div class="step-pane sample-pane active" ng-if="step === 'summary'">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post" style="margin: 0;">
                    <?= $this->element('Directory.summary_form') ?>
                </form>
            </div>
        </div>
        <div class="actions bottom">
        </div>
    </div>
</div>

<!-- <script  POCOR-8613>
$(function () {
var datepicker0 = $('#User_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true, language: '<?php echo $dateLanguage; ?>'});
$( document ).on('DOMMouseScroll mousewheel scroll', function(){
    window.clearTimeout( t );
    t = window.setTimeout( function(){
        datepicker0.datepicker('place');
    });
});
}); 

//]]>
</script> -->

<style>
    .pd-10 {
        padding: 20px 25px
    }
    .close-btn {
        border: 1px solid #000;
    }
    .header-space-lg{
        margin-bottom: 20px !important
    }
    .mb-16{
        margin-bottom: 16px;
    }
    .font-italic{
        font-style: italic;
    }
    .mb-0{
        margin-bottom: 0;
    }
    .d-flex{
        display: flex;
    }
    .position-relative{
        position: relative;
    }
    .fontSize-16{
        font-size: 16px !important;
    }
    .input-hidden{
        opacity: 0;
        position: absolute;
        width: 100% !important;
        height: 100% !important;
        left: 0;
        top: 0;
    }
    .row-content{
        margin-bottom: 16px;
    }
    .vertical-align-top {
        vertical-align: top !important;
    }
    /* stepper container wrapper */
    .stepper-content-wrapper {
        border: 1px solid #d4d4d4;
        border-radius: 4px;
        box-shadow: 0 1px 4px rgb(0 0 0 / 7%);
        background-color: #f9f9f9;
        position: relative;
        min-height: 610px;
    }
    .stepper-content-wrapper{
        -webkit-box-shadow: none!important;
        box-shadow: none!important;
        border: 0;
        background-color: #FFF;
        margin-bottom: 10px;
    }
    .stepper-content-wrapper:before,
    .stepper-content-wrapper:after {
        display: table;
        content: "";
        line-height: 0;
    }
    /* steps container */
    .stepper-content-wrapper .steps-container {
        border-radius: 4px 4px 0 0;
        overflow: hidden;
        border: 1px solid #DDD;
        height: 48px;
        border-bottom: none;
    }
    /* steps */

    .stepper-content-wrapper > ul.steps li,
    .stepper-content-wrapper > .steps-container > ul.steps li {
        float: left;
        margin: 0;
        padding: 0 20px 0 30px;
        height: 46px;
        line-height: 46px;
        position: relative;
        background: #ededed;
        color: #999999;
        font-size: 16px;
        cursor: not-allowed;
    }
    .stepper-content-wrapper > ul.steps li,
    .stepper-content-wrapper > .steps-container > ul.steps li {
        float: left;
        margin: 0;
        padding: 8px 20px 0 30px;
        height: 48px;
        line-height: 32px;
        position: relative;
        background: #ededed;
        color: #999;
        font-size: 12px;
        cursor: not-allowed;
        border-bottom: none;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background: #f1f6fc;
        color: #3a87ad;
        cursor: default;
    }
    .stepper-content-wrapper > ul.steps li:first-child,
    .stepper-content-wrapper > .steps-container > ul.steps li:first-child {
        border-radius: 4px 0 0 4px;
        padding-left: 20px;
    }
    .stepper-content-wrapper > ul.steps li:first-child,
    .stepper-content-wrapper > .steps-container > ul.steps li:first-child {
        -webkit-border-radius: 4px 0 0 0;
        border-radius: 4px 0 0 0;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        line-height: 30px;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background-color: #999;
        color: #FFF;
        cursor: default;
        border-bottom: none;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background-color: #69C;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background-color: #6699CC;
    }
    .stepper-content-wrapper .steps {
        margin-left: 0!important;
    }
    .stepper-content-wrapper > ul.steps,
    .stepper-content-wrapper > .steps-container > ul.steps {
        list-style: none outside none;
        padding: 0;
        margin: 0;
        width: 999999px;
    }
    /* step wrapper */
    .stepper-steps-wrapper {
        overflow: hidden;
        width: 100%;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-align: center;
    }
    /* chevron */
    .stepper-content-wrapper > ul.steps li .chevron,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron {
        border: 24px solid transparent;
        border-left: 14px solid #d4d4d4;
        border-right: 0;
        display: block;
        position: absolute;
        right: -14px;
        top: 0;
        z-index: 1;
    }
    .stepper-content-wrapper > ul.steps li .chevron,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron {
        border: 24px solid transparent;
        border-left: 14px solid #d4d4d4;
        border-right: 0;
        display: block;
        position: absolute;
        right: -15px;
        top: -5px;
        z-index: 1;
        margin-top: 5px;
        transform: scale(1.1,1.1);
        -ms-transform: scale(1.1,1.1);
        -webkit-transform: scale(1.1,1.1);
    }
    /* chevron before */
    .stepper-content-wrapper > ul.steps li .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron:before {
        border: 24px solid transparent;
        border-left: 14px solid #ededed;
        border-right: 0;
        content: "";
        display: block;
        position: absolute;
        right: 1px;
        top: -24px;
    }
    .stepper-content-wrapper > ul.steps li .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron:before {
        border: 24px solid transparent;
        border-left: 14px solid #ededed;
        border-right: 0;
        content: "";
        display: block;
        position: absolute;
        right: 1px;
        top: -24px;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron:before {
        border-left: 14px solid #f1f6fc;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron:before {
        border-left: 14px solid #999;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron::before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron::before {
        border-left-color: #69C;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron::before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron::before {
        border-left-color: #6699CC;
    }
    /* action buttons */
    .stepper-content-wrapper > .actions {
        z-index: 1000;
        position: absolute;
        right: 0;
        top: 0;
        line-height: 46px;
        float: right;
        padding-left: 15px;
        padding-right: 15px;
        vertical-align: middle;
        background-color: #e5e5e5;
        border-left: 1px solid #d4d4d4;
        border-radius: 0 4px 0 0;
    }
    .stepper-content-wrapper > .actions {
        background-color: #f9f9f9;
        border-left: 1px solid #d4d4d4;
        border-radius: 0 4px 0 0;
        float: right;
        line-height: 47px;
        padding-left: 15px;
        padding-right: 15px;
        position: absolute;
        right: 1px;
        top: 1px;
        vertical-align: middle;
        z-index: 500;
    }
    /* step content */
    .stepper-content-wrapper .step-content {
        border-top: 1px solid #D4D4D4;
        padding: 10px;
        float: left;
        width: 100%;
    }
    .stepper-content-wrapper .step-content {
        border-top: 1px solid #DDD;
        border-right: 1px solid #DDD;
        border-left: 1px solid #DDD;
        border-bottom: 1px solid #DDD;
        border-radius: 0 0 4px 4px;
        float: left;
        width: 100%;
    }
    @media (min-width: 800px) {
        .row-content{
            display: flex;
            align-items: flex-start;
        }
    }
    @media only screen and (max-width: 800px){
        .stepper-content-wrapper .actions.top {
        position: fixed!important;
        bottom: 35px!important;
        top: auto!important;
        height: 50px;
        left: 0!important;
        right: 0!important;
        border-top: 1px solid #DDD;
        border-left-color: transparent;
        -webkit-border-radius: 0!important;
        border-radius: 0!important;
        }
        .stepper-content-wrapper .btn-prev,
        .stepper-content-wrapper .btn-next {
            position: relative;
        }
        .stepper-content-wrapper .btn-next {
            text-align: left;
            padding-right: 20px;
        }
        .stepper-content-wrapper .actions.top .btn-next,
        .stepper-content-wrapper .actions.top .btn-prev {
            position: absolute;
            top: 10px;
        }
        .stepper-content-wrapper .actions.top .btn-next {
            right: 10px;
        }
    }

    .uib-title {
        background-color: #fff !important;
        color: #333 !important;
        margin-top: -22px;
        border-color: #fff !important;
    }

    .uib-title:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-left, .uib-right {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-left:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-right:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-day .btn-sm {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-day .btn-sm:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-month .btn-default {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-month .btn-default:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-years .btn-default {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-years .btn-default:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-datepicker-popup {
        padding: 5px 10px;
    }

    .uib-close {
        display: none !important;
    }

    .uib-datepicker-current {
        width: 230% !important;
        border-radius: 3px !important;
        border-color: #fff !important;
        color: #000 !important;
        background: #fff !important;
    }

    .uib-datepicker-current:hover {
        background: #eee !important;
    }

    .uib-clear {
        border-color: #fff !important;
        border-radius: 3px !important;
        color: #000 !important;
        background: #fff !important;
    }

    .uib-clear:hover {
        background: #eee !important;
    }

    .time {
        margin-top: -15px !important;
        margin-bottom: 5px !important;
    }

    .hours {
        vertical-align: middle !important;
    }

    .minutes {
        vertical-align: middle !important;
    }

    .check_box {
        margin-top: 2px !important;
        display: inline-flex !important;
    }

    .file-input {
        width: 200% !important;
        border: 0px !important;
        font-size: 14px !important;
        height: 40px !important;
        padding-left: 0px !important;
    }

    .alert {
        color: #FFF !important;
        padding: 10px !important;
        margin: 0 0 10px !important;
    }

    .alert-success {
        background-color: #77B576 !important;
        border: 1px solid #77B576 !important;
        color:
    }

</style>
