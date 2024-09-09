import { Component, OnInit } from '@angular/core';
import { IDynamicFormApi, KdAlertEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { ExcelService } from '../shared/excel.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-student-meal-import',
  templateUrl: './student-meal-import.component.html',
  styleUrls: ['./student-meal-import.component.css']
})
export class StudentMealImportComponent implements OnInit {
  public api: IDynamicFormApi = {};
  counter: number = 0;
  displayLoading: boolean = false;

  public pageheader: any = {
    leftBtn: [{
      type: "back",
      callback: () => {
        this.backToData();
      }
    }],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public _confirmationData: Array<any> = [
    {
      key: 'class',
      label: 'Class',
      visible: true,
      required: false,
      value: '',
      readonly: false,
      controlType: 'dropdown',
      options: [
        { key: '', value: '--Select--' }
      ],
      events: true,
    }
  ];

  // public _formButtons: Array<any> = [
  //   {
  //     type: 'submit',
  //     name: 'Import',
  //     icon: 'kd-import',
  //     class: 'btn-text'
  //   },
  //   {
  //     type: 'cancel',
  //     name: 'Cancel',
  //     icon: 'kd-close',
  //     class: 'btn-outline'
  //   }
  // ];

  public _formButtons: Array<any> = [
    {
      name: '',
      class: 'd-none'
    },
    {
      name: '',
      class: 'd-none'
    }
  ]
  themeArray = DEFAULT_TEMPLATE_THEME;
  academic_class: any[];
  selected_academic_class: any;
  institution_id: any;
  academic_Period: any;
  selectedClassId: any;
  meal_import_data: any;
  institution_name: string;

  constructor(
    private Rest: ApiService,
    private excelSvc: ExcelService,
    private _kdAlertEvent: KdAlertEvent,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
    // this.institution_id = 6;
    this.academic_Period = JSON.parse(localStorage.getItem("academic_Period"));
    // this.academic_Period = 33;
    this.institution_name = localStorage.getItem("institutionName");
    this.pageheader.pageheaderText = `${this.institution_name} - Import Student Attendances`
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

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  setTheme() {
    this.Rest.getWithToken('themes').subscribe({
      next: (response: any) => {
        console.log(response?.data[3].default_value, "response");
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

  exportToExcel() {
    // var uri = '/assets/excelformat/OpenEMIS_Exams_Import_Exam_Centre_Template.xlsx';
    // var link = document.createElement("a");
    // link.href = uri;
    // link.download = 'OpenEMIS_Exams_Import_Exam_Centre_Template.xlsx';
    // document.body.appendChild(link);
    // link.click();
    // document.body.removeChild(link);

    let dataValidationHeadings = {
      "OpenEMIS ID": "OpenEMIS ID",
      "Meal Programme Code": "Meal Programmes",
      "Meal Received Code": "Meal Received",
      "Meal Benefit Name": "Meal Benefit"
    };

    let dataColumnHeadings = this.meal_import_data.data.Data.header;
    let referenceNames = Object.keys(this.meal_import_data.data.References)
    let temp2 = {}; let temp3 = {}; let temp4 = {}; let temp5 = {}; let temp6 = {}; let temp7 = {};
    let assetsArr = this.meal_import_data.data.References["Meal Benefit"].data;
    let statusArr = this.meal_import_data.data.References["Meal Programmes"].data;
    let levelArr = this.meal_import_data.data.References["Meal Received"].data;
    let parentArr = this.meal_import_data.data.References["OpenEMIS ID"].data;
    let responseArr = [
      {
        key: 1,
        value: 'Multiple Choice'
      },
      {
        key: 2,
        value: 'Open Ended'
      },
      {
        key: 3,
        value: 'Short Answer'
      }
    ];
    for (let x in assetsArr) {
      temp2[assetsArr[x].Name] = assetsArr[x].Id;
    }
    for (let x in statusArr) {
      temp3[statusArr[x].Name] = statusArr[x]["Code"];
    }
    for (let x in levelArr) {
      temp4[levelArr[x].Name] = levelArr[x]["Code"];
    }
    for (let x in parentArr) {
      temp5[parentArr[x].Name] = parentArr[x]["OpenEMIS ID"];
    }
    for (let x in responseArr) {
      temp6[responseArr[x].value] = responseArr[x].key;
    }
    if (!Object.keys(temp2).length) {
      temp2 = { '': '' }
    }
    if (!Object.keys(temp3).length) {
      temp3 = { '': '' }
    }
    if (!Object.keys(temp4).length) {
      temp4 = { '': '' }
    }
    if (!Object.keys(temp5).length) {
      temp5 = { '': '' }
    }
    this.meal_import_data.data.References["Meal Benefit"].data = temp2;
    this.meal_import_data.data.References["Meal Received"].data = temp4;
    this.meal_import_data.data.References["Meal Programmes"].data = temp3;
    this.meal_import_data.data.References["OpenEMIS ID"].data = temp5;
    let referenceData = this.meal_import_data.data.References;
    console.log(referenceData, "referenceData");

    this.excelSvc.init('OpenEMIS_Core_Import_Institution_Meal_Students_Template', 'Import Student Meals Data', dataColumnHeadings, referenceNames, referenceData, dataValidationHeadings);
  }

  public generateImportTemplate() {

    this.Rest.getItemExport(`institutions/students/meals/import/template?institution_id=${this.institution_id}&institution_class_id=${this.selectedClassId}`).subscribe((res: any) => {
      console.log(res, "res");
      // this.meal_import_data = res;
      let url = window.URL.createObjectURL(res);
      let a = document.createElement('a');
      document.body.appendChild(a);
      a.setAttribute('style', 'display: none');
      a.href = url;
      a.download = res.filename || 'Student meal';
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
    },
      (error: any) => {

      });
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
          this.selected_academic_class = this.academic_class[0]?.key;
          let classData = this._confirmationData;
          classData[0].options = this.academic_class;
          this._confirmationData = [...classData];
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

  detectValue(event: any) {
    console.log(event, "event");

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
                // this.exportToExcel();
                this.generateImportTemplate();
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
      let formButton = this._formButtons;
      formButton = [
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
      this._formButtons = [...formButton]
      console.log(this._formButtons, "_formButtons");

      console.log(this._confirmationData, "_confirmationData");
      // this._confirmationData[this._confirmationData.length - 1]['config']['leftButton'][0].callback = this.generateImportTemplate.bind(this);
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

  _submitEvent(event: any) {
    console.log(event, "event--");
    if (event.class && event.select_file_to_import) {
      const formData = new FormData();
      formData.append('file', event.select_file_to_import);
      formData.append('institution_class_id', event.class);
      formData.append('institution_id', this.institution_id);
      formData.append('academic_period_id', this.academic_Period);
      this.Rest.postImportTemplete('institutions/students/meals/import', formData).subscribe({
        next: (response: any) => {
          console.log(response, "response");
          let toasterConfig: any = {
            title: 'Student meal imported successfully!!',
            showCloseButton: true,
            tapToDismiss: true,
          };
          // localStorage.setItem("meal_imported",JSON.stringify(response.data));
          this._kdAlertEvent.info(toasterConfig);
          console.log(response.data, "response.data");
          let meal_data = response.data;
          this.router.navigateByUrl('Institution/Institutions/ImportStudentMeals/results', {
            state: {
              importData: { meal_imported: meal_data, meal_import_result: this.meal_import_data },
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
    let tokenData = localStorage.getItem('encoded_url');
    if (tokenData) {
      this.router.navigateByUrl(`Institution/Institutions/StudentMeals/index/${tokenData}`);
    }
  }

  backToData() {
    let tokenData = localStorage.getItem('encoded_url');
    if (tokenData) {
      this.router.navigateByUrl(`Institution/Institutions/StudentMeals/index/${tokenData}`);
    }
  }

}
