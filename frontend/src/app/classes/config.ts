import { } from "openemis-styleguide-lib";


const colDef = {
  openEmisId: {
    headerName: "OpenEmis ID",
    primaryKey: true
  },
  name: {
    headerName: 'Name',
    sortable: true,
  },
  gender: {
    headerName: 'Gender',
    sortable: true,
  },
  educationGrade: {
    headerName: 'Education Grade',
    sortable: true,
  },
  studentStatus: {
    headerName: 'Student Status',
    sortable: true,
  },
  specialNeeds: {
    headerName: "Special Needs",
    sortable: true,
  }
}
export const MULTITABLE_DATA = {
  masterData: [
    { openEmisId: 123, name: 'Test', gender: 'Female', educationGrade: 'Primary', studentStatus: 'Active', specialNeeds: 'Yes' },
    { openEmisId: 456, name: 'Zest', gender: 'Female', educationGrade: 'Primary', studentStatus: 'Active', specialNeeds: 'Not' },
  ],
  slaveData: [],
  config: {
    colDef: colDef,
    height: 500,
    gridSetting: {
      topPlaceholder: 'Top Placeholder',
      bottomPlaceholder: 'Bottom Placeholder',
      toTopBtn: { icon: 'fa fa-angle-double-up fa-lg', text: 'To Top' },
      toBottomBtn: { icon: 'fa fa-angle-double-down fa-lg', text: 'To Bottom' }
    }
  }
}

export const PAGE_DATA = [
  {
    'key': 'dropdown',
    'label': 'Academic Period',
    'visible': true,
    'required': true,
    'controlType': 'dropdown',
    'options': [
      {
        'key': 1,
        'value': '2024'
      }, {
        'key': 2,
        'value': '2023'
      }
    ]
  },
  {
    'key': 'text',
    'label': 'Class Name',
    'visible': true,
    'required': true,
    'controlType': 'text',
  },
  {
    'key': 'dropdown',
    'label': 'Shift',
    'visible': true,
    'required': true,
    'controlType': 'dropdown',
    'options': [
      {
        'key': 1,
        'value': 'First shift'
      }, {
        'key': 2,
        'value': 'Second shift'
      }
    ]
  },
  {
    'key': 'dropdown',
    'label': 'Unit',
    'visible': true,
    'required': false,
    'controlType': 'dropdown',
    'options': [
      {
        'key': 1,
        'value': 'Unit 1'
      }, {
        'key': 2,
        'value': 'Unit 2'
      }
    ]
  },
  {
    'key': 'dropdown',
    'label': 'Course',
    'visible': true,
    'required': false,
    'controlType': 'dropdown',
    'options': [
      {
        'key': 1,
        'value': 'Course 1'
      }, {
        'key': 2,
        'value': 'Course 2'
      }
    ]
  },
  {
    'key': 'dropdown',
    'label': 'Home Room Teacher',
    'visible': true,
    'required': false,
    'controlType': 'dropdown',
    'options': [
      {
        'key': 1,
        'value': 'Teacher 1'
      }, {
        'key': 2,
        'value': 'Teacher 2'
      }
    ]
  },
  {
    'key': 'forSecondaryTeacher',
    'label': 'Secondary Teachers',
    'visible': true,
    'order': 1,
    'required': false,
    'controlType': 'text',
    'type': 'multiselect',
    'multiselect': true,
    'clickToggleDropdown': true,
    'lengthToSearch': 1,
    'placeholder': 'Select secondary teacher',
    'options': [
      {
        'key': 1,
        'value': 'Teacher 1'
      }, {
        'key': 2,
        'value': 'Teacher 2'
      }, {
        'key': 3,
        'value': 'Teacher 3'
      }
    ]
  },
  {
    'key': 'input',
    'label': 'Capacity',
    'visible': true,
    'required': true,
    'controlType': 'text',
  },
  {
    key: 'multiselect',
    label: 'Add Student',
    visible: true,
    required: false,
    controlType: 'multi-table',
    config: MULTITABLE_DATA.config,
    masterData: MULTITABLE_DATA.masterData,
    slaveData: MULTITABLE_DATA.slaveData
  }
]