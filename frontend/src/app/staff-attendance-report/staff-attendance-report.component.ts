import { Component, OnInit } from '@angular/core';
import { IDynamicFormApi } from 'openemis-styleguide-lib';

@Component({
  selector: 'app-staff-attendance-report',
  templateUrl: './staff-attendance-report.component.html',
  styleUrls: ['./staff-attendance-report.component.css']
})
export class StaffAttendanceReportComponent implements OnInit {
  public api: IDynamicFormApi = {};
  displayLoading: boolean = false;

  public breadcrumbList: any = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Institutions',
      path: ''
    },
    {
      name: 'Avory Primary School',
      path: ''
    },
    {
      name: 'Import Staff Attendances',
      path: ''
    }]
  }

  public pageheader: any = {
    leftBtn: [{
      type: 'back',
      callback: () => {
        this.backToData();
      }
    }],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Import Staff Attendances",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public _formButtons: Array<any> = [
    {
      type: 'submit',
      name: 'Import',
      icon: 'kd-import',
      class: 'btn-text'
    },
    {
      type: 'cancel',
      name: 'Cancel',
      icon: 'kd-close',
      class: 'btn-outline'
    }
  ];

  public _confirmationData: Array<any> = [
    {
      'key': 'select_file_to_import',
      'label': 'Select File To Import',
      'visible': true,
      'required': true,
      'controlType': 'file-input',
      'type': 'file',
      'config': {
        'leftToolbar': true,
        'leftButton': [
          {
            'icon': 'kd-download',
            'label': 'Download Template',
            'callback': (): void => {
              event.preventDefault();
              console.log('this is callback for download button');
            }
          }
        ],
        'infoText': [
          { 'text': 'Format Supported: xls, xlsx, ods, zip' },
          { 'text': 'File size should not be larger than 512KB.' },
          { 'text': 'Recommended Maximum Records: 2000' }
        ],
      }
    }
  ];


  constructor() { }

  ngOnInit(): void {
  }

  backToData() {

  }

  _submitEvent(event: any){
    console.log(event,"event");
  }

  _buttonEvent(event: any){
    console.log(event,"event");
  }

}
