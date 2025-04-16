import { Component, OnInit, ViewChild } from '@angular/core';
import { TABLE_COLUMN_LIST } from './student-meals.config';
import { IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { ITableApi, ITableColumn, ITableConfig, KdAlertEvent, KdPageBase, KdPageBaseEvent, KdTable } from 'openemis-styleguide-lib';
import { timer } from 'rxjs';
import { ApiService } from '../api.service';
import { ActivatedRoute, Router } from '@angular/router';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { saveAs } from 'file-saver'

@Component({
  selector: 'app-student-meals',
  templateUrl: './student-meals.component.html',
  styleUrls: ['./student-meals.component.css']
})
export class StudentMealsComponent extends KdPageBase implements OnInit {
  @ViewChild('changetable') child: KdTable;

  public displayLoading: boolean = true;
  // public breadcrumbList = {
  //   home: { icon: 'fa fa-home', path: this.redirectUrl('https://dmo-tst.openemis.org/core/Dashboard'), toEllipsis: false },
  //   list: []
  // };
  public pageheader: any = {
    leftBtn: [{
      type: "export",
      callback: (): void => {
        this.exportData();
      }
    },
    {
      type: "import",
      callback: (): void => {
        this.importTableFields();
      }
    },
    {
      type: "edit",
      callback: (): void => {
        this.onEditClick();
      }
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  public miniDashboardConfig: any = '';
  public miniDashboardData: Array<IMiniDashboardItem>;
  public displayMiniDashboard: boolean = true;
  public _row: Array<any> = [];
  public _column: Array<ITableColumn>;
  public _config: ITableConfig = {
    id: "normalTable",
    rowIdKey: "id",
    gridHeight: "auto",
    rowContentHeight: 60,
    loadType: "normal",
    externalFilter: false,
    paginationConfig: {
      pagesize: 10,
      total: 50000,
    },
    context: {
      isMarked: false,
      schoolClosed: false,
      mode: 'view',
      mealTypes: [],
      mealBenefitTypeOptions: [],
    }
  }

  public academicPeriod: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Academic Period',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': []
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
      'value': '',
      'options': [],
    }
  ]
  public day: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Day',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'value': '',
      'options': []
    }
  ]
  public class: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Class',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': []
    }
  ]
  public mealProgram: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Meal Program',
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

  public _tableApi: ITableApi = {};
  public displayEditTable: boolean = false
  academicYear: any = [];
  academic_Period: any;
  academic_week: any = [];
  academic_period_week: any;
  start_day: any;
  end_day: any;
  academicYearDay: any;
  academic_day: any = [];
  academic_period_day: any = '';
  academic_class: any = [];
  academic_meal: any = [];
  meal_benefit: any = [];
  selected_academic_class: any;
  selected_meal_program: any;
  meal_distribution: any = [];
  oldRow: any = [];
  absent: number = 0;
  counter: number = 0;
  institution_id: number;
  institution_name: any = '';
  mealImportUrl: any = '';
  institutionNameUrl: any;
  baseUrl: string;
  mealExportUrl: string = '';
  mealHelpUrl: string = '';
  themeArray = DEFAULT_TEMPLATE_THEME;

  constructor(
    private Rest: ApiService,
    private router: Router,
    public pageEvent: KdPageBaseEvent,
    public _router: Router,
    public _activatedRoute: ActivatedRoute,
    private _kdAlertEvent: KdAlertEvent
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: pageEvent,
    });
  }

  ngOnInit(): void {
    this.counter = 0;
    this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
    // this.institution_id = 6;
    this.institution_name = localStorage.getItem("institutionName");
    this.institutionNameUrl = localStorage.getItem("institutionDashborad");
    this.baseUrl = localStorage.getItem("baseUrl");
    this.pageheader.pageheaderText = `${this.institution_name} - - Student Meals`
    // super.updateBreadcrumb();
    this.loginData();
  }

  redirectUrl(url: any) {
    window.open(url)
  }

  exportData() {
    this.Rest.getItemExport(`institutions/students/meals/export?academic_period_id=${this.academic_Period}&day_id=${this.academic_period_day}&week_id=${this.academic_period_week}&week_start_day=${this.start_day}&week_end_day=${this.end_day}&institution_class_id=${this.selected_academic_class}&meal_program_id=${this.selected_meal_program}&institution_id=${this.institution_id}`).subscribe({
      next: (response: any) => {
        let url = window.URL.createObjectURL(response);
        let a = document.createElement('a');
        document.body.appendChild(a);
        a.setAttribute('style', 'display: none');
        a.href = url;
        a.download = response.filename || 'Student meals';
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

      },
      error: (error: any) => {


      }
    })
  }

  importTableFields() {
    // let baseData = this.getBaseUrl();
    // console.log(this.baseUrl, "baseUrl");
    // console.log(baseData, "baseData");
    // console.log(this.mealImportUrl, "mealImportUrl");

    let tokenData = localStorage.getItem('encoded_url');
    if (tokenData) {
      localStorage.setItem("institution_id", JSON.stringify(this.institution_id));
      localStorage.setItem("academic_Period", JSON.stringify(this.academic_Period));
      this.router.navigateByUrl(`Institution/Institutions/${tokenData}/ImportStudentMeals/add`);
    }
  }

  onEditClick() {
    if (!this.selected_meal_program || this._row.length == 0) {
      let toasterConfig: any = {
        title: 'Please select necessary options',
        showCloseButton: true,
        tapToDismiss: true,
      };

      this._kdAlertEvent.warn(toasterConfig);
      return;
    }
    this.displayLoading = true;
    let toasterConfig: any = {
      title: 'Student meal will be saved automatically.',
      showCloseButton: true,
      tapToDismiss: true,
    };

    this._kdAlertEvent.info(toasterConfig);
    this.pageheader = {
      leftBtn: [
        {
          type: "back",
          callback: (): void => {
            this.onBackClick();
          }
        }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: `${this.institution_name} - - Student Meals`,
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
    this.displayEditTable = !this.displayEditTable;
    this.child.setStudentMeal(this.displayEditTable);
    this.displayMiniDashboard = false;
    setTimeout(() => {
      this.displayLoading = false;
    }, 1000);

    let academic_Period = this.academicPeriod;
    academic_Period[0].disabled = true;
    this.academicPeriod = [...academic_Period];

    let academic_Week = this.week;
    academic_Week[0].disabled = true;
    this.week = [...academic_Week];

    let academic_Day = this.day;
    academic_Day[0].disabled = true;
    this.day = [...academic_Day];

    let academic_Shift = this.class;
    academic_Shift[0].disabled = true;
    this.class = [...academic_Shift];

    let meal_Program = this.mealProgram;
    meal_Program[0].disabled = true;
    this.mealProgram = [...meal_Program];
  }

  onBackClick() {
    this.displayLoading = true;
    this.displayEditTable = !this.displayEditTable;
    this.child.setStudentMeal(this.displayEditTable);
    this.displayMiniDashboard = true;
    this._row.forEach((item) => {
      const indexInArray2: any = this.findIndexInArray2(item.meal_received_id, item.user.openemis_no, item.meal_benefit_id);
      if (indexInArray2.index != -1 && indexInArray2.data == 'notNull') {
        this.callPostMealAPI(item);
        this.setDashboard();
      } else if (indexInArray2.index != -1 && indexInArray2.data == null) {
        item.meal_received_id = 1;
        this.callPostMealAPI(item);
        this.setDashboard();
      }
    });
    this.oldRow = JSON.parse(JSON.stringify(this._row));
    setTimeout(() => {
      this.displayLoading = false;
    }, 1000);
    if (this.mealHelpUrl != '') {
      this.pageheader = {
        leftBtn: [{
          type: "export",
          callback: (): void => {
            this.exportData();
          }
        },
        {
          type: "import",
          callback: (): void => {
            this.importTableFields();
          }
        },
        {
          type: "edit",
          callback: (): void => {
            this.onEditClick();
          }
        },
        {
          custom: true,
          icon: 'fa fa-question-circle',
          tooltip: 'Help',
          callback: (): void => {
            this.helpData();
          }
        },
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: `${this.institution_name} - - Student Meals`,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
    } else {
      this.pageheader = {
        leftBtn: [{
          type: "export",
          callback: (): void => {
            this.exportData();
          }
        },
        {
          type: "import",
          callback: (): void => {
            this.importTableFields();
          }
        },
        {
          type: "edit",
          callback: (): void => {
            this.onEditClick();
          }
        }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: `${this.institution_name} - - Student Meals`,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
    }

    let academic_Period = this.academicPeriod;
    academic_Period[0].disabled = false;
    this.academicPeriod = [...academic_Period];

    let academic_Week = this.week;
    academic_Week[0].disabled = false;
    this.week = [...academic_Week];

    let academic_Day = this.day;
    academic_Day[0].disabled = false;
    this.day = [...academic_Day];

    let academic_Shift = this.class;
    academic_Shift[0].disabled = false;
    this.class = [...academic_Shift];

    let meal_Program = this.mealProgram;
    meal_Program[0].disabled = false;
    this.mealProgram = [...meal_Program];
  }

  findIndexInArray2(keyValue: any, id: any, meal_benefit_id: any) {
    for (let i = 0; i < this.oldRow.length; i++) {
      if (this.oldRow[i].meal_received_id != keyValue && this.oldRow[i].user.openemis_no == id || keyValue == null && this.oldRow[i].user.openemis_no == id ||
        this.oldRow[i].meal_benefit_id != meal_benefit_id && this.oldRow[i].user.openemis_no == id
      ) {
        if ((this.oldRow[i].meal_received_id == null && keyValue == 3) || (this.oldRow[i].meal_received_id == 3 && keyValue == null)) {
          let obj = {
            index: -1,
            data: 'notNull'
          }
          return obj;
        } else if (this.oldRow[i].meal_received_id == null && keyValue == null) {
          let obj = {
            index: i,
            data: null
          }
          return obj;
        } else if (this.oldRow[i].meal_benefit_id != meal_benefit_id) {
          let obj = {
            index: i,
            data: 'notNull'
          }
          return obj;
        } else {
          let obj = {
            index: i,
            data: 'notNull'
          }
          return obj;
        }
      }
    }
    let obj = {
      index: -1,
      data: 'notNull'
    }
    return obj; // Return -1 if the value is not found
  }

  callPostMealAPI(data: any) {

    let obj = {
      "institution_id": data?.institution_id,
      "institution_class_id": data?.institution_class_id,
      "academic_period_id": data?.academic_period_id,
      "date": this.academic_period_day,
      "student_id": data?.student_id,
      "meal_programmes_id": data?.meal_program_id,
      "meal_received_id": data?.meal_received_id,
      "meal_benefit_id": data?.meal_benefit_id
    }
    this.Rest.postWithToken('institutions/students/meal-benefits', obj).subscribe({
      next: (response: any) => {
        console.log(response, "response");

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

  setDashboard() {
    this.absent = 0;
    this._row.forEach((student, index) => {
      if (student.meal_received_id == 1) {
        this.absent += 1;
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
        label: 'Meal Received',
        value: this.absent
      }
    ]

    timer(1000).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.mealReceived,
        TABLE_COLUMN_LIST.mealBenefit
      ];
    });
  }

  loginData() {
    // this.Rest.setSession();
    let token = localStorage.getItem("loginToken");
    if (!token) {
      let userName = sessionStorage.getItem('nbn');
      let password = sessionStorage.getItem('pbn');
      const chars = password.split('.');
      password = chars[0];
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
        // decodedPassword = decodedPassword.replace(/^"(.*)"$/, '$1');
        decodedPassword = decodedPassword.replace(/[\[\]"]/g, '');
        console.log(decodedPassword,"decodedPassword");
        if (userName && decodedPassword) {
          this.loginApi(userName, decodedPassword);
        } else {
          this.removeSession();
        }
      }
    } else {
      this.setTheme();
      this.getAPIData();
      this.mealBenefitType();
      this.mealType();
    }
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

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
          this.mealBenefitType();
          this.mealType();
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

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  mealBenefitType() {
    this.Rest.getWithToken('meal-benefit-types').subscribe({
      next: (response: any) => {
        if (response) {
          this.meal_benefit = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              id: element.id,
              name: element.name,
              default: element.default
            }
            this.meal_benefit.push(obj);
          });
          this._config.context.mealBenefitTypeOptions = this.meal_benefit;
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

  mealType() {
    this.Rest.getWithToken(`institutions/${this.institution_id}/meal-distributions`).subscribe({
      next: (response: any) => {
        if (response) {
          this.meal_distribution = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              id: element.id,
              name: element.name,
              code: element.code
            }
            this.meal_distribution.push(obj);
          });
          this._config.context.mealTypes = this.meal_distribution;

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
    this.Rest.getWithToken('academic-periods').subscribe({
      next: (response: any) => {
        if (response) {

          this.academicYear = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.start_year
            }
            this.academicYear.push(obj);
          });
          this.academic_Period = this.academicYear[0].key;
          this.academicPeriod[0].options = this.academicYear;
          this.getAcademicWeek(this.academic_Period);
          this.getClassData()
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
            this.start_day = element?.start_day;
            this.end_day = element?.end_day;
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
        this.academicYearDay = response?.data?.list;
        this.academic_day = [];
        this.academic_period_day = '';
        response?.data?.list.forEach((element: any, index: any) => {
          if (index != 0) {
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
          }
        });
        if (this.academic_period_day == '') {
          this.academic_period_day = this.academic_day[0].key;
        }
        let newDay = this.day;
        newDay[0].options = this.academic_day;
        newDay[0].value = this.academic_period_day;
        this.day = [...newDay];
        this.getClassData();
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

  getClassData() {
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
          this.class = [...classData];
          this.displayLoading = false;
          this.getMealProgramData();
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

  getMealProgramData() {
    this.Rest.getWithToken(`institutions/${this.institution_id}/meal-programmes?academic_period_id=${this.academic_Period}`).subscribe({
      next: (response: any) => {
        if (response) {
          this.academic_meal = [];
          if (response?.data?.data.length > 0) {
            response?.data?.data.forEach((element: any) => {
              let obj = {
                key: element?.id,
                value: element?.name
              }
              this.academic_meal.push(obj);
            });
          } else {
            let obj = {
              key: null,
              value: 'No Options'
            }
            this.academic_meal.push(obj);
          }
          this.selected_meal_program = this.academic_meal[0]?.key;
          let mealData = this.mealProgram;
          mealData[0].options = this.academic_meal;
          this.mealProgram = [...mealData];
          this.getMealStudent(this.selected_meal_program);
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

  getMealStudent(id: any) {
    this.selected_meal_program = id;
    // This is dynamic link 
    // institutions/${this.institution_id}/meal-students?academic_period_id=${this.academic_Period}&day_id=${this.academic_period_day}&institution_class_id=${this.selected_academic_class}&meal_program_id=${this.selected_meal_program}

    //This is static link
    // institutions/${this.institution_id}/meal-students?academic_period_id=33&day_id=2024-01-30&institution_class_id=591&meal_program_id=3
    console.log(this.academic_Period, "academic_Period");
    console.log(this.academic_period_day, "this.academic_period_day");
    console.log(this.selected_academic_class, "this.selected_academic_class");
    console.log(this.selected_meal_program, "selected_meal_program");

    if (this.academic_Period && this.academic_period_day && this.selected_academic_class && this.selected_meal_program) {
      this.Rest.getWithToken(`institutions/${this.institution_id}/meal-students?academic_period_id=${this.academic_Period}&day_id=${this.academic_period_day}&institution_class_id=${this.selected_academic_class}&meal_program_id=${this.selected_meal_program}`).subscribe({
        next: (response: any) => {
          if (response && response?.data?.data?.length > 0) {
            let newDataRow = [];
            this._row = [];
            response.data.data.forEach((element: any) => {
              this._row.push(element);
              newDataRow.push(element);
              this.setDashboard();
              this.displayLoading = false;
            });
            this.oldRow = JSON.parse(JSON.stringify(newDataRow));
            let tokenData = localStorage.getItem('meal_url_data');
            let tokenArray = tokenData.split(".");
            this.mealImportUrl = tokenArray[1];

            let importUrl = response?.data?.url?.import;
            this.mealImportUrl = importUrl.replace("cake_session_id", this.mealImportUrl);
            console.log(this.mealImportUrl, "this.mealImportUrl 11");
            let exportUrl = response?.data?.url?.export;
            this.mealExportUrl = exportUrl.replace("cake_session_id", this.mealImportUrl);

            if (response?.data?.url?.help) {
              this.mealHelpUrl = response?.data?.url?.help;
              this.pageheader = {
                leftBtn: [{
                  type: "export",
                  callback: (): void => {
                    this.exportData();
                  }
                },
                {
                  type: "import",
                  callback: (): void => {
                    this.importTableFields();
                  }
                },
                {
                  type: "edit",
                  callback: (): void => {
                    this.onEditClick();
                  }
                },
                {
                  custom: true,
                  icon: 'fa fa-question-circle',
                  tooltip: 'Help',
                  callback: (): void => {
                    this.helpData();
                  }
                },
                ],
                moreAction: [],
                moreBtn: false,
                pageheaderText: `${this.institution_name} - - Student Meals`,
                searchBtn: false,
                searchEvent: ['change', 'keyup']
              }
            }

            console.log(this.mealImportUrl, "result", this.mealExportUrl);
          } else {
            this._row = [];
            this.displayLoading = false;
            let toasterConfig: any = {
              title: 'No data found',
              showCloseButton: true,
              tapToDismiss: true,
            };

            this._kdAlertEvent.warn(toasterConfig);
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
    } else {
      this.displayLoading = false;
      let toasterConfig: any = {
        title: 'Please select necessary options',
        showCloseButton: true,
        tapToDismiss: true,
      };

      this._kdAlertEvent.warn(toasterConfig);
      this._row = [];
      this.setDashboard();
    }

  }

  getBaseUrl() {
    if (document.cookie) {
      let base_url: any = document.cookie.split('; ')
        .find(row => row.startsWith(`my_base_url=`))?.split('=')
      if (base_url && base_url[1]) {
        let setBaseUrl = decodeURIComponent(base_url[1]);
        if (setBaseUrl == '/') {
          return '/';
        }
        return `${setBaseUrl}`
      } else {
        return '/'
      }
    } else {
      return '/'
    }
  }

  helpData() {
    window.open(this.mealHelpUrl, "_self");
  }

  _submitEvent(event: any, type: any) {
    this.displayLoading = true;
    switch (type) {
      case 'academicPeriod': {
        this.getAcademicWeek(event.target.value);
        this.academicPeriod[0].value = event.target.value;
        break;
      }
      case 'week': {
        this.getAcademicDay(event.target.value);
        this.week[0].value = event.target.value;
        break;
      }
      case 'day': {
        this.getClassData();
        this.day[0].value = event.target.value;
        this.academic_period_day = event.target.value;
        this.getClassData();
        this.getMealProgramData();
        break;
      }
      case 'class': {
        this.selected_academic_class = event.target.value;
        this.class[0].value = event.target.value;
        this.getMealProgramData();
        break;
      }
      case 'mealProgram': {
        this.getMealStudent(event.target.value);
        this.mealProgram[0].value = event.target.value;
        break;
      }
    }

  }

  closeTostr(data: any) {
    switch (data) {
      case 'option': {
        break;
      }
      case 'meal': {
        break;
      }
    }
  }

}
