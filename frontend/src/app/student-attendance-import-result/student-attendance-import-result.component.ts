import { Component, OnInit } from '@angular/core';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { MINI_DASHBOARD_CONFIG } from '../student-attendance/student_attendance.config';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { TABLE_COLUMN_LIST } from './student-attendance-import-report.config';
import { ITableApi, ITableConfig, KdAlertEvent } from 'openemis-styleguide-lib';
import { Router } from '@angular/router';
import { timer } from 'rxjs';
import { ExcelService } from '../shared/excel.service';

@Component({
  selector: 'app-student-attendance-import-result',
  templateUrl: './student-attendance-import-result.component.html',
  styleUrls: ['./student-attendance-import-result.component.css']
})
export class StudentAttendanceImportResultComponent implements OnInit {
  displayLoading: boolean = false;
  themeArray = DEFAULT_TEMPLATE_THEME;
  displayMiniDashboard: boolean = false;
  public miniDashboardConfig: IMiniDashboardConfig = MINI_DASHBOARD_CONFIG;
  public miniDashboardData: Array<IMiniDashboardItem>;
  public _column: any;

  public pageheader: any = {
    leftBtn: [{
      type: "back",
      callback: () => {
        this.backToData();
      }
    },
    {
      custom: true,
      icon: 'fa fa-download',
      tooltip: 'Failed download',
      callback: (): void => {
        this.generateFailExcel();
      }
    },
    {
      custom: true,
      icon: 'fa fa-download',
      tooltip: 'Success download',
      callback: (): void => {
        this.generateSuccessExcel();
      }
    },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  institution_name: string;
  counter: any = 0;
  public _config: ITableConfig = {
    id: 'listTable',
    rowIdKey: "id",
    gridHeight: "auto",
    rowContentHeight: 25,
    loadType: "infinite",
    externalFilter: false,
    paginationConfig: {
      pagesize: 10000,
      total: 1000,
    },
  };

  public _tableApi: ITableApi = {};
  public _row: Array<any>;
  dataFromRoute: any;
  attendance_import_result: any;
  institution_id: any;
  selectedClassId: any;

  constructor(
    private Rest: ApiService,
    public router: Router,
    private _kdAlertEvent: KdAlertEvent,
    private excelSvc: ExcelService,
  ) {
    this.dataFromRoute = this.router.getCurrentNavigation().extras.state?.['importData'];
  }

  ngOnInit(): void {
    console.log(this.dataFromRoute, "this.dataFromRoute");

    if (!this.dataFromRoute) {
      let toasterConfig: any = {
        title: 'Please fill all fields',
        showCloseButton: true,
        tapToDismiss: true,
      };

      this._kdAlertEvent.warn(toasterConfig);
      let tokenData = localStorage.getItem('encoded_url');
      if (tokenData) {
        this.router.navigateByUrl(`Institution/Institutions/${tokenData}/ImportStudentAttendances/add`);
      }
      // this.router.navigateByUrl('Institution/Institutions/ImportStudentAttendances/add');
    } else {
      this.attendance_import_result = this.dataFromRoute.attendance_data_imported;
      this.selectedClassId = this.dataFromRoute.selectedClassId;
      this.institution_name = localStorage.getItem("institutionName");
      this.pageheader.pageheaderText = `${this.institution_name} - Import Student Attendances`;
      this.institution_id = localStorage.getItem("institution_id");
      // this.institution_id = 6;
      this.loginData();
    }
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
      this.setMiniDashboard();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.setMiniDashboard();
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

  setMiniDashboard() {
    this.miniDashboardData = [
      {
        type: 'text',
        label: 'Total Rows:',
        value: this.attendance_import_result?.total_count
      },
      {
        type: 'text',
        label: 'Rows Imported:',
        value: this.attendance_import_result?.records_added?.count
      },
      {
        type: 'text',
        label: 'Rows Updated:',
        value: this.attendance_import_result?.records_updated?.count
      },
      {
        type: 'text',
        label: 'Rows Failed:',
        value: this.attendance_import_result?.records_failed?.count
      },

    ];
    this.displayMiniDashboard = true;

    timer(100).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.rowNumber,
        TABLE_COLUMN_LIST.date,
        TABLE_COLUMN_LIST.student_attendance_type_code,
        TABLE_COLUMN_LIST.period,
        TABLE_COLUMN_LIST.institution_subject_name,
        TABLE_COLUMN_LIST.openEMIS_id,
        TABLE_COLUMN_LIST.absence_type_code,
        TABLE_COLUMN_LIST.student_absence_reason_code,
        TABLE_COLUMN_LIST.comment
      ];

      let row = [];
      this.attendance_import_result?.records_failed?.rows.forEach((element: any) => {
        let obj = {
          row_number: element?.row_number,
          date: !element?.errors['Date ( DD/MM/YYYY )'] ? element?.data['Date ( DD/MM/YYYY )'] : `${element?.data['Date ( DD/MM/YYYY )']}` == null ? `${element?.data['Date ( DD/MM/YYYY )']} (${element?.errors['Date ( DD/MM/YYYY )']})` : `(${element?.errors['Date ( DD/MM/YYYY )']})`,
          openemis_id: !element?.errors['OpenEMIS ID'] ? element?.data['OpenEMIS ID'] : `${element?.data['OpenEMIS ID']}` == null ? `${element?.data['OpenEMIS ID']} (${element?.errors['OpenEMIS ID']})` : `(${element?.errors['OpenEMIS ID']})`,
          absence_type_code: !element?.errors['Absence Type Code'] ? element?.data['Absence Type Code'] : `${element?.data['Absence Type Code']}` == null ? `${element?.data['Absence Type Code']} (${element?.errors['Absence Type Code']})` : `(${element?.errors['Absence Type Code']})`,
          institution_subject_name: !element?.errors['Institution Subject Name'] ? element?.data['Institution Subject Name'] : `${element?.data['Institution Subject Name']}` == null ? `${element?.data['Institution Subject Name']} (${element?.errors['Institution Subject Name']})` : `(${element?.errors['Institution Subject Name']})`,
          period: !element?.errors['Period'] ? element?.data['Period'] : `${element?.data['Period']}` == null ? `${element?.data['Period']} (${element?.errors['Period']})` : `(${element?.errors['Period']})`,
          student_absence_reason_code: !element?.errors['Student Absence Reason Code'] ? element?.data['Student Absence Reason Code'] : `${element?.data['Student Absence Reason Code']}` == null ? `${element?.data['Student Absence Reason Code']} (${element?.errors['Student Absence Reason Code']})` : `(${element?.errors['Student Absence Reason Code']})`,
          student_attendance_type_code: !element?.errors['Student Attendance Type Code'] ? element?.data['Student Attendance Type Code'] : `${element?.data['Student Attendance Type Code']}` == null ? `${element?.data['Student Attendance Type Code']} (${element?.errors['Student Attendance Type Code']})` : `(${element?.errors['Student Attendance Type Code']})`,
          comment: !element?.errors['Comment'] ? element?.data['Comment'] : `${element?.data['Comment']}` == null ? `${element?.data['Comment']} (${element?.errors['Comment']})` : `(${element?.errors['Comment']})`
        }
        row.push(obj);
      });
      this.attendance_import_result?.records_updated?.rows.forEach((element: any) => {
        let obj = {
          row_number: element?.row_number,
          date: !element?.errors['Date ( DD/MM/YYYY )'] ? element?.data['Date ( DD/MM/YYYY )'] : `${element?.data['Date ( DD/MM/YYYY )']}` == null ? `${element?.data['Date ( DD/MM/YYYY )']} (${element?.errors['Date ( DD/MM/YYYY )']})` : `(${element?.errors['Date ( DD/MM/YYYY )']})`,
          openemis_id: !element?.errors['OpenEMIS ID'] ? element?.data['OpenEMIS ID'] : `${element?.data['OpenEMIS ID']}` == null ? `${element?.data['OpenEMIS ID']} (${element?.errors['OpenEMIS ID']})` : `(${element?.errors['OpenEMIS ID']})`,
          absence_type_code: !element?.errors['Absence Type Code'] ? element?.data['Absence Type Code'] : `${element?.data['Absence Type Code']}` == null ? `${element?.data['Absence Type Code']} (${element?.errors['Absence Type Code']})` : `(${element?.errors['Absence Type Code']})`,
          institution_subject_name: !element?.errors['Institution Subject Name'] ? element?.data['Institution Subject Name'] : `${element?.data['Institution Subject Name']}` == null ? `${element?.data['Institution Subject Name']} (${element?.errors['Institution Subject Name']})` : `(${element?.errors['Institution Subject Name']})`,
          period: !element?.errors['Period'] ? element?.data['Period'] : `${element?.data['Period']}` == null ? `${element?.data['Period']} (${element?.errors['Period']})` : `(${element?.errors['Period']})`,
          student_absence_reason_code: !element?.errors['Student Absence Reason Code'] ? element?.data['Student Absence Reason Code'] : `${element?.data['Student Absence Reason Code']}` == null ? `${element?.data['Student Absence Reason Code']} (${element?.errors['Student Absence Reason Code']})` : `(${element?.errors['Student Absence Reason Code']})`,
          student_attendance_type_code: !element?.errors['Student Attendance Type Code'] ? element?.data['Student Attendance Type Code'] : `${element?.data['Student Attendance Type Code']}` == null ? `${element?.data['Student Attendance Type Code']} (${element?.errors['Student Attendance Type Code']})` : `(${element?.errors['Student Attendance Type Code']})`,
          comment: !element?.errors['Comment'] ? element?.data['Comment'] : `${element?.data['Comment']}` == null ? `${element?.data['Comment']} (${element?.errors['Comment']})` : `(${element?.errors['Comment']})`
        }
        row.push(obj);
      });
      this.attendance_import_result?.records_added?.rows.forEach((element: any) => {
        let obj = {
          row_number: element?.row_number,
          date: !element?.errors['Date ( DD/MM/YYYY )'] ? element?.data['Date ( DD/MM/YYYY )'] : `${element?.data['Date ( DD/MM/YYYY )']}` == null ? `${element?.data['Date ( DD/MM/YYYY )']} (${element?.errors['Date ( DD/MM/YYYY )']})` : `(${element?.errors['Date ( DD/MM/YYYY )']})`,
          openemis_id: !element?.errors['OpenEMIS ID'] ? element?.data['OpenEMIS ID'] : `${element?.data['OpenEMIS ID']}` == null ? `${element?.data['OpenEMIS ID']} (${element?.errors['OpenEMIS ID']})` : `(${element?.errors['OpenEMIS ID']})`,
          absence_type_code: !element?.errors['Absence Type Code'] ? element?.data['Absence Type Code'] : `${element?.data['Absence Type Code']}` == null ? `${element?.data['Absence Type Code']} (${element?.errors['Absence Type Code']})` : `(${element?.errors['Absence Type Code']})`,
          institution_subject_name: !element?.errors['Institution Subject Name'] ? element?.data['Institution Subject Name'] : `${element?.data['Institution Subject Name']}` == null ? `${element?.data['Institution Subject Name']} (${element?.errors['Institution Subject Name']})` : `(${element?.errors['Institution Subject Name']})`,
          period: !element?.errors['Period'] ? element?.data['Period'] : `${element?.data['Period']}` == null ? `${element?.data['Period']} (${element?.errors['Period']})` : `(${element?.errors['Period']})`,
          student_absence_reason_code: !element?.errors['Student Absence Reason Code'] ? element?.data['Student Absence Reason Code'] : `${element?.data['Student Absence Reason Code']}` == null ? `${element?.data['Student Absence Reason Code']} (${element?.errors['Student Absence Reason Code']})` : `(${element?.errors['Student Absence Reason Code']})`,
          student_attendance_type_code: !element?.errors['Student Attendance Type Code'] ? element?.data['Student Attendance Type Code'] : `${element?.data['Student Attendance Type Code']}` == null ? `${element?.data['Student Attendance Type Code']} (${element?.errors['Student Attendance Type Code']})` : `(${element?.errors['Student Attendance Type Code']})`,
          comment: !element?.errors['Comment'] ? element?.data['Comment'] : `${element?.data['Comment']}` == null ? `${element?.data['Comment']} (${element?.errors['Comment']})` : `(${element?.errors['Comment']})`
        }
        row.push(obj);
      });
      this._row = row;
    })
  }

