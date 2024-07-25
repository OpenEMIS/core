import { Component, OnInit } from '@angular/core';
import { IDynamicFormApi } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-student-attendance-report',
  templateUrl: './student-attendance-report.component.html',
  styleUrls: ['./student-attendance-report.component.css']
})
export class StudentAttendanceReportComponent implements OnInit {
  public api: IDynamicFormApi = {};
  counter: number = 0;
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
      name: 'Import Student Attendances',
      path: ''
    }]
  }

  public pageheader: any = {
    leftBtn: [{
      type: "back",
      callback: () => {
        this.backToData();
      }
    }],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Import Student Attendances",
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
      key: 'class',
      label: 'Class',
      visible: true,
      required: false,
      value: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
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
            'label': 'Download',
            'callback': (): void => {
              event.preventDefault();
              console.log('this is callback for download button');
            }
          }
        ],
        'infoText': [
          {
            'text': 'Format Supported: xls, xlsx, ods, zip'
          },
          {
            'text': 'File size should not be larger than 512KB.'
          },
          {
            'text': 'Recommended Maximum Records: 2000'
          }
        ],
      }
    },

  ];

  academic_class: any[];
  selected_academic_class: any;

  constructor(
    private Rest: ApiService
  ) { }

  ngOnInit(): void {
    this.loginData();
  }

  loginData() {
    this.Rest.setSession();
    let token = localStorage.getItem("loginToken");
    if (!token) {
      let userName = sessionStorage.getItem('username');
      let password = sessionStorage.getItem('password');

      if (userName == null && password == null) {
        setTimeout(() => {
          this.counter = this.counter + 1;
          if (this.counter <= 5) {
            this.loginData();
          } else {
            alert('Please login again')
          }
        }, 1500);
      } else {
        var decodedPassword = atob(password);
        if (userName && decodedPassword) {
          this.loginApi(userName, decodedPassword);
        } else {
          this.removeSession();
        }
      }
    } else {
      this.getAPIData();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.getAPIData();
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  getAPIData() {
    this.Rest.getWithToken(`institutions/6/classes?order=id&academic_period_id=33`).subscribe({
      next: (response: any) => {
        if (response) {
          this.academic_class = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            this.academic_class.push(obj);
          });
          let classData = this._confirmationData;
          classData[0].options = this.academic_class;
          classData[0].value = this.academic_class[0].key;
          this._confirmationData = [...classData];
          console.log(this._confirmationData, "this._confirmationData");
          this.displayLoading = false;
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  _submitEvent(event: any){
    console.log(event,"event--");
  }

  _buttonEvent(event: any){
    console.log(event,"event");
    
  }

  backToData() {

  }

}
