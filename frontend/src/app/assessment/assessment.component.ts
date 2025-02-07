import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ITableApi, ITableColumn, ITableConfig, KdPageBase, KdPageBaseEvent, KdTable, KdTableEvent, KdToolbarEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { TABLE_COLUMN_LIST } from './assessment_config';
import { timer } from 'rxjs';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

@Component({
  selector: 'app-assessment',
  templateUrl: './assessment.component.html',
  styleUrls: ['./assessment.component.css']
})

export class AssessmentComponent extends KdPageBase implements OnInit, OnDestroy {
  @ViewChild(KdTable) child: KdTable;
  public _state: string = 'social_studies';
  public _row: Array<any> = [];
  public _column: Array<ITableColumn> = [];
  public _tableApi: ITableApi = {};
  public _displayEditable: boolean = false;
  public _editTable: boolean = true;
  public displayLoading: boolean = true;

  public pageHeaderTitle: any = ""

  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Institutions',
      path: '',
    },
    {
      name: 'Avory Primary School',
      path: '',
    }]
  };

  public academicPeriod: Array<any> = [
    {
      'key': '',
      'label': '',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
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

  public pageheader = {
    leftBtn: [{
      type: "back",
      callback: (): void => {
        this.backToData();
      }
    },
    {
      type: "edit",
      callback: (): void => {
        this.editTableFields();
      }
    },
    {
      custom: true,
      icon: 'fa kd-header-row',
      tooltip: 'Report',
      callback: (): void => {
        this.getReport();
      }
    },
    {
      custom: true,
      icon: 'fa fa-file-pdf-o',
      tooltip: 'PDF',
      callback: (): void => {
        this.gePdftReport();
      }
    },
    {
      type: "export",
      callback: (): void => {
        this.exportSheet();
      }
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: this.pageHeaderTitle,
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public tabsListHtml: Array<any> = [];

  public _config: ITableConfig = {
    id: 'listTable',
    gridHeight: "auto",
    loadType: "infinite",
    externalFilter: false,
    rowContentHeight: 25,
    paginationConfig: {
      pagesize: 10,
    },
  };
  academicTerm: any;
  oldData: any = [];
  counter: any = 0;
  institution_id: number;
  themeArray = DEFAULT_TEMPLATE_THEME;
  excelReport: any;
  pdfReport: any;
  reportCard: any;
  queryParamsData: any;
  class_Id: any;
  assessment_id: any;
  gradingTypeOption_id: any;
  academicPeriod_id: any;
  education_grade_id: any;
  assessmentPeriodData: any;

  constructor(
    private _router: Router,
    _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private _tableEvent: KdTableEvent,
    private _toolbarEvent: KdToolbarEvent,
    private Rest: ApiService,
    private router: Router,
    private activatedRoute: ActivatedRoute
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

    this.class_Id = JSON.parse(localStorage.getItem("classId"));
    this.class_Id = 591;
    this.institution_id = JSON.parse(localStorage.getItem("institutionId"));
    this.institution_id = 6;
    this.assessment_id = JSON.parse(localStorage.getItem("assessmentId"));
    this.assessment_id = 34;
    this.gradingTypeOption_id = JSON.parse(localStorage.getItem("gradingTypeOptionID"));
    this.gradingTypeOption_id = 5;
    this.academicPeriod_id = JSON.parse(localStorage.getItem("academicPeriodId"));
    this.academicPeriod_id = 33;

    this.loginData();
    this._column = [];
    this.counter = 0;

    let columns: Array<any> = [];
    columns.push(TABLE_COLUMN_LIST.id);
    columns.push(TABLE_COLUMN_LIST.name);
    columns.push(TABLE_COLUMN_LIST.status);
    columns.push(TABLE_COLUMN_LIST.period1);
    columns.push(TABLE_COLUMN_LIST.period2);
    columns.push(TABLE_COLUMN_LIST.totalMark);
    this._column = columns;

    let queryData = this.activatedRoute.snapshot.queryParamMap.get('queryString');
    this.queryParamsData = queryData.split(".");

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
      this.getAPIlist();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIlist();
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

  getAPIlist() {
    this.assessmentPeriods();
    this.assessmentAcademicPeriod('Term 1');
    this.assessment();
    this.assessmentItems();
  }

  assessmentPeriods() {
    this.Rest.getWithToken(`assessments/${this.assessment_id}/assessmentperiods`).subscribe({
      next: (res: any) => {
        if (res) {
          this.academicTerm = [];
          res?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            this.academicTerm.push(obj);
          });
          // this.academic_Period = this.academicYear[0].key;
          this.academicPeriod[0].options = this.academicTerm;

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

  assessmentAcademicPeriod(data: string) {
    this.Rest.getWithToken(`assessments/${this.assessment_id}/periods?academic_term=${data}`).subscribe({
      next: (res: any) => {
        if (res) {
          this.assessmentPeriodData = res?.data;
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

  assessment() {
    this.Rest.getWithToken(`assessments/${this.assessment_id}`).subscribe({
      next: (res: any) => {
        this.pageHeaderTitle = res?.data?.code_name;
        this.pageheader.pageheaderText = res?.data?.code_name;
        this.education_grade_id = res?.data?.education_grade_id;
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

  assessmentItems() {
    this.Rest.getWithToken(`assessments/${this.assessment_id}/assessmentitems?class_id=${this.class_Id}&academic_period_id=${this.academicPeriod_id}&institution_id=${this.institution_id}`).subscribe({
      next: (res: any) => {
        if (res) {
          res?.data?.data.forEach((element: any, index: any) => {
            let obj = {};
            if (index == 0) {
              obj = {
                education_subject_id: element.InstitutionSubjects.education_subject_id,
                tabId: element.InstitutionSubjects.id,
                tabName: element.InstitutionSubjects.name,
                isActive: true
              }
            } else {
              obj = {
                education_subject_id: element.InstitutionSubjects.education_subject_id,
                tabId: element.InstitutionSubjects.id,
                tabName: element.InstitutionSubjects.name
              }
            }
            this.tabsListHtml.push(obj);
          });
          this._state = this.tabsListHtml[0].tabId;
          this.institutionsSubject(this.tabsListHtml[0].tabId);
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

  institutionsSubject(institution_subject_id) {
    this.Rest.getWithToken(`institutions/subject/student?institution_id=${this.institution_id}&institution_class_id=${this.class_Id}&assessment_id=${this.assessment_id}&academic_period_id=${this.academicPeriod_id}&institution_subject_id=${institution_subject_id}&education_grade_id=${this.education_grade_id}`).subscribe({
      next: (res: any) => {
        if (res) {
          let objData = [];
          // res?.data?.data.forEach((element: any, index: any) => {
          //   if (element) {
          //     console.log(element.mark,"element.mark", element.first_name + ' ' + element.last_name);
          //     let obj = {
          //       id: element.the_student_code,
          //       name: element.first_name + ' ' + element.last_name,
          //       status: element.the_student_status,
          //       period1: element.mark != null ? parseFloat(element.mark) : '',
          //       period2: element.mark != null ? parseFloat(element.mark) : '',
          //       period3: element.mark != null ? parseFloat(element.mark) : '',
          //       totalMark: element.total_mark,
          //       student_id: element?.student_id,
          //       education_subject_id: element?.education_subject_id,
          //       assessment_period_id: element?.assessment_period_id
          //     }
          //     let newIndex = objData.findIndex(element => element.id == obj.id);
          //     if (newIndex == -1) {
          //       objData.push(obj);
          //       this.oldData.push(obj);
          //     } else {
          //       objData.splice(newIndex, 1, obj);
          //       this.oldData.splice(newIndex, 1, obj);
          //     }
          //   }
          // });

          let obj = {};
          let newId = this.assessmentPeriodData?.data[0]?.id;
          if (this.assessmentPeriodData?.data[0]?.academic_term == 'Term 1') {
            res?.data?.data.forEach((element: any, index: any) => {
              if (element.assessment_period_id == newId) {
                obj = {
                  id: element.the_student_code,
                  name: element.first_name + ' ' + element.last_name,
                  status: element.the_student_status,
                  period1: element.mark,
                  totalMark: element.total_mark,
                  student_id: element?.student_id,
                  education_subject_id: element?.education_subject_id,
                  assessment_period_id: element?.assessment_period_id
                }
                objData.push(obj);
              }
            })
            if (this.assessmentPeriodData.data.length > 1) {
              let secondId = this.assessmentPeriodData.data[1].id;
              res?.data?.data.forEach((element: any, index: any) => {
                if (element.assessment_period_id == secondId) {
                  let newIndex = objData.findIndex(element1 => element1.id == element.the_student_code);
                  if (newIndex != -1) {
                    console.log(objData[newIndex], "newIndex");

                    objData[newIndex]['period2'] = element.mark;
                    objData[newIndex]['assessment_period_id2'] = element.assessment_period_id
                  }
                }
              })
            }
          } else if (this.assessmentPeriodData?.data[0]?.academic_term == 'Term 2') {
            res?.data?.data.forEach((element: any, index: any) => {
              if (element.assessment_period_id == newId) {
                obj = {
                  id: element.the_student_code,
                  name: element.first_name + ' ' + element.last_name,
                  status: element.the_student_status,
                  period3: element.mark,
                  totalMark: element.total_mark,
                  student_id: element?.student_id,
                  education_subject_id: element?.education_subject_id,
                  assessment_period_id: element?.assessment_period_id
                }
                objData.push(obj);
              }
            })
          }
          let baseData = this.getBaseUrl();
          if (this.queryParamsData?.length > 0) {
            if (res?.data?.urls?.excel) {
              let importExcelUrl = res?.data?.urls?.excel;
              let finalExcelUrl = importExcelUrl.replace("cake_session_id", this.queryParamsData[1]);
              this.excelReport = baseData + finalExcelUrl;
            }
            if (res?.data?.urls?.pdf) {
              let importExcelUrl = res?.data?.urls?.pdf;
              let finalExcelUrl = importExcelUrl.replace("cake_session_id", this.queryParamsData[1]);
              this.pdfReport = baseData + finalExcelUrl;
            }
            if (res?.data?.urls?.reportCardGenerate) {
              let importExcelUrl = res?.data?.urls?.reportCardGenerate;
              let finalExcelUrl = importExcelUrl.replace("cake_session_id", this.queryParamsData[1]);
              this.reportCard = baseData + finalExcelUrl;
            }
          }
          timer(2000).subscribe((): void => {
            this._row = objData;
            this.oldData = JSON.parse(JSON.stringify(objData));
            this.displayLoading = false;
          });

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

  exportSheet() {
    console.log(this.excelReport, "this.excelReport");

    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(this.excelReport);
    link.download = 'Report';
    link.click();
  }

  gePdftReport() {
    console.log(this.pdfReport, "pdfReport");

    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(this.pdfReport);
    link.download = 'Report';
    link.click();
  }

  getReport() {
    console.log(this.reportCard, "reportCard");

    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(this.reportCard);
    link.download = 'Report';
    link.click();
  }


  public editTableFields() {
    this._displayEditable = !this._displayEditable;
    this.child.toggleEdits('number');
    this.pageheader = {
      leftBtn: [{
        type: "back",
        callback: (): void => {
          this.backToData();
        }
      },
      {
        custom: true,
        icon: 'fa kd-header-row',
        tooltip: 'Report',
        callback: (): void => {
          this.getReport();
        }
      },
      {
        custom: true,
        icon: 'fa fa-file-pdf-o',
        tooltip: 'PDF',
        callback: (): void => {
          this.gePdftReport();
        }
      },
      {
        type: "export",
        callback: (): void => {
          this.exportSheet();
        }
      }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: this.pageHeaderTitle,
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
  }

  public backToData() {
    if (this._displayEditable) {
      this.child.toggleEdits('number');
      this._row.forEach((item) => {
        const indexInArray2 = this.findIndexInArray2(item.period1, item.id);
        const indexInArray3 = this.findIndexInArray3(item.period2, item.id);
        const indexInArray4 = this.findIndexInArray4(item.period3, item.id);
        if (indexInArray2 !== -1) {
          this.Rest.postWithToken
            (`institutions/students/assessment-item-results?student_id=${item.student_id}&assessment_id=${this.assessment_id}&education_subject_id=${item.education_subject_id}&assessment_grading_option_id=${this.gradingTypeOption_id}&education_grade_id=${this.education_grade_id}&academic_period_id=${this.academicPeriod_id}&assessment_period_id=${item?.assessment_period_id}&institution_id=${this.institution_id}&institution_classes_id=${this.class_Id}&marks=${item.period1}&action_type=default`, {}).subscribe({
              next: (res: any) => {
                if (res) {
                  this.displayLoading = false;
                  this._displayEditable = false;
                }
              },
              error: (error: any) => {
                if (error) {
                  if (error.message == "Token has expired") {
                    localStorage.removeItem("loginToken");
                    this.loginData();
                  }
                }
                this._displayEditable = false;
              }
            })
        }

        if (indexInArray3 !== -1) {
          this.Rest.postWithToken
            (`institutions/students/assessment-item-results?student_id=${item.student_id}&assessment_id=${this.assessment_id}&education_subject_id=${item.education_subject_id}&assessment_grading_option_id=${this.gradingTypeOption_id}&education_grade_id=${this.education_grade_id}&academic_period_id=${this.academicPeriod_id}&assessment_period_id=${item?.assessment_period_id2}&institution_id=${this.institution_id}&institution_classes_id=${this.class_Id}&marks=${item.period2}&action_type=default`, {}).subscribe({
              next: (res: any) => {
                if (res) {
                  this.displayLoading = false;
                  this._displayEditable = false;
                }
              },
              error: (error: any) => {
                if (error) {
                  if (error.message == "Token has expired") {
                    localStorage.removeItem("loginToken");
                    this.loginData();
                  }
                }
                this._displayEditable = false;
              }
            })
        }

        if (indexInArray4 !== -1) {
          this.Rest.postWithToken
            (`institutions/students/assessment-item-results?student_id=${item.student_id}&assessment_id=${this.assessment_id}&education_subject_id=${item.education_subject_id}&assessment_grading_option_id=${this.gradingTypeOption_id}&education_grade_id=${this.education_grade_id}&academic_period_id=${this.academicPeriod_id}&assessment_period_id=${item?.assessment_period_id}&institution_id=${this.institution_id}&institution_classes_id=${this.class_Id}&marks=${item.period3}&action_type=default`, {}).subscribe({
              next: (res: any) => {
                if (res) {
                  this.displayLoading = false;
                  this._displayEditable = false;
                }
              },
              error: (error: any) => {
                if (error) {
                  if (error.message == "Token has expired") {
                    localStorage.removeItem("loginToken");
                    this.loginData();
                  }
                }
                this._displayEditable = false;
              }
            })
        }
      });

      this.pageheader = {
        leftBtn: [{
          type: "back",
          callback: (): void => {
            this.backToData();
          }
        },
        {
          type: "edit",
          callback: (): void => {
            this.editTableFields();
          }
        },
        {
          custom: true,
          icon: 'fa kd-header-row',
          tooltip: 'Report',
          callback: (): void => {
            this.getReport();
          }
        },
        {
          custom: true,
          icon: 'fa fa-file-pdf-o',
          tooltip: 'PDF',
          callback: (): void => {
            this.gePdftReport();
          }
        },
        {
          type: "export",
          callback: (): void => {
            this.exportSheet();
          }
        }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: this.pageHeaderTitle,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
    }
  }

  findIndexInArray2(keyValue: any, id: any) {
    for (let i = 0; i < this.oldData.length; i++) {
      if (this.oldData[i].period1 != keyValue && this.oldData[i].id == id) {
        console.log(this.oldData[i].period1, keyValue, this.oldData[i].id, id);
        if ((this.oldData[i].meal_received_id == null && keyValue == 3) || (this.oldData[i].meal_received_id == 3 && keyValue == null)) {
          return -1;
        } else {
          return i;
        }
      }
    }
    return -1; // Return -1 if the value is not found
  }

  findIndexInArray3(keyValue: any, id: any) {
    for (let i = 0; i < this.oldData.length; i++) {
      if (this.oldData[i].period2 != keyValue && this.oldData[i].id == id) {
        console.log(this.oldData[i].period1, keyValue, this.oldData[i].id, id);
        if ((this.oldData[i].meal_received_id == null && keyValue == 3) || (this.oldData[i].meal_received_id == 3 && keyValue == null)) {
          return -1;
        } else {
          return i;
        }
      }
    }
    return -1; // Return -1 if the value is not found
  }

  findIndexInArray4(keyValue: any, id: any) {
    for (let i = 0; i < this.oldData.length; i++) {
      if (this.oldData[i].period3 != keyValue && this.oldData[i].id == id) {
        console.log(this.oldData[i].period1, keyValue, this.oldData[i].id, id);
        if ((this.oldData[i].meal_received_id == null && keyValue == 3) || (this.oldData[i].meal_received_id == 3 && keyValue == null)) {
          return -1;
        } else {
          return i;
        }
      }
    }
    return -1; // Return -1 if the value is not found
  }


  _selectTabs(event: any) {
    this.pageheader = {
      leftBtn: [{
        type: "back",
        callback: (): void => {
          this.backToData();
        }
      },
      {
        type: "edit",
        callback: (): void => {
          this.editTableFields();
        }
      },
      {
        custom: true,
        icon: 'fa kd-header-row',
        tooltip: 'Report',
        callback: (): void => {
          this.getReport();
        }
      },
      {
        custom: true,
        icon: 'fa fa-file-pdf-o',
        tooltip: 'PDF',
        callback: (): void => {
          this.gePdftReport();
        }
      },
      {
        type: "export",
        callback: (): void => {
          this.exportSheet();
        }
      }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: this.pageHeaderTitle,
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
    this._state = event;
    this._displayEditable = false;
    this._row = [];
    this.displayLoading = true;
    this.institutionsSubject(event);
  }

  changeTermData(data: any) {
    if (data.target.value == "Term 2") {
      this._column = [];
      this.assessmentAcademicPeriod('Term 2');
      timer(100).subscribe((): void => {
        this._column = [
          TABLE_COLUMN_LIST.id,
          TABLE_COLUMN_LIST.name,
          TABLE_COLUMN_LIST.status,
          TABLE_COLUMN_LIST.period3,
          TABLE_COLUMN_LIST.totalMark
        ];
      });
    } else {
      this._column = [];
      this.assessmentAcademicPeriod('Term 1');
      timer(100).subscribe((): void => {
        this._column = [
          TABLE_COLUMN_LIST.id,
          TABLE_COLUMN_LIST.name,
          TABLE_COLUMN_LIST.status,
          TABLE_COLUMN_LIST.period1,
          TABLE_COLUMN_LIST.period2,
          TABLE_COLUMN_LIST.totalMark
        ];
      });
    }
    this.institutionsSubject(this.tabsListHtml[0].tabId);
  }

  ngOnDestroy(): void {
    super.destroyPageBaseSub();
  }
}
