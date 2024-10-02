import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ITableApi, ITableColumn, KdAlertEvent, KdPageBase, KdPageBaseEvent, KdToolbarEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { timer } from 'rxjs';
import { MINI_DASHBOARD_CONFIG, TABLE_COLUMN_LIST } from '../student-attendance/student_attendance.config';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';

@Component({
  selector: 'app-student-attendance-archive',
  templateUrl: './student-attendance-archive.component.html',
  styleUrls: ['./student-attendance-archive.component.css']
})
export class StudentAttendanceArchiveComponent extends KdPageBase implements OnInit, OnDestroy {
  public displayLoading: boolean = false;

  public pageheader = {
    leftBtn: [{
      type: "export",
      callback: (): void => {
        this.exportData();
      }
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  institution_id: any;
  institution_name: string;
  public _column: Array<ITableColumn>;
  counter: any = 0;
  themeArray = DEFAULT_TEMPLATE_THEME;
  academicYear: any = [];
  academic_Period: any;
  public miniDashboardConfig: IMiniDashboardConfig = MINI_DASHBOARD_CONFIG;
  public miniDashboardData: Array<IMiniDashboardItem>;
  public displayMiniDashboard: boolean = false;
  public _row: Array<any> = [];
  public _config: any;
  public _tableApi: ITableApi = {};
  displayDaySubject: boolean = false;
  readonly TABLEID: string = "normalTable";
  readonly PAGESIZE: number = 10;
  readonly TOTALROWS: number = 50000;

  public academicPeriod: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Academic Period',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': [],
      'value': ''
    }
  ]
  public week: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Week',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': [],
      'value': ''
    }
  ]
  public day: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Day',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [],
      'value': ''
    }
  ]
  public class: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Class',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [],
      'value': ''
    }
  ]
  public period: Array<any> = [
    {
      'key': 'radio',
      'label': 'Attendance per day:',
      'visible': true,
      'required': false,
      'controlType': 'radio',
      'type': 'radio',
      'list': [],
      'value': ''
    }
  ]
  public educationGrade: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Education Grade',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [],
      'value': ''
    }
  ]

  public institutionSubject: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Subjects:',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': []
    }
  ]

  public filterButtons: Array<any> = [
    {
      name: '',
      class: 'd-none'
    },
    {
      name: '',
      class: 'd-none'
    }
  ]
  absent: number;
  late: number;

  constructor(
    private _router: Router,
    _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private Rest: ApiService,
    private _toolbarEvent: KdToolbarEvent,
    private _kdAlertEvent: KdAlertEvent
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: pageEvent,
    });
  }

  ngOnInit(): void {
    super.setPageTitle("", false);
    super.setToolbarMainBtns([]);
    super.enableToolbarSearch(true);

    super.updatePageHeader();
    super.updateBreadcrumb();

    this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
    this.institution_id = 6;
    this.institution_name = localStorage.getItem("institutionName");
    this.pageheader.pageheaderText = `${this.institution_name} - Institution Student Absences Archived`;

    timer(10).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.student_attendance_select_new,
        TABLE_COLUMN_LIST.reasonOrComment_select_new
      ];
    });
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
      // this.absenceTypeAPI();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
          // this.absenceTypeAPI();
          this.removeSession();
        }
      },
      error: (error: any) => {

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

  setDashboard() {
    this.absent = 0;
    this.late = 0;
    this._row.forEach((student, index) => {
      if (student.institution_student_absences.absence_type_id == 1 || student.institution_student_absences.absence_type_id == 2) {
        this.absent += 1;
      } else if (student.institution_student_absences.absence_type_id == 3 || student.institution_student_absences.absence_type_id == 3) {
        this.late += 1;
      }
    })

    this.miniDashboardData = [
      {
        type: 'text',
        icon: 'kd-students icon',
        label: 'Total Students:',
        value: this._row.length
      },
      {
        type: 'text',
        label: 'Students Absent',
        value: this.absent
      },
      {
        type: 'text',
        label: 'Students Late',
        value: this.late
      },

    ]
  }

  getAPIData() {
    this.Rest.getWithToken(`academic-period/archive?institution_id=${this.institution_id}`).subscribe({
      next: (response: any) => {
        if (response?.data?.data) {
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.start_year
            }
            this.academicYear.push(obj);
          });
          this.academic_Period = this.academicYear[0].key;
          this.academicPeriod[0].options = this.academicYear;
          this.academicPeriod[0].value = this.academic_Period;
          // this.getAcademicWeek(this.academic_Period);
        } else {
          let obj = {
            key: "",
            value: "No option"
          }
          this.academicYear.push(obj);
          this.academic_Period = this.academicYear[0].key;
          this.academicPeriod[0].options = this.academicYear;
          this.academicPeriod[0].value = this.academic_Period;

          let weekData = this.week;
          weekData[0].options = this.academicYear;
          this.week = [...weekData];

          let dayData = this.day;
          dayData[0].options = this.academicYear;
          this.day = [...dayData];

          let classData = this.class;
          classData[0].options = this.academicYear;
          this.class = [...classData];

          let educationGradeData = this.educationGrade;
          educationGradeData[0].options = this.academicYear;
          this.educationGrade = [...educationGradeData];

          let institutionSubjectData = this.institutionSubject;
          institutionSubjectData[0].options = this.academicYear;
          this.institutionSubject = [...institutionSubjectData];

          this.setDashboard();
          console.log(this._column,"this._column");
          this._config = {
            id: this.TABLEID,
            rowIdKey: "id",
            gridHeight: "auto",
            rowContentHeight: 60,
            loadType: "normal",
            externalFilter: false,
            paginationConfig: {
              pagesize: this.PAGESIZE,
              total: this.TOTALROWS,
            },
            context: {
              // absenceTypes: this.absenceTypeList,
              education_grade_id: 189,
              isMarked: false,
              mode: 'view',
              period: 1,
              schoolClosed: true,
              // studentAbsenceReasons: this.studentAbsenceReasons,
              subject_id: 0,
              week: 49,
              scope: {
                data: []
              }
            }
          }
          
          this.displayMiniDashboard = true;
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

  _submitEvent(event: any, data: any) {

  }

  exportData() {

  }

  ngOnDestroy(): void {

  }

}
