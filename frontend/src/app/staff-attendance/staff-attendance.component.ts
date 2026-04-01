import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ITableApi, ITableColumn, ITableConfig, KdAlertEvent, KdPageBase, KdPageBaseEvent, KdTable, KdTableEvent, KdToolbarEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { MINI_DASHBOARD_CONFIG, TABLE_COLUMN_LIST } from './staff-attendance.config';
import { Subscription, timer } from 'rxjs';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

@Component({
  selector: 'app-staff-attendance',
  templateUrl: './staff-attendance.component.html',
  styleUrls: ['./staff-attendance.component.css']
})
export class StaffAttendanceComponent extends KdPageBase implements OnInit {
  @ViewChild('changetable') child: KdTable;
  readonly TABLEID: string = "infinite";
  readonly PAGESIZE: number = 10;
  readonly TOTALROWS: number = 50000;

  public displayLoading: boolean = true;
  public miniDashboardConfig: IMiniDashboardConfig = MINI_DASHBOARD_CONFIG;
  public miniDashboardData: Array<IMiniDashboardItem>

  present: number = 0;
  absent: number = 0;
  late: number = 0;

  public displayEditTable: boolean = false
  public fieldDisable: boolean = false;
  public _tableApi: ITableApi = {};
  public _row: Array<any>;
  public _column: Array<ITableColumn>;
  public _config: ITableConfig = {
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
      action: this.displayEditTable ? 'edit' : 'view',
      ownEdit: 1,
      otherEdit: 1,
      permissionStaffId: 8810,
      period: '2023'
    }
  }

  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Institutions',
      path: '',
    },
    {
      name: 'Avory Primary School',
      path: '',
    },
    {
      name: 'Student Attendances',
      path: '',
    }]
  };

  public pageheader = {
    leftBtn: [
      {
        type: "import",
        callback: (): void => {
          this.editTableFields();
        }
      },
      {
        type: "edit",
        callback: (): void => {
          this.onEditClick();
        }
      },
      {
        type: "export",
        callback: (): void => {
          this.backToData();
        }
      },
      {
        icon: "fa fa-folder",
        callback: (): void => {
          this.onFolderClick();
        }
      },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

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

  public academicPeriod: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Academic Period:',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': []
    }
  ]

  public academicWeek: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Week:',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'value': '',
      'options': [],
    }
  ]

  public academicDay: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Day:',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'value': '',
      'options': [],
    }
  ]

  public academicShift: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Shift:',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'value': '',
      'options': [],
    }
  ]

  public _toolbarSearchSub: any;
  private _tableSub: any;
  academicYear: any = [];
  academic_period: any = '';
  academicYearWeek: any = [];
  academic_period_week: any;
  academicYearDay: any = [];
  academic_period_day: any;
  academic_Shift_data: any = [];
  academic_shift: any;
  start_day: any;
  end_day: any;
  selected_day: any;
  staff_data: any;
  day_id: any;
  counter: number = 0;
  selected_day_value: any;
  oldRowData: any;
  institution_id: number;
  themeArray = DEFAULT_TEMPLATE_THEME;
  institution_name: string;

  constructor(
    _router: Router,
    _activatedRoute: ActivatedRoute,
    _pageEvent: KdPageBaseEvent,
    private _tableEvent: KdTableEvent,
    private _toolbarEvent: KdToolbarEvent,
    private Rest: ApiService,
    private _kdAlertEvent: KdAlertEvent
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: _pageEvent,
    });
  }

  ngOnInit(): void {
    super.setPageTitle("Table - Staff Attendance", false);
    super.setToolbarMainBtns([
      {
        type: 'edit',
        callback: () => {
          this.onEditClick();
        }
      }
    ]);
    // super.enableToolbarSearch(true);
    super.updatePageHeader();
    super.updateBreadcrumb();
    this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
    // this.institution_id = 6;
    this.institution_name = localStorage.getItem("institutionName");
    this.pageheader.pageheaderText = `${this.institution_name} - Institution Staff Attendances`
    
    timer(1000).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.staffOpenEmisId,
        TABLE_COLUMN_LIST.staffName,
        TABLE_COLUMN_LIST.inAndOutTime,
        TABLE_COLUMN_LIST.leave,
        TABLE_COLUMN_LIST.comment
      ];
    });

    timer(2000).subscribe((): void => {
      this.counter = 0;
      this.loginData();
    })

    this._toolbarSearchSub = this._toolbarEvent
      .onSendSearchText()
      // .debounceTime(500)
      .subscribe((_text: string): void => {
        this._tableApi.general.searchRow(_text);
      });

    this._tableSub = this._tableEvent
      .onKdTableEventList(this.TABLEID)
      .subscribe((_event: any): void => { });
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
      this.removeSession();
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
          this.removeSession();
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
        console.log(response?.data[3].default_value, "response");
        let selectedThemeData = '';
        if(response?.data[3].value){
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

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  getAPIData() {
    this.Rest.getWithToken('academic-periods').subscribe({
      next: (res: any) => {
        if (res) {
          res?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.start_year
            }
            this.academicYear.push(obj);
          });
          this.academic_period = this.academicYear[0].key;
          let academic_years = this.academicPeriod;
          academic_years[0].options = this.academicYear;
          academic_years[0].value = this.academicYear[0].key;
          this.academicYear = [...academic_years];
          this.getAcademicWeek(this.academic_period);
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

  getAcademicWeek(id: any) {
    this.academic_period = id;
    this.Rest.getWithToken('academic-periods/' + this.academic_period + '/weeks').subscribe({
      next: (res: any) => {
        if (res) {
          this.academicYearWeek = [];
          this.academic_period_week = '';
          res?.data?.list?.weeks.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name,
              start_day: element.start_day,
              end_day: element.end_day,
              selected: element?.selected
            }
            if (obj?.selected) {
              this.academic_period_week = element.id;
              this.start_day = element?.start_day;
              this.end_day = element?.end_day;
            }
            this.academicYearWeek.push(obj);
          });
          if (this.academic_period_week == '') {
            this.academic_period_week = this.academicYearWeek[0]?.id;
            this.start_day = this.academicYearWeek[0]?.start_day;
            this.end_day = this.academicYearWeek[0]?.end_day;
          }
          let week = this.academicWeek;
          week[0].options = this.academicYearWeek;
          week[0].value = this.academic_period_week;
          this.academicWeek = [...week];
          this.getAcademicShift();
          this.getAcademicDay(this.academic_period_week);
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

  getAcademicDay(id: any) {
    this.academic_period_week = id;
    this.Rest.getWithToken('academic-periods/' + this.academic_period + '/weeks/' + this.academic_period_week + '/days?institution_id=' + this.institution_id + '&school_closed_required=true').subscribe({
      next: (res: any) => {
        if (res) {
          this.academicYearDay = [];
          this.academic_period_day = '';
          this.selected_day = '';
          this.selected_day_value = '';
          this.day_id = '';
          res?.data?.list.forEach((element: any) => {
            let obj = {
              key: element.date,
              value: element.name,
              current_week_number_selected: element.current_week_number_selected,
              date: element.id,
              selected: element.day_number,
              day_number: element.day_number
            }
            if (obj.day_number) {
              this.academic_period_day = element.date;
              this.selected_day = element.date;
              this.selected_day_value = element.name;
              this.day_id = element.id;
            }
            this.academicYearDay.push(obj);
          });
          if (this.academic_period_day == '') {
            this.academic_period_day = this.academicYearDay[0].key;
            this.selected_day = this.academicYearDay[0].key;
            this.selected_day_value = this.academicYearDay[0].value;
            this.day_id = this.academicYearDay[0].date;
          }
          let newDay = this.academicDay;
          newDay[0].options = this.academicYearDay;
          newDay[0].value = this.academic_period_day;
          this.academicDay = [...newDay];
          this.getStaffData();
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

  getAcademicShift() {
    this.Rest.getWithToken('institutions/' + this.institution_id + '/shift-options?academic_period_id=' + this.academic_period + '&institution_id=' + this.institution_id).subscribe({
      next: (res: any) => {
        if (res) {
          this.academic_Shift_data = [];
          res?.data.forEach((element: any) => {
            let obj = {
              key: element?.id,
              value: element?.name
            }
            this.academic_Shift_data.push(obj);
          });
          this.academic_shift = this.academic_Shift_data[0].key;
          let shift = this.academicShift;
          shift[0].options = this.academic_Shift_data;
          shift[0].value = this.academic_Shift_data[0].key;
          this.academicShift = [...shift];
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

  getStaffData() {
    this.Rest.getWithToken
      ('institutions/' + this.institution_id + '/staff/attendances?academic_period_id=' + this.academic_period +
        '&institution_id=' + this.institution_id + '&week_id=' + this.academic_period_week +
        '&week_start_day=' + this.start_day + '&week_end_day=' + this.end_day +
        '&day_id=' + this.day_id + '&shift_id=' + this.academic_shift +
        '&day_date=' + this.selected_day + '&own_attendance_view=1&own_attendance_edit=1&other_attendance_view=1&other_attendance_edit=1').
      subscribe({
        next: (res: any) => {
          if (res) {
            this._row = res?.data?.list;
            this.oldRowData = JSON.parse(JSON.stringify(this._row));
            this.setDashboard();
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

  saveStaffData(item: any) {
    let date = item.date;
    let paramsData = {
      "staff_id": item?._matchingData?.User.id,
      "institution_id": this.institution_id,
      "academic_period_id": this.academic_period,
      "date": this.selected_day,
      "shift_id": parseInt(this.academic_shift),
      "time_in": item?.attendance[date]?.time_in,
      "time_out": item?.attendance[date]?.time_out,
      "comment": item?.attendance[date]?.comment
    }
    this.Rest.postWithToken('institutions/staff/attendances', paramsData).subscribe({
      next: (res: any) => {
        let toasterConfig: any = {
          title: 'Time record successfully saved',
          showCloseButton: true,
          tapToDismiss: true,
        };

        this._kdAlertEvent.success(toasterConfig);

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

  onEditClick() {
    if (this.academic_shift == -1) {
      let toasterConfig: any = {
        title: 'Please select shift',
        showCloseButton: true,
        tapToDismiss: true,
      };

      this._kdAlertEvent.error(toasterConfig);
      return
    }
    this.displayLoading = true;
    this.displayEditTable = !this.displayEditTable;
    if (this.displayEditTable) {
      this.fieldDisable = true;
      let toasterConfig: any = {
        title: 'Attendance will be saved automatically',
        showCloseButton: true,
        tapToDismiss: true,
      };

      this._kdAlertEvent.info(toasterConfig);
      this.pageheader = {
        leftBtn: [
          {
            type: "back",
            callback: (): void => {
              this.onEditClick();
            }
          }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: `${this.institution_name} - Institution Staff Attendances`,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }

      let academic_Period = this.academicPeriod;
      academic_Period[0].disabled = true;
      this.academicPeriod = [...academic_Period];

      let academic_Week = this.academicWeek;
      academic_Week[0].disabled = true;
      this.academicWeek = [...academic_Week];

      let academic_Day = this.academicDay;
      academic_Day[0].disabled = true;
      this.academicDay = [...academic_Day];

      let academic_Shift = this.academicShift;
      academic_Shift[0].disabled = true;
      this.academicShift = [...academic_Shift];

    } else {
      this.fieldDisable = false;
      this.pageheader = {
        leftBtn: [
          {
            type: "import",
            callback: (): void => {
              this.editTableFields();
            }
          },
          {
            type: "edit",
            callback: (): void => {
              this.onEditClick();
            }
          },
          {
            type: "export",
            callback: (): void => {
              this.backToData();
            }
          },
          {
            icon: "fa fa-folder",
            callback: (): void => {
              this.onFolderClick();
            }
          },
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: `${this.institution_name} - Institution Staff Attendances`,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
      let academic_Period = this.academicPeriod;
      academic_Period[0].disabled = false;
      this.academicPeriod = [...academic_Period];

      let academic_Week = this.academicWeek;
      academic_Week[0].disabled = false;
      this.academicWeek = [...academic_Week];

      let academic_Day = this.academicDay;
      academic_Day[0].disabled = false;
      this.academicDay = [...academic_Day];

      let academic_Shift = this.academicShift;
      academic_Shift[0].disabled = false;
      this.academicShift = [...academic_Shift];
      this.checkChangesData();
    }

    setTimeout(() => {
      this.child.setAttendanceStaff(this.displayEditTable);
    }, 100);

    setTimeout(() => {
      this.setDashboard();
    }, 200);
    this.displayLoading = false;
  }

  checkChangesData() {
    this._row.forEach((item) => {
      let date = item.date;
      const indexInArray2 = this.findIndexInArray2(item?.attendance[date]?.time_in, item?.attendance[date]?.time_out, item._matchingData.User.openemis_no);
      if (indexInArray2 !== -1) {
        this.oldRowData = JSON.parse(JSON.stringify(this._row));
        this.saveStaffData(item);
      }
    });
  }

  findIndexInArray2(time_in: any, time_out: any, id: any) {
    for (let i = 0; i < this.oldRowData.length; i++) {
      let date = this.oldRowData[i].date;
      if ((this.oldRowData[i].attendance[date].time_in != time_in || this.oldRowData[i].attendance[date].time_out != time_out) && this.oldRowData[i]._matchingData.User.openemis_no == id) {
        if ((this.oldRowData[i].attendance[date].time_in == null && time_in == 0) ||
          (this.oldRowData[i].attendance[date].time_in == 0 && time_in == null) &&
          (this.oldRowData[i].attendance[date].time_out == null && time_out == 0) ||
          (this.oldRowData[i].attendance[date].time_out == 0 && time_out == null)) {
          return -1;
        }
        else {
          return i;
        }
      }
    }
    return -1; // Return -1 if the value is not found
  }

  setDashboard() {
    this.present = this.absent = this.late = 0;
    this._row?.forEach((currentStaff) => {
      let date = currentStaff.date

      if (!currentStaff.attendance[date].leave.length) {
        this.present++;
      } else {
        this.absent++;
      }

    })

    this.miniDashboardData = [
      {
        type: 'text',
        icon: 'kd-students icon',
        label: 'Total',
        value: this._row?.length
      },
      {
        type: 'text',
        label: 'Present',
        value: this.present
      },
      {
        type: 'text',
        label: 'Absent',
        value: this.absent
      },
      {
        type: 'text',
        label: 'Late',
        value: this.late
      },

    ]
  }

  changeClick(element: any, type: any) {
    switch (type) {
      case 'academicPeriod': {
        this.getAcademicWeek(element.target.value);
        break;
      }

      case 'academicWeek': {
        this.academicYearWeek.forEach((data: any) => {
          if (data.key == element.target.value) {
            this.start_day = data?.start_day;
            this.end_day = data?.end_day;
          }
        });
        this.getAcademicDay(element.target.value);
        break;
      }

      case 'academicDay': {
        this.academicYearDay.forEach((data: any) => {
          if (data.key == element.target.value) {
            this.selected_day = data?.key;
            this.selected_day_value = data?.value;
            this.day_id = data?.date;
          }
        });
        this.getStaffData();
        break;
      }

      case 'academicShift': {
        this.academic_shift = element.target.value;
        this.getStaffData();
        break;
      }
    }
  }

  closeTostr(data: any) {
    switch (data) {
      case 'shift': {
        break;
      }
      case 'attendance': {
        break;
      }
      case 'successSave': {
        break;
      }
    }
  }

  backToData() {

  }

  editTableFields() { }

  onFolderClick() { }
}
