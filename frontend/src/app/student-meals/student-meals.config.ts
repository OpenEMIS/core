import { IMiniDashboardConfig } from "openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface";

export const MINI_DASHBOARD_MEAL_CONFIG: IMiniDashboardConfig = {
    closeButtonDisabled: false,
    // rtl: true,
};

interface TableColumns {
    openEmisId?: any;
    personName?: any;
    mealReceived?: any;
    mealBenefit?: any;
}

const COLUMN_OPENEMISID: any = {
    headerName: "OpenEMIS ID",
    field: "user.openemis_no",
    pinned: 'left',
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false
}

const COLUMN_PERSONNAME: any = {
    headerName: "Name",
    field: "user.full_name",
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false,
    pinned: 'left',
    menuTabs: ['filterMenuTab']
}

const COLUMN_MEAL_RECEIVED: any = {
    headerName: 'Meal Received',
    field: 'meal_benefit_id',
    menuTabs: [],
    suppressSorting: true,
    cellRenderer: (params) => {
        if (params.hasOwnProperty('value')) {
            let context = params.context;
            let mealTypes = context.mealTypes;
            let isMarked = context.isMarked;
            let isSchoolClosed = params.context.schoolClosed;
            let mode = params.context.mode;
            let data = params.data;
            // console.log(mode,"mode mode");

            if (mode == 'view') {
                return getViewMealElement(data, mealTypes, isMarked, isSchoolClosed);
            } else if (mode == 'edit') {
                let api = params.api
                return getEditMealElement(data, mealTypes, api, context);
            }
        }

    }
}

const COLUMN_MEAL_BENEFIT: any = {
    headerName: 'Benefit Type',
    field: 'meal_benefit_name',
    menuTabs: [],
    suppressSorting: true,
    cellRenderer: (params) => {
        if (params.hasOwnProperty('value')) {
            let context = params.context;
            let mealTypes = context.mealTypes;
            let mode = context.mode;
            let data = params.data;
            let mealBenefitTypeOptions = context.mealBenefitTypeOptions;
            // console.log(mealBenefitTypeOptions,"mealBenefitTypeOptions");
            if (params.data.hasOwnProperty('meal_benefit_name')) {
                let studentMealTypeId = (params.data.meal_received_id == null) ? null : params.data.meal_received_id;
                let mealTypeObj = mealTypes.find(obj => obj.id == studentMealTypeId);
                if (mode == 'view') {
                    if (studentMealTypeId == 1) {
                        if (params.data.meal_benefit_id) {
                            let idName = params.context.mealBenefitTypeOptions.findIndex((data: any) => {
                                if (params?.data?.meal_benefit_id) {
                                    if (data.id == params.data.meal_benefit_id) {
                                        return data.id
                                    }
                                } else {
                                    return 1
                                }
                            })
                            return `<span>${params.context.mealBenefitTypeOptions[idName].name}</span>`
                        } 
                    } else
                        if (studentMealTypeId == 2 || studentMealTypeId == 3 || studentMealTypeId == null) {
                            return '<i style="color: #999999;" class="fa fa-minus"></i>';
                        } else if (studentMealTypeId == 1 && params.data.meal_benefit_name == null) {
                            let idName = params.context.mealBenefitTypeOptions.findIndex((data: any) => {
                                if (params?.data?.meal_benefit_id) {
                                    if (data.id == params.data.meal_benefit_id) {
                                        return data.id
                                    }
                                } else {
                                    return 1
                                }
                            })
                            return `<span>${params.context.mealBenefitTypeOptions[idName].name}</span>`
                        } else {
                            return '<i style="color: #999999;" class="fa fa-minus"></i>';
                        }
                } else if (mode == 'edit') {
                    let api = params.api;
                    if (mealTypeObj != undefined) {
                        switch (mealTypeObj.name) {
                            case 'None':
                                return '<i style="color: #999999;" class="fa fa-minus"></i>';
                            case 'Not Received':
                                return '<i style="color: #999999;" class="fa fa-minus"></i>';
                            case 'Received':
                                let eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                let eSelect = getEditMealBenefiteElement(data, mealBenefitTypeOptions, context, api);
                                eCell.appendChild(eSelect);
                                return eCell;
                        }
                    } else {
                        return '<i style="color: #999999;" class="fa fa-minus"></i>';
                    }
                }
            }
        }
    }
}

