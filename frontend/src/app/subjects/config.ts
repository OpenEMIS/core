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
  class: {
    headerName: 'Class',
    sortable: true,
  },
  gender: {
    headerName: 'Gender',
    sortable: true,
  },
  studentStatus: {
    headerName: 'Student Status',
    sortable: true,
  },
}

export const MULTITABLE_DATA = {
  masterData: [
    { openEmisId: 123, name: 'Test', class: 'Primary 1-A', gender: 'Female', studentStatus: 'Active'},
    { openEmisId: 456, name: 'Zest', class: 'Primary 1-B', gender: 'Male', studentStatus: 'Active'},
    
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
    'key': 'text',
    'label': 'Name',
    'visible': true,
    'required': true,
    'controlType': 'text',
  },
  {
    'key': 'dropdown',
    'label': 'Academic Period',
    'visible': true,
    'required': true,
    'controlType': 'dropdown',
    'options': [
      {
        'key': 1,
        'value': '2023'
      }, {
        'key': 2,
        'value': '2022'
      }
    ]
  },
  {
    'key': 'text',
    'label': 'Subject Name',
    'visible': true,
    'required': true,
    'controlType': 'text',
    'value' : 'Social Studies',
    'readonly' : true,
  },
  {
    'key': 'forClasses',
    'label': 'Classes',
    'visible': true,
    'order': 1,
    'required': true,
    'controlType': 'text',
    'type': 'multiselect',
    'multiselect': true,
    'clickToggleDropdown': true,
    'lengthToSearch': 1,
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
    'key': 'forTeachers',
    'label': 'Teachers',
    'visible': true,
    'order': 1,
    'required': false,
    'controlType': 'text',
    'type': 'multiselect',
    'multiselect': true,
    'clickToggleDropdown': true,
    'lengthToSearch': 1,
    'placeholder': 'Select teacher',
    'options': [
      {
        'key': 1,
        'value': 'Amanda'
      }, {
        'key': 2,
        'value': 'Roger'
      }
    ]
  },
  {
    'key': 'forRooms',
    'label': 'Rooms',
    'visible': true,
    'order': 1,
    'required': false,
    'controlType': 'text',
    'type': 'multiselect',
    'multiselect': true,
    'clickToggleDropdown': true,
    'lengthToSearch': 1,
    'placeholder': 'Select room',
    'options': [
      {
        'key': 1,
        'value': 'Room 1'
      }, {
        'key': 2,
        'value': 'Room 2'
      }
    ]
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