<div class="wrapper">
    <div class="wrapper-child">
        <div class="panel">
            <div class="panel-body">
                <div class="row section-header"><?= __('Information') ?></div>
                <div class="row row-content hidden"></div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Photo Content') ?></div>
                    <div class="form-input">
                        <div class="table-thumb">
                            <div class="profile-image-thumbnail">
                                <i class="kd-staff"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row row-content">
                    <?php $dynamicOpenemisNoHeader = (isset($dynamicOpenemisNoHeader) && !empty($dynamicOpenemisNoHeader)) ? h($dynamicOpenemisNoHeader) : __('OpenEMIS ID'); ?>
                    <div class="col-xs-6 col-md-3 form-label"><?= $dynamicOpenemisNoHeader ?></div>
                    <div class="form-input">{{selectedUserData.openemis_no}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('First Name') ?></div>
                    <div class="form-input">{{selectedUserData.first_name}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Middle Name') ?></div>
                    <div class="form-input">{{selectedUserData.middle_name}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Third Name') ?></div>
                    <div class="form-input">{{selectedUserData.third_name}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Last Name') ?></div>
                    <div class="form-input">{{selectedUserData.last_name}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Preferred Name') ?></div>
                    <div class="form-input">{{selectedUserData.preferred_name}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Gender') ?></div>
                    <div class="form-input">{{selectedUserData.gender.name}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Date of Birth') ?></div>
                    <div class="form-input">{{selectedUserData.date_of_birth}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Email') ?></div>
                    <div class="form-input">{{selectedUserData.email}}</div>
                </div>
                <div class="row section-header"><?= __('Identities / Nationalities') ?></div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Details') ?></div>
                    <div class="form-input"
                         ng-if="selectedUserData.identity_type_name || selectedUserData.identity_number || selectedUserData.nationality_name">
                        <div class="form-input table-full-width">
                            <div class="table-wrapper">
                                <div class="table-in-view">
                                    <table class="table" style="margin-bottom: 0px !important;">
                                        <thead>
                                        <tr>
                                            <th><?= __('Identity Type') ?></th>
                                            <th><?= __('Identity Number') ?></th>
                                            <th><?= __('Nationality') ?></th>
                                            <th><?= __('Preferred') ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td class="vertical-align-top">
                                                {{selectedUserData.identity_type_name}}
                                            </td>
                                            <td class="vertical-align-top">
                                                {{selectedUserData.identity_number}}
                                            </td>
                                            <td class="vertical-align-top">
                                                {{selectedUserData.nationality_name}}
                                            </td>
                                            <td class="vertical-align-top"><?= __('Yes') ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row section-header"><?= __('Location') ?></div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Address') ?></div>
                    <div class="form-input">{{selectedUserData.address}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Postal Code') ?></div>
                    <div class="form-input">{{selectedUserData.postalCode}}</div>
                </div>
                <div class="row section-header"><?= __('Address Area') ?></div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Address Area') ?></div>
                    <div class="form-input">{{selectedUserData.addressArea.name}}</div>
                </div>
                <div class="row section-header"><?= __('Birthplace Area') ?></div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Birthplace Area') ?></div>
                    <div class="form-input">{{selectedUserData.birthplaceArea.name}}</div>
                </div>
                <div class="row section-header"><?= __('Other Information') ?></div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Modified By') ?></div>
                    <div class="form-input"><?= __('System Administrator') ?></div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Modified On') ?></div>
                    <div class="form-input">{{todayDate}}</div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Created By') ?></div>
                    <div class="form-input"><?= __('System Administrator') ?></div>
                </div>
                <div class="row row-content">
                    <div class="col-xs-6 col-md-3 form-label"><?= __('Created On') ?></div>
                    <div class="form-input">{{todayDate}}</div>
                </div>
            </div>
        </div>
    </div>
</div>