export const TABLE_COLUMN_LIST: TableColumns = {
    openEmisId: COLUMN_OPENEMISID,
    personName: COLUMN_PERSONNAME,
    mealReceived: COLUMN_MEAL_RECEIVED,
    mealBenefit: COLUMN_MEAL_BENEFIT
};

function getViewMealElement(data: any, mealTypes: any, isMarked: any, isSchoolClosed: any) {
    if (data.hasOwnProperty('meal_received_id')) {
        let html = ''

        if (data.meal_received_id == 1) {
            // html = data.institution_student_meal.meal_received
            html = 'Received'
        } else if (data.meal_received_id == 2) {
            // html = data.institution_student_meal.meal_received
            html = 'Not Received'
        } else if (data.meal_received_id == null || data.meal_received_id == 3) {
            html = '<i style="color: #999999;" class="fa fa-minus"></i>'
        }
        return html
    }
}

function getEditMealBenefiteElement(data, mealBenefitTypeOptions, context, api) {
    let dataKey = 'meal_benefit_id';
    let eSelectWrapper = document.createElement('div');
    eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");
    eSelectWrapper.setAttribute("id", dataKey);
    eSelectWrapper.setAttribute("style", "display: block");

    let eSelect = document.createElement("select");
    eSelect.setAttribute('style', 'width: 190px;')
    if (data[dataKey] == null) {
        data[dataKey] = mealBenefitTypeOptions[0].id
    }

    mealBenefitTypeOptions.forEach((obj, index) => {
        let eOption = document.createElement("option");
        let labelText = obj.name;
        eOption.setAttribute("value", obj.id);
        eOption.innerHTML = labelText;
        eSelect.appendChild(eOption);
    })
    eSelect.value = data[dataKey];
    eSelect.addEventListener('change', () => {
        let oldValue = data[dataKey];
        data[dataKey] = eSelect.value;
    })
    eSelectWrapper.appendChild(eSelect);
    return eSelectWrapper;
}

function getEditMealElement(data, mealTypes, api, context) {
    let dataKey = 'meal_received_id';
    let eCell = document.createElement('div');
    eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
    eCell.setAttribute("id", dataKey);
    if (data[dataKey] == null) {
        data[dataKey] = data.default_meal_receive_id ?? 1; //POCOR-9633: use API-supplied default instead of hardcoded Received
        console.log('[POCOR-9633] meal_received_id was null, set to default:', data[dataKey], '(default_meal_receive_id from API:', data.default_meal_receive_id, ')');
    }
    let eSelect = document.createElement("select");
    eSelect.setAttribute('style', 'width: 190px;')
    mealTypes.forEach((obj) => {
        let eOption = document.createElement("option");
        let labelText = obj.name;
        eOption.setAttribute("value", obj.id);
        eOption.setAttribute("selected", data[dataKey]);
        eOption.innerHTML = labelText;
        eSelect.appendChild(eOption);
    })

    eSelect.value = data[dataKey];
    eSelect.addEventListener('change', () => {
        let oldValue = data[dataKey];
        let newValue = eSelect.value;

        let mealTypeObj = mealTypes.find(obj => obj.id == newValue);

        if (newValue != oldValue) {
            let oldParams = {
                meal_received_id: oldValue
            };

            switch (mealTypeObj.name) {
                case 'None':
                    data.meal_received_id = 3;
                    break;

                case 'Not Received':
                    data.meal_received_id = 2;
                    break;

                case 'Received':
                    data.meal_received_id = newValue;
                    break;
            }
            oldValue = newValue;
            // data.institution_student_meal.meal_received_id = newValue;
            let refreshParams = {
                columns: ['meal_benefit_name'],
                force: true
            }

            api.refreshCells(refreshParams);
        }

    })
    eCell.appendChild(eSelect);
    return eCell;
}
