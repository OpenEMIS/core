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
    leftBtn: [
    //   {
    //   type: "export",
    //   callback: (): void => {
    //     this.exportData();
    //   }
    // }
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
  academic_week: any = [];
  academic_period_week: any;
  start_day: any;
  end_day: any;
  selected_day_week: any;
  selected_day: any;
  academic_day: any = [];

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
  academic_period_day: any;
  academic_class: any[] = [];
  selected_academic_class: any;
  education_grade: any[];
  education_grade_id: any;
  selected_education_grade: any;
  attendanceMarkType: any[];
  selected_institution_subject: number;
  instution_subject: any[];
  selectedInstitutionSubject: any;
  studentAbsenceReasons: any = [];
  absenceTypeList: any;
  displayEditTable: boolean = false;

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
    // this.institution_id = 6;
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
    // this.Rest.setSession(); //POCOR-9594: CakePHP template injects real credentials via sessionStorage
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
      this.absenceTypeAPI();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
          this.absenceTypeAPI();
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

  absenceTypeAPI() {
    this.Rest.getWithToken('absence-types').subscribe({
      next: (response: any) => {
        if (response) {
          this.absenceTypeList = response?.data?.data;
          this.absenceReasonAPI();
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

  absenceReasonAPI() {
    this.Rest.getWithToken('absence-reasons').subscribe({
      next: (response: any) => {
        if (response) {
          this.studentAbsenceReasons = response?.data?.data;
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
              absenceTypes: this.absenceTypeList,
              education_grade_id: 189,
              isMarked: false,
              mode: this.displayEditTable ? 'edit' : 'view',
              period: 1,
              schoolClosed: true,
              studentAbsenceReasons: this.studentAbsenceReasons,
              subject_id: 0,
              week: 49,
              scope: {
                data: []
              }
            }
          }
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
    this.Rest.getWithToken(`academic-period/archive?institution_id=${this.institution_id}`).subscribe({
      next: (response: any) => {
        if (response?.data?.data.length > 0) {
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
          this.getAcademicWeek(this.academic_Period);
        } else {
          let toasterConfig: any = {
            title: 'No Archive data available',
            showCloseButton: true,
            tapToDismiss: true,
          };

          this._kdAlertEvent.warn(toasterConfig);
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
          console.log(this._column, "this._column");
        }
        // this._config = {
        //   id: this.TABLEID,
        //   rowIdKey: "id",
        //   gridHeight: "auto",
        //   rowContentHeight: 60,
        //   loadType: "normal",
        //   externalFilter: false,
        //   paginationConfig: {
        //     pagesize: this.PAGESIZE,
        //     total: this.TOTALROWS,
        //   },
        //   context: {
        //     // absenceTypes: this.absenceTypeList,
        //     education_grade_id: 189,
        //     isMarked: false,
        //     mode: 'view',
        //     period: 1,
        //     schoolClosed: true,
        //     studentAbsenceReasons: this.studentAbsenceReasons,
        //     subject_id: 0,
        //     week: 49,
        //     scope: {
        //       data: []
        //     }
        //   }
        // }

        this.displayMiniDashboard = true;
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

  getAcademicWeek(id: any) {
    this.academic_Period = id;
    this.Rest.getWithToken('academic-periods/' + this.academic_Period + '/weeks').subscribe({
      next: (response: any) => {
        this.academic_week = [];
        response?.data?.list?.weeks.forEach((element: any) => {
          let obj = {
            key: element?.id,
            value: element?.name,
            start_day: element?.start_day,
            end_day: element?.end_day,
            selected: element?.selected
          }
          if (obj.selected) {
            this.academic_period_week = element.id;
            this.selected_day_week = element?.start_day;
            this.start_day = element?.start_day;
            this.end_day = element?.end_day;
            this.selected_day = element?.selected
          }
          this.academic_week.push(obj);
        });
        let academicWeekData = this.week;
        academicWeekData[0].options = this.academic_week;
        academicWeekData[0].value = this.academic_period_week;
        this.week = [...academicWeekData];

        this.getAcademicDay(this.academic_period_week);
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

  getAcademicDay(id: any) {
    this.academic_period_week = id;
    this.Rest.getWithToken('academic-periods/' + this.academic_Period + '/weeks/' + this.academic_period_week + '/days?institution_id=' + this.institution_id + '&school_closed_required=true').subscribe({
      next: (response: any) => {
        this.academic_day = [];
        this.academic_period_day = '';
        response?.data?.list.forEach((element: any, index: any) => {
          let obj = {
            key: element.date,
            value: element.name,
            current_week_number_selected: element.current_week_number_selected,
            date: element.date,
            selected: element.day_number
          }
          if (obj.selected) {
            this.academic_period_day = element.date;
          }
          this.academic_day.push(obj);
        });
        this.academic_day.shift();
        if (this.academic_period_day == '') {
          this.academic_period_day = this.academic_day[0].key;
        }
        let newDay = this.day;
        newDay[0].options = this.academic_day;
        newDay[0].value = this.academic_period_day;
        this.day = [...newDay];
        this.getClassData(this.academic_period_day);
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

  getClassData(id: any) {
    this.academic_period_day = id;
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
          this.selected_academic_class = this.academic_class[0]?.key;
          let classData = this.class;
          classData[0].options = this.academic_class;
          classData[0].value = this.selected_academic_class;
          this.class = [...classData];
          this.getEducationGrade(this.selected_academic_class);
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

  getEducationGrade(id: any) {
    this.selected_academic_class = id;
    this.Rest.getWithToken(`institutions/classes/${id}/grades`).subscribe({
      next: (response: any) => {
        if (response) {
          this.education_grade = [];
          response?.data.forEach((element: any) => {
            let obj = {
              key: element.education_grades.id,
              value: element.education_grades.code,
              education_grade_id: element.education_grade_id
            }
            this.education_grade.push(obj);
          });
          this.education_grade_id = this.education_grade[0].education_grade_id;
          this.selected_education_grade = this.education_grade[0].key;
          let education_grade_data = this.educationGrade;
          education_grade_data[0].options = this.education_grade;
          education_grade_data[0].value = this.selected_education_grade;
          this.educationGrade = [...education_grade_data];
          this.subjectDayInstitution(this.selected_education_grade);
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

  subjectDayInstitution(id: any) {
    this.selected_education_grade = id;
    this.Rest.getWithToken(`grades/${this.selected_education_grade}/attendance-types?academic_period_id=${this.academic_Period}&institution_class_id=${this.selected_academic_class}&day_id=${this.selected_day_week}`).subscribe({
      next: (response: any) => {
        console.log(response, "response Data Topa");
        if (response) {
          if (response?.data?.data[0].code == 'DAY') {
            this.displayDaySubject = true;
            this.studentAttendanceType();
          } else {
            this.displayDaySubject = false;
            this.institutionSubjectClass(id);
          }
        }

      }, error: () => {

      }
    })
  }


  institutionSubjectClass(id: any) {
    this.selected_education_grade = id;
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/subjects?academic_period_id=${this.academic_Period}`).subscribe({
      next: (response: any) => {
        if (response) {
          this.instution_subject = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            this.instution_subject.push(obj);
          });
          this.selectedInstitutionSubject = this.instution_subject[0].key;
          this.selected_institution_subject = this.instution_subject[0].key;
          let insitution_subject_data = this.institutionSubject;
          insitution_subject_data[0].options = this.instution_subject;
          this.institutionSubject = [...insitution_subject_data];
          this.studentAttendanceMarked(this.selected_institution_subject);
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

  studentAttendanceType() {
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/student-attendance-types?academic_period_id=${this.academic_Period}&day_id=${this.selected_day_week}&week_start_day=${this.start_day}&week_end_day=${this.end_day}`).subscribe({
      next: (response: any) => {
        this.attendanceMarkType = [];
        if (response) {
          response?.data?.data.forEach((element: any) => {
            let obj = {
              'title': element.name,
              'value': element.id
            }
            this.attendanceMarkType.push(obj);
          });
          let attendance_mark = this.period;
          attendance_mark[0].list = this.attendanceMarkType;
          attendance_mark[0].value = this.attendanceMarkType[0].value;
          this.period = [...attendance_mark];
          this.selected_institution_subject = 0;
          this.studentAttendanceMarked(0);
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

  studentAttendanceMarked(subjectId: any) {
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/student-attendance-marked/archive?academic_period_id=${this.academic_Period}&day_id=${this.selected_day_week}&attendance_period_id=1&subject_id=${subjectId}`).subscribe({
      next: (response: any) => {
        if (response) {
          this.getStudentAttendanceData();
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

  getStudentAttendanceData() {
    // insitutions/6/grades/206/classes/591/student-attendances?academic_period_id=33&day_id=2024-02-05&attendance_period_id=1&subject_id=0&week_id=6&week_start_day=2024-02-05&week_end_day=2024-02-11
    if (this.academic_period_day == -1) {
      console.log(this._column, "this._column");
      this._column = [
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.monday,
        TABLE_COLUMN_LIST.tuesday,
        TABLE_COLUMN_LIST.wednesday,
        TABLE_COLUMN_LIST.thursday,
        TABLE_COLUMN_LIST.friday
      ];
    } else {
      this._column = [
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.student_attendance_select_new,
        TABLE_COLUMN_LIST.reasonOrComment_select_new
      ];
    }
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/student-attendance/archive?academic_period_id=${this.academic_Period}&day_id=${this.academic_period_day}&attendance_period_id=1&subject_id=${this.selected_institution_subject}&week_id=${this.academic_period_week}&week_start_day=${this.start_day}&week_end_day=${this.end_day}`).subscribe({
      next: (response: any) => {
        if (response) {
          console.log(response.data.data, "this._row 1111");
          this._row = [];
          response?.data?.data.forEach((element: any, index: any) => {
            if (element?.is_NoClassScheduled == 1) {
              element.institution_student_absences.absence_type_id = 99;
              element.institution_student_absences.absence_type_code = 99;
            }
            let institution_student_absences = {
              student_absence_reason_id: element?.student_absence_reason_id,
              absence_type_id: element?.absence_type_id,
              student_absence_reason_name: element?.student_absence_reason
            }
            let obj = {
              academic_period_id: element?.academic_period_id,
              created_date: element?.created_date,
              institution_class_id: element?.institution_class_id,
              institution_class_name: element?.institution_class_name,
              institution_id: element?.institution_id,
              institution_student_absences: institution_student_absences,
              is_NoClassScheduled: element?.is_NoClassScheduled,
              student_id: element?.student_id,
              user: element?.user
            }
            this._row.push(obj);
            // this.oldRowData.push(obj);
          });
          // this._row = newRow;
          //   this.displayLoading = false;
          // this._row = response?.data?.data;
          console.log(this._row, "this._row 1111");
          this.displayLoading = false;
          // this.oldRowData = JSON.parse(JSON.stringify(this._row));
          this.setDashboard();
        }

      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
        this.displayLoading = false;
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
