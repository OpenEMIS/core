import { Component, OnInit } from '@angular/core';
import { IDynamicFormApi, KdAlertEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { Router } from '@angular/router';

@Component({
  selector: 'app-student-attendance-report',
  templateUrl: './student-attendance-report.component.html',
  styleUrls: ['./student-attendance-report.component.css']
})
export class StudentAttendanceReportComponent implements OnInit {
  public api: IDynamicFormApi = {};
  counter: number = 0;
  displayLoading: boolean = false;
  themeArray = DEFAULT_TEMPLATE_THEME;

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
      name: '',
      class: 'd-none'
    },
    {
      name: '',
      class: 'd-none'
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
    // {
    //   'key': 'select_file_to_import',
    //   'label': 'Select File To Import',
    //   'visible': true,
    //   'required': true,
    //   'controlType': 'file-input',
    //   'type': 'file',
    //   'config': {
    //     'leftToolbar': true,
    //     'leftButton': [
    //       {
    //         'icon': 'kd-download',
    //         'label': 'Download',
    //         'callback': (): void => {
    //           event.preventDefault();
    //           console.log('this is callback for download button');
    //         }
    //       }
    //     ],
    //     'infoText': [
    //       {
    //         'text': 'Format Supported: xls, xlsx, ods, zip'
    //       },
    //       {
    //         'text': 'File size should not be larger than 512KB.'
    //       },
    //       {
    //         'text': 'Recommended Maximum Records: 2000'
    //       }
    //     ],
    //   }
    // },

  ];

  academic_class: any[];
  selected_academic_class: any;
  institution_id: any;
  academic_Period: number;
  selectedClassId: any;
  student_attendance_data: any;

  constructor(
    private Rest: ApiService,
    private _kdAlertEvent: KdAlertEvent,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
    // this.institution_id = 6;
    this.academic_Period = JSON.parse(localStorage.getItem("academic_Period"));
    this.academic_Period = 33;
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
      this.setTheme();
      this.getAPIData();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
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

  setTheme() {
    this.Rest.getWithToken('themes').subscribe({
      next: (response: any) => {
        let selectedThemeData = '';
        if (response?.data[3].value) {
          selectedThemeData = response?.data[3].value;
          selectedThemeData = `#${selectedThemeData}`;
        } else {
          selectedThemeData = response?.data[3].default_value;
          selectedThemeData = `#${selectedThemeData}`;
        }
        this.themeArray.btnGroup[0].dropdownContent.forEach((element: any) => {
          if (element.text == selectedThemeData) {
            document.body.className = element.theme + ' fuelux';
          }
        });
      },
      error: (error: any) => {

      }
    })
  }

  getAPIData() {
    this.Rest.getWithToken(`institutions/${this.institution_id}/classes?order=id&academic_period_id=${this.academic_Period}`).subscribe({
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
          this.academic_class.unshift({ key: '', value: '--Select--' })
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

  detectValue(event: any) {
    if (event.controlType == "dropdown" && event.value != '') {
      this.selectedClassId = event.value;
      let confirmation = this._confirmationData;
      confirmation[0].value = event.value;
      confirmation[1] = {
        'key': 'select_file_to_import',
        'label': 'Select File To Import',
        'visible': true,
        'required': false,
        'controlType': 'file-input',
        'type': 'file',
        'config': {
          'leftToolbar': true,
          'leftButton': [
            {
              'icon': 'kd-download',
              'label': 'Download',
              'callback': (): void => {
                // event.preventDefault();
                this.exportToExcel();
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
      };
      this._confirmationData = [...confirmation];

      this._formButtons = [
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
      ]

    } else if (event.controlType == "dropdown" && event.value == '') {
      let confirmation = this._confirmationData;
      confirmation[0].value = event.value;
      confirmation.pop();
      this._confirmationData = [...confirmation];
      this._formButtons = [
        {
          name: '',
          class: 'd-none'
        },
        {
          name: '',
          class: 'd-none'
        }
      ]
    }
  }

  exportToExcel() {

    this.Rest.getItemExport(`institutions/students/attendances/import/template?institution_id=${this.institution_id}&institution_class_id=${this.selectedClassId}`).subscribe({
      next: (response: any) => {
        let url = window.URL.createObjectURL(response);
        let a = document.createElement('a');
        document.body.appendChild(a);
        a.setAttribute('style', 'display: none');
        a.href = url;
        a.download = response.filename || 'Student attendance';
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

      },
      error: (error: any) => {
      
      }
    })
  }

  _submitEvent(event: any) {
    console.log(event, "event--");
    if (event.class && event.select_file_to_import) {
      const formData = new FormData();
      formData.append('file', event.select_file_to_import);
      formData.append('institution_class_id', event.class);
      formData.append('institution_id', this.institution_id);
      this.Rest.postImportTemplete('institutions/students/attendances/import', formData).subscribe({
        next: (response: any) => {
          console.log(response, "response");
          let toasterConfig: any = {
            title: 'Student attendance imported successfully!!',
            showCloseButton: true,
            tapToDismiss: true,
          };
          // localStorage.setItem("meal_imported",JSON.stringify(response.data));
          this._kdAlertEvent.info(toasterConfig);
          console.log(response.data, "response.data");
          let attendance_data = response.data;
          this.router.navigateByUrl('Institution/Institutions/ImportStudentAttendance/results', {
            state: {
              importData: { attendance_data_imported: attendance_data, selectedClassId: this.selectedClassId },
            },
          });
        },
        error: (error) => {
          console.log(error, "error");
          let toasterConfig: any = {
            title: 'Something went wrong, Please try again',
            showCloseButton: true,
            tapToDismiss: true,
          };

          this._kdAlertEvent.error(toasterConfig);
        }
      })
    } else {
      let toasterConfig: any = {
        title: 'Please fill all fields',
        showCloseButton: true,
        tapToDismiss: true,
      };

      this._kdAlertEvent.warn(toasterConfig);
    }
  }

  _buttonEvent(event: any) {
    console.log(event, "event");

  }

  backToData() {
    let tokenData = localStorage.getItem('encoded_url');
    if(tokenData){
      this.router.navigateByUrl(`Institution/Institutions/StudentAttendances/index/${tokenData}`);
    }
  }

}
