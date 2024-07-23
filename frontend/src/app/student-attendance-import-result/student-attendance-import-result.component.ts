import { Component, OnInit } from '@angular/core';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { MINI_DASHBOARD_CONFIG } from '../student-attendance/student_attendance.config';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { TABLE_COLUMN_LIST } from './student-attendance-import-report.config';
import { ITableApi, ITableConfig, KdAlertEvent } from 'openemis-styleguide-lib';
import { Router } from '@angular/router';
import { timer } from 'rxjs';

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
      tooltip: 'Download',
      callback: (): void => {
        this.generateExcel();
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
  ) {
    this.dataFromRoute = this.router.getCurrentNavigation().extras.state?.['importData'];
  }

  ngOnInit(): void {
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

  generateExcel() {
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

}