  backToData() {
    let tokenData = localStorage.getItem('encoded_url');
    if (tokenData) {
      this.router.navigateByUrl(`Institution/Institutions/${tokenData}/ImportStudentAttendances/add`);
    }
  }

  generateFailExcel() {
    let dataValidationHeadings = {};

    let dataColumnHeadings = [
      "Date ( DD/MM/YYYY )",
      "OpenEMIS ID",
      "Absence Type Code",
      "Institution Subject Name",
      "Period",
      "Student Absence Reason Code",
      "Student Attendance Type Code",
      "Errors"
    ];
    let data = {
      header: [],
      References: {}
    }

    if (this.attendance_import_result.records_failed.count > 0) {
      for (var key in this.attendance_import_result.records_failed.rows[0].data) {
        data.header.push(key);
        data.References[key] = {
          data: [],
          header: []
        }
      }
      data.References["Errors"] = {
        data: [],
        header: []
      }
      this.attendance_import_result.records_failed.rows.forEach((element: any, index: any) => {
        if (element.data['Date ( DD/MM/YYYY )']) {
          let obj = {
            Name: element.data['Date ( DD/MM/YYYY )']
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        }

        if (element.data['Absence Type Code']) {
          let obj = {
            Name: element.data['Absence Type Code']
          }
          data.References['Absence Type Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Absence Type Code'].data.push(obj);
        }

        if (element.data['Institution Subject Name']) {
          let obj = {
            Name: element.data['Institution Subject Name']
          }
          data.References['Institution Subject Name'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Institution Subject Name'].data.push(obj);
        }

        if (element.data['Period']) {
          let obj = {
            Name: element.data['Period']
          }
          data.References['Period'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Period'].data.push(obj);
        }

        if (element.data['Student Absence Reason Code']) {
          let obj = {
            Name: element.data['Student Absence Reason Code']
          }
          data.References['Student Absence Reason Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Student Absence Reason Code'].data.push(obj);
        }

        if (element.data['Student Attendance Type Code']) {
          let obj = {
            Name: element.data['Student Attendance Type Code']
          }
          data.References['Student Attendance Type Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Student Attendance Type Code'].data.push(obj);
        }

        //For error code
        let dateObj: any = {};
        let programObj: any = {};
        let receivedObj: any = {};
        let periodObj: any = {};
        let typeCodeObj: any = {};
        if (element.errors['Date ( DD/MM/YYYY )']) {
          dateObj = {
            error: element.errors['Date ( DD/MM/YYYY )']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Absence Type Code']) {
          programObj = {
            error: element.errors['Absence Type Code']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Institution Subject Name']) {
          receivedObj = {
            error: element.errors['Institution Subject Name']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Period']) {
          periodObj = {
            error: element.errors['Period']
          }
        }
        if (element.errors['Student Attendance Type Code']) {
          typeCodeObj = {
            error: element.errors['Student Attendance Type Code']
          }
        }
        if(dateObj?.error || programObj?.error || receivedObj?.error || periodObj?.error || typeCodeObj?.error ){
          let dateData = dateObj?.error != undefined ? dateObj?.error : '';
          let programData = programObj?.error != undefined ? programObj?.error : '';
          let receivedData = receivedObj?.error != undefined ? receivedObj?.error : '';
          let periodData = periodObj?.error != undefined ? periodObj?.error : '';
          let typeCodeData = typeCodeObj?.error != undefined ? typeCodeObj?.error : '';

          let str = `${dateData} ${programData} ${receivedData} ${periodData} ${typeCodeData}`;
          data.References['Errors'].data.push(str)
        }
      });
    }

    let referenceNames = Object.keys(data.References);
    let temp2 = {}; let temp3 = {}; let temp4 = {}; let temp5 = {}; let temp6 = {}; let temp7 = {};
    let assetsArr = data.References["Absence Type Code"].data;
    let statusArr = data.References["Date ( DD/MM/YYYY )"].data;
    let levelArr = data.References["Institution Subject Name"].data;
    let parentArr = data.References["Period"].data;
    let receivedArr = data.References["Student Attendance Type Code"].data;
    let reasonArr = data.References["Student Absence Reason Code"].data;

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
      temp2[assetsArr[x].Name] = assetsArr[x].Name;
    }
    for (let x in statusArr) {
      temp3[statusArr[x].Name] = statusArr[x].Name;
    }
    for (let x in levelArr) {
      temp4[levelArr[x].Name] = levelArr[x].Name;
    }
    for (let x in parentArr) {
      temp5[parentArr[x].Name] = parentArr[x].Name;
    }
    for (let x in receivedArr) {
      temp5[receivedArr[x].Name] = receivedArr[x].Name;
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
    let referenceData = data.References;    
    this.excelSvc.init('OpenEMIS_Core_Failed_Students_Attendance_Data_Template', 'Failed Student Attendance Data', dataColumnHeadings, referenceNames, data.References, dataValidationHeadings);
  }

  generateSuccessExcel() {
    let dataValidationHeadings = {};
    let updatedMealRecord: any = [];
    updatedMealRecord.push(...this.attendance_import_result.records_added.rows, ...this.attendance_import_result.records_updated.rows);
    
    // updatedMealRecord.push(this.meal_report.records_updated.rows);
    let dataColumnHeadings = [
      "Date ( DD/MM/YYYY )",
      "Absence Type Code",
      "Institution Subject Name",
      "Period",
      "Student Absence Reason Code",
      "Student Attendance Type Code"
    ];
    let data = {
      header: [],
      References: {}
    }

    if (updatedMealRecord.length > 0) {
      for (var key in updatedMealRecord[0].data) {
        data.header.push(key);
        data.References[key] = {
          data: [],
          header: []
        }
      }
      // data.References["Errors"] = {
      //   data: [],
      //   header: []
      // }
      updatedMealRecord.forEach((element: any, index: any) => {
        if (element.data['Date ( DD/MM/YYYY )']) {
          let obj = {
            Name: element.data['Date ( DD/MM/YYYY )']
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        }

        if (element.data['Student Attendance Type Code']) {
          let obj = {
            Name: element.data['Student Attendance Type Code']
          }
          data.References['Student Attendance Type Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Student Attendance Type Code'].data.push(obj);
        }

        if (element.data['Period']) {
          let obj = {
            Name: element.data['Period']
          }
          data.References['Period'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Period'].data.push(obj);
        }

        if (element.data['Institution Subject Name']) {
          let obj = {
            Name: element.data['Institution Subject Name']
          }
          data.References['Institution Subject Name'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Institution Subject Name'].data.push(obj);
        }

        if (element.data['Absence Type Code']) {
          let obj = {
            Name: element.data['Absence Type Code']
          }
          data.References['Absence Type Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Absence Type Code'].data.push(obj);
        }

        if (element.data['Student Absence Reason Code']) {
          let obj = {
            Name: element.data['Student Absence Reason Code']
          }
          data.References['Student Absence Reason Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Student Absence Reason Code'].data.push(obj);
        }

        //For error code
        let dateObj: any = {};
        let programObj: any = {};
        let receivedObj: any = {};
        if (element.errors['Date ( DD/MM/YYYY )']) {
          dateObj = {
            error: element.errors['Date ( DD/MM/YYYY )']
          }
        }
        if (element.errors['Absence Type Code']) {
          programObj = {
            error: element.errors['Absence Type Code']
          }
        }
        if (element.errors['Institution Subject Name']) {
          receivedObj = {
            error: element.errors['Institution Subject Name']
          }
          // data.References['Errors'].data.push(obj);
        }
        if(dateObj?.error || programObj?.error || receivedObj?.error){
          let dateData = dateObj?.error != undefined ? dateObj?.error : '';
          let programData = programObj?.error != undefined ? programObj?.error : '';
          let receivedData = receivedObj?.error != undefined ? receivedObj?.error : '';

          let str = `${dateData} ${programData} ${receivedData}`;
          data.References['Errors'].data.push(str)
        }
      });
    }

    let referenceNames = Object.keys(data.References);

    let temp2 = {}; let temp3 = {}; let temp4 = {}; let temp5 = {}; let temp6 = {}; let temp7 = {};
    let assetsArr = data.References["Absence Type Code"]?.data;
    let statusArr = data.References["Date ( DD/MM/YYYY )"]?.data;
    let levelArr = data.References["Institution Subject Name"]?.data;
    let parentArr = data.References["Period"]?.data;
    let receivedArr = data.References["Student Absence Reason Code"]?.data;
    let typeCodeArr = data.References["Student Attendance Type Code"]?.data;
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
      temp2[assetsArr[x].Name] = assetsArr[x].Name;
    }
    for (let x in statusArr) {
      temp3[statusArr[x].Name] = statusArr[x].Name;
    }
    for (let x in levelArr) {
      temp4[levelArr[x].Name] = levelArr[x].Name;
    }
    for (let x in parentArr) {
      temp5[parentArr[x].Name] = parentArr[x].Name;
    }
    for (let x in receivedArr) {
      temp5[receivedArr[x].Name] = receivedArr[x].Name;
    }
    for (let x in typeCodeArr) {
      temp5[typeCodeArr[x].Name] = typeCodeArr[x].Name;
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
    let referenceData = data.References;
    this.excelSvc.init('OpenEMIS_Core_Success_Students_Attendance_Data_Template', 'Success Student Attendance Data', dataColumnHeadings, dataColumnHeadings, data.References, dataValidationHeadings);
  }

}
