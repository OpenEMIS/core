import { Component, OnInit } from '@angular/core';
import { IWizardConfig } from '../add-directory/add-directory.config';
import { IDynamicFormApi, ITreeConfig, IWizardApi, KdPageBase, KdPageBaseEvent, KdToolbarEvent, KdWizardEvent } from 'openemis-styleguide-lib';
import { Subscription } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../api.service';
import { WIZARD_TEXT } from './add-student.config';
import { element } from 'protractor';

@Component({
  selector: 'app-add-student',
  templateUrl: './add-student.component.html',
  styleUrls: ['./add-student.component.css']
})
export class AddStudentComponent extends KdPageBase implements OnInit {
  public displayLoading: boolean = true;

  public breadcrumbList: any;

  public pageheader: any;

  public wizardConfig: IWizardConfig = WIZARD_TEXT;
  public wizardType: string = 'html';
  public wizardApi: IWizardApi = {};
  public _step: number = 1;
  public wizardId: string = 'wizard';
  private _wizardActionSub: Subscription;
  private _wizardLastStepSub: Subscription;
  _status: any;
  _nextStatusSub: Subscription;

  public api: IDynamicFormApi = {};
  public _formButtons: Array<any> = [];
  public maxDate = new Date();
  public _questionBase: Array<any> = [
    { key: 'SearchByIdentity', label: 'Search By Identity', visible: true, controlType: 'section' },
    {
      key: 'nationality',
      label: 'Nationality',
      visible: true,
      required: false,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'identity_type',
      label: 'Identity Type',
      visible: true,
      required: false,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'identity_number',
      label: 'Identity Number',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    { key: 'SearchByBasicInformation', label: 'Search By Basic Information', visible: true, controlType: 'section' },
    {
      key: 'openEMIS_id',
      label: 'OpenEMIS ID',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'first_name',
      label: 'First Name',
      visible: true,
      required: true,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'middle_name',
      label: 'Middle Name',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'third_name',
      label: 'Third Name',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'last_name',
      label: 'Last Name',
      visible: true,
      required: true,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'preferred_name',
      label: 'Preferred Name',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'gender',
      label: 'Gender',
      visible: true,
      required: true,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'date_of_birth',
      label: 'Date Of Birth',
      visible: true,
      required: true,
      controlType: 'date',
      minDate: "1970-01-01",
      maxDate: `${this.maxDate.getFullYear()}-${this.maxDate.getMonth() + 1
        }-${this.maxDate.getDate()}`,
    },
  ]

  public TREE_CONFIG_SINGLE: ITreeConfig = {
    id: 'configSingle',
    list: [
      {
        data: { id: 'endor', name: 'Endor - Country' }, children: [
          {
            data: { id: 'northRegion', name: 'North - Region' }, children: [
              { data: { id: 'district1', name: 'District 1 - District' } },
              { data: { id: 'district2', name: 'District 2 - District' } },
              { data: { id: 'district3', name: 'District 3 - District' } },
              { data: { id: 'district4', name: 'District 4 - District' } },
              { data: { id: 'district5', name: 'District 5 - District' } },
            ]
          },
          {
            data: { id: 'southRegion', name: 'South - Region' }, children: [
              { data: { id: 'district6', name: 'District 6 - District' } },
              { data: { id: 'district7', name: 'District 7 - District' } },
              { data: { id: 'district8', name: 'District 8 - District' } },
              { data: { id: 'district9', name: 'District 9 - District' } },
              { data: { id: 'district10', name: 'District 10 - District' } },
            ]
          },
        ]
      }
    ],
    selectionMode: 'single'
  };
  public BIRTHPLACE: ITreeConfig = {
    id: 'configSingle',
    list: [
      {
        data: { id: 'endor', name: 'Endor - Country' }, children: [
          {
            data: { id: 'northRegion', name: 'North - Region' }, children: [
              { data: { id: 'district1', name: 'District 1 - District' } },
              { data: { id: 'district2', name: 'District 2 - District' } },
              { data: { id: 'district3', name: 'District 3 - District' } },
              { data: { id: 'district4', name: 'District 4 - District' } },
              { data: { id: 'district5', name: 'District 5 - District' } },
            ]
          },
          {
            data: { id: 'southRegion', name: 'South - Region' }, children: [
              { data: { id: 'district6', name: 'District 6 - District' } },
              { data: { id: 'district7', name: 'District 7 - District' } },
              { data: { id: 'district8', name: 'District 8 - District' } },
              { data: { id: 'district9', name: 'District 9 - District' } },
              { data: { id: 'district10', name: 'District 10 - District' } },
            ]
          },
        ]
      }
    ],
    selectionMode: 'single'
  };
  public _confirmationQuestionBase: Array<any> = [
    { key: 'information', label: 'Information', visible: true, controlType: 'section' },
    {
      key: 'image',
      label: 'Photo Content',
      visible: true,
      required: false,
      controlType: 'file-input',
      type: 'file',
      config: {
        preview: true,
        fileType: 'image',
        infoText: [
          {
            'text': 'Only .jpg and .png files are allowed.'
          }
        ],
        imageConfig: {
          type: 'student',
          height: '150',
          width: '150',
          isExpandable: true,
        }
      },
      events: true,
    },
    {
      key: 'openEMIS_id',
      label: 'OpenEMIS ID',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
      value: '',
      readonly: true
    },
    {
      key: 'first_name',
      label: 'First Name',
      visible: true,
      required: true,
      order: 1,
      controlType: 'text',
      readonly: true
    },
    {
      key: 'middle_name',
      label: 'Middle Name',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'third_name',
      label: 'Third Name',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'last_name',
      label: 'Last Name',
      visible: true,
      required: true,
      order: 1,
      controlType: 'text',
      readonly: true
    },
    {
      key: 'preferred_name',
      label: 'Preferred Name',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
      readonly: true
    },
    {
      key: 'gender',
      label: 'Gender',
      visible: true,
      required: true,
      value: 1,
      readonly: true,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'date_of_birth',
      label: 'Date Of Birth',
      visible: true,
      required: false,
      controlType: 'text',
      readonly: true
    },
    { key: 'location', label: 'Location', visible: true, controlType: 'section' },
    {
      key: 'address',
      label: 'Address',
      visible: true,
      required: false,
      controlType: 'textarea',
    },
    {
      key: 'postal_code',
      label: 'Postal Code',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text'
    },
    { key: 'address_area', label: 'Address Area', visible: true, controlType: 'section' },
    {
      key: 'addressArea',
      label: 'Address Area',
      visible: true,
      required: false,
      controlType: 'tree',
      config: this.TREE_CONFIG_SINGLE
    },
    { key: 'birthplace_area', label: 'Birthplace Area', visible: true, controlType: 'section' },
    {
      key: 'birthPlaceArea',
      label: 'Birthplace Area',
      visible: true,
      required: false,
      controlType: 'tree',
      config: this.BIRTHPLACE
    },
    { key: 'other_information', label: 'Other Information', visible: true, controlType: 'section' },
    {
      key: 'contact_type',
      label: 'Contact Type',
      visible: true,
      required: false,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'contact_value',
      label: 'Contact Value',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'nationality',
      label: 'Nationality',
      visible: true,
      required: false,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'identity_type',
      label: 'Identity Type',
      visible: true,
      required: false,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'identity_number',
      label: 'Identity Number',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
    }
  ]

  public _addStudentQuestionBase: Array<any> = [
    { key: 'informations', label: 'Informations', visible: true, controlType: 'section' },
    {
      key: 'openEMIS_id',
      label: 'OpenEMIS ID',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
      value: '',
      readonly: true
    },
    {
      key: 'Student',
      label: 'Student',
      visible: true,
      required: false,
      order: 1,
      controlType: 'text',
      readonly: true
    },
    {
      key: 'identity_number',
      label: 'Identity Number',
      visible: true,
      required: false,
      readonly: true,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'date_of_birth',
      label: 'Date Of Birth',
      visible: true,
      required: false,
      controlType: 'text',
      value: '',
      readonly: true
    },
    {
      key: 'gender',
      label: 'Gender',
      visible: true,
      required: false,
      value: 1,
      readonly: true,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
    {
      key: 'user_name',
      label: 'User Name',
      visible: true,
      required: true,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'password',
      label: 'Password',
      visible: true,
      required: true,
      order: 1,
      controlType: 'text',
    },
    {
      key: 'student_status',
      label: 'Student Status',
      visible: true,
      required: true,
      readonly: true,
      value: 'Pending',
      order: 1,
      controlType: 'text',
    },
    {
      key: 'academic_period',
      label: 'Academic Period',
      visible: true,
      required: true,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '31',
          value: '2024'
        },
        {
          key: '32',
          value: '2023'
        },
        {
          key: '33',
          value: '2022'
        },
        {
          key: '34',
          value: '2021'
        }
      ],
      events: true,
    },
    {
      key: 'education_grade',
      label: 'Education Grade',
      visible: true,
      required: true,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '59',
          value: 'yes'
        }
      ],
      events: true,
    },
    {
      key: 'class',
      label: 'Class',
      visible: true,
      required: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '524',
          value: 'yes'
        }
      ],
      events: true,
    },
    {
      key: 'start_date',
      label: 'Start Date',
      visible: true,
      required: true,
      controlType: 'date',
      minDate: "1970-01-01",
      maxDate: `${this.maxDate.getFullYear()}-${this.maxDate.getMonth() + 1
        }-${this.maxDate.getDate()}`,
    },
    {
      key: 'end_date',
      label: 'End Date',
      visible: true,
      required: false,
      controlType: 'text',
      readonly: true,
      value: '2024-12-31',
    },

  ]

  public _viewQuestion: any = [
    { key: 'information', label: 'Information', visible: true, controlType: 'section' },
    {
      'key': 'profile_photo',
      'label': 'Photo Content',
      'visible': true,
      'required': false,
      'controlType': 'file-input',
      'config': {
        'preview': true,
        'fileType': 'image',
        'leftToolbar': false,
        'imageConfig': {
          'type': 'student',
          'height': '150',
          'width': '150',
          'isExpandable': false,
          'title': 'Tony_chan.jpg'
        },
      },
      'value': {
        'data': 'Thisisthetestdataoftonychanwithnametongchanjpg',
        'extension': 'jpg',
        'filename': 'Tony_chan.jpg',
        'src': ''
      }
    },
    {
      'value': '',
      'key': 'openemis_no',
      'visible': true,
      'label': 'Openemis No.',
      'type': 'string'
    },
    {
      'key': 'first_name',
      'label': 'First Name',
      'visible': true,
      'value': '',
    },
    {
      'key': 'middle_name',
      'label': 'Middle Name',
      'visible': true,
      'controlType': 'text',
      'value': '',
    },
    {
      'key': 'third_name',
      'label': 'Third Name',
      'visible': true,
      'controlType': 'text',
      'type': 'string',
      'value': '',
    },
    {
      'key': 'last_name',
      'label': 'Last Name',
      'visible': true,
      'controlType': 'text',
      'value': '',
    },
    {
      'key': 'preferred_name',
      'label': 'Preferred Name',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    {
      'key': 'gender',
      'label': 'Gender',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    {
      'key': 'dob',
      'label': 'Date of Birth',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    {
      'key': 'email',
      'label': 'Email',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'identities_nationalities', label: 'Identities / Nationalities', visible: true, controlType: 'section' },
    {
      'key': 'details',
      'label': 'Details',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'location', label: 'Location', visible: true, controlType: 'section' },
    {
      'key': 'address',
      'label': 'Address',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    {
      'key': 'postal_code',
      'label': 'Postal Code',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'address_area', label: 'Address Area', visible: true, controlType: 'section' },
    {
      'key': 'addressArea',
      'label': 'Address Area',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'birthplace_area', label: 'Birthplace Area', visible: true, controlType: 'section' },
    {
      'key': 'birthplaceArea',
      'label': 'Birthplace Area',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'other_information', label: 'Other Information', visible: true, controlType: 'section' },
    {
      'key': 'modified_by',
      'label': 'Modified By',
      'visible': true,
      'controlType': 'text',
      'value': 'System Administrator'
    },
    {
      'key': 'modified_on',
      'label': 'Modified On',
      'visible': true,
      'controlType': 'text',
      'value': '2024-01-11 10:06:16'
    },
    {
      'key': 'created_by',
      'label': 'Created By',
      'visible': true,
      'controlType': 'text',
      'value': 'System Administrator'
    },
    {
      'key': 'created_on',
      'label': 'Created On',
      'visible': true,
      'controlType': 'text',
      'value': '2024-01-11 10:06:16'
    },
  ]
  firstStepValue: any = [];
  firstStepData: any;
  counter: number = 0;
  selectedFirstStepValue: any;
  addStudentData: any = [];
  _confirmationData: any[] = [];
  confirmationStepValue: any = [];
  internalSearchData: any;
  selectedInternalSearchData: any;
  customFieldData: any = [];
  institution_id: any;
  institution_name: any;
  login_user_id: any;

  constructor(
    private _router: Router,
    _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private Rest: ApiService,
    private _toolbarEvent: KdToolbarEvent,
    private _kdWizardEvent: KdWizardEvent
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: pageEvent,
    });
  }

  ngOnInit(): void {
    this.displayLoading = false;
    super.updatePageHeader();
    super.updateBreadcrumb();

    this.getLocalStorageData();

    this._kdWizardEvent.onActionClicked(this.wizardId).subscribe((_event: any): void => {
      console.log(_event?.step?.content, "_event?.step?.content");

      if (_event.action == "previous") {
        if (_event.step.label == 'Internal Search') {
          this.firstStepData = localStorage.getItem("student_first_step");
          if (this.confirmationStepValue.length > 0) {
            this.firstStepData = this.confirmationStepValue;
            this.firstStepValue = this.confirmationStepValue;
          } else {
            this.firstStepData = JSON.parse(this.firstStepData);
          }
          let questionBase = this._questionBase;
          questionBase.forEach((element: any, index: any) => {
            const indexInArray = this.findIndex2(element?.key);
            if (indexInArray != undefined) {
              if (questionBase[index].key == "date_of_birth") {
                questionBase[index].value = this.firstStepData[indexInArray].value.obj;
              } else {
                questionBase[index].value = this.firstStepData[indexInArray].value;
              }
            }
          })
          this._questionBase = [...questionBase]
          this.wizardApi.updateSteps(_event.action);
        } else if (_event.step.content == 'add-student') {
          if (this.confirmationStepValue.length > 0) {
            let confimStudent = this._confirmationQuestionBase;
            confimStudent.forEach((element: any, index: any) => {
              const indexArray = this.findIndexData(element.key);
              if (indexArray != undefined) {
                if (confimStudent[index].key == "date_of_birth") {
                  confimStudent[index].value = this.confirmationStepValue[indexArray].value.text;
                } else {
                  confimStudent[index].value = this.confirmationStepValue[indexArray].value;
                }
              }
            });
            this._confirmationQuestionBase = [...confimStudent];
          }
          this.wizardApi.updateSteps(_event.action);
        } else {
          this.wizardApi.updateSteps(_event.action);
        }
      }
      if (_event?.step?.label == "User Details") {
        let firstValidation = this.firstStepValidation();
        if (firstValidation == undefined) {
          this.wizardApi.updateSteps(_event.action);
          this.checkInternalSearch();
          localStorage.setItem("student_first_step", JSON.stringify(this.firstStepValue));
        }
      } else if (_event?.step?.content == "internal-search" && _event.action != "previous") {
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "external-search" && _event.action != "previous") {
        this.confirmationApi();
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "confirmation" && _event.action != "previous") {
        this.addStudentApi();
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "add-student" && _event.action != "previous") {
        let studentValidation = this.addStudentValidation();
        if (studentValidation == undefined) {
          this.wizardApi.updateSteps(_event.action);
          this.wizardApi.disableButton('previous');
          this.saveStudentData();
        }
      }
    });
    this._kdWizardEvent.onNextClickedWhenLastStep(this.wizardId).subscribe((_event: any): void => {
      console.log('clicked next from last step', _event);
    });

    this._nextStatusSub = this.Rest.nextBtnEvent.subscribe((_status: any) => {
      this._status = _status;
      if (this._status == 'disable') {
        this.disableNextButton();
      } else if (this._status == 'enable') {
        this.enableNextButton();
      }
    })

    this.loginData();
  }

  getLocalStorageData() {
    this.institution_id = localStorage.getItem('institution_id');
    this.institution_name = localStorage.getItem('institution_name');
    this.login_user_id = localStorage.getItem('login_user_id');

    console.log(this.institution_id, this.institution_name, this.login_user_id, '--test--');
    if (this.institution_name) {
      let newBreadCrumb: any = this.breadcrumbList;

      newBreadCrumb = {
        home: { icon: 'fa fa-home', path: '' },
        list: [
          {
            name: 'Institutions',
            path: '',
          },
          {
            name: this.institution_name,
            path: '',
          }
        ]
      }
      this.breadcrumbList = newBreadCrumb;

      let newPageheader = this.pageheader
      newPageheader = {
        moreAction: [],
        moreBtn: false,
        pageheaderText: this.institution_name + ' - Staff',
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
      this.pageheader = newPageheader;
    }
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
      this.getUserType();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.getUserType();
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

  getUserType() {
    this.Rest.getWithToken('nationalities').subscribe({
      next: (res: any) => {
        if (res) {
          let question = this._questionBase;
          let confirmation = this._confirmationQuestionBase;
          question[1].options = [];
          confirmation[20].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[1].options.push(obj);
            confirmation[20].options.push(obj);
          });
          question[1].options.unshift({ key: null, value: '--Select--' });
          confirmation[20].options.unshift({ key: null, value: '--Select--' });
          this._questionBase = [...question];
          this._confirmationQuestionBase = [...confirmation];
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
    });

    this.Rest.getWithToken('identity-types/list').subscribe({
      next: (res: any) => {
        if (res) {
          let question = this._questionBase;
          let confirmation = this._confirmationQuestionBase;
          question[2].options = [];
          confirmation[21].options = [];
          res?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[2].options.push(obj);
            confirmation[21].options.push(obj);
          });
          question[2].options.unshift({ key: null, value: '--Select--' });
          confirmation[21].options.unshift({ key: null, value: '--Select--' });
          this._questionBase = [...question];
          this._confirmationQuestionBase = [...confirmation];
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

    this.Rest.getWithToken('users/genders').subscribe({
      next: (res: any) => {
        if (res) {
          let question = this._questionBase;
          let confirmation = this._confirmationQuestionBase;
          let studentQuestion = this._addStudentQuestionBase
          question[11].options = [];
          confirmation[8].options = [];
          studentQuestion[5].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[11].options.push(obj);
            confirmation[8].options.push(obj);
            studentQuestion[5].options.push(obj);
          });
          question[11].options.unshift({ key: null, value: '--Select--' });
          confirmation[8].options.unshift({ key: null, value: '--Select--' });
          studentQuestion[5].options.unshift({ key: null, value: '--Select--' });
          this._questionBase = [...question];
          this._confirmationQuestionBase = [...confirmation];
          this._addStudentQuestionBase = [...studentQuestion];
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
    });

  }

  confirmationApi() {
    console.log(this.selectedFirstStepValue, "this.selectedFirstStepValue");

    let confirmationQuestion = this._confirmationQuestionBase;
    confirmationQuestion[3].value = this.selectedFirstStepValue?.first_name;
    confirmationQuestion[4].value = this.selectedFirstStepValue?.middle_name;
    confirmationQuestion[5].value = this.selectedFirstStepValue?.third_name;
    confirmationQuestion[6].value = this.selectedFirstStepValue?.last_name;
    confirmationQuestion[7].value = this.selectedFirstStepValue?.preferred_name;
    confirmationQuestion[8].value = this.selectedFirstStepValue?.gender;
    confirmationQuestion[9].value = this.selectedFirstStepValue?.date_of_birth?.text;
    confirmationQuestion[20].value = this.selectedFirstStepValue?.nationality;
    this._confirmationQuestionBase = [...confirmationQuestion];
    if (this.selectedFirstStepValue?.nationality) {
      confirmationQuestion[21].value = this.selectedFirstStepValue?.identity_type ? this.selectedFirstStepValue?.identity_type : 161;
    }
    confirmationQuestion[22].value = this.selectedFirstStepValue?.identity_number;

    for (const property in this.selectedFirstStepValue) {
      if (property == 'date_of_birth') {
        let obj = {
          key: property,
          value: {
            text: this.selectedFirstStepValue[property]?.obj?.year + '-' + this.selectedFirstStepValue[property]?.obj?.month + '-' + this.selectedFirstStepValue[property]?.obj?.day,
            obj: this.selectedFirstStepValue[property]?.obj
          }
        }
        this.confirmationStepValue.push(obj);
      } else {
        let obj = {
          key: property,
          value: this.selectedFirstStepValue[property]
        }
        this.confirmationStepValue.push(obj);
      }
    }
    if (this.selectedInternalSearchData) {
      this.internalSearchData?.data?.data.forEach((element: any) => {
        if (element?.openemis_no == this.selectedInternalSearchData) {
          let confirmation = this._confirmationQuestionBase;
          confirmation[2].value = element?.openemis_no;
          this._confirmationQuestionBase = [...confirmation];
        }
      });
    } else {
      this.Rest.getWithToken('users/generate-openemis-id').subscribe({
        next: (res: any) => {
          if (res) {
            let confirmation = this._confirmationQuestionBase;
            confirmation[2].value = res?.data?.openemis_no;
            this._confirmationQuestionBase = [...confirmation];
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
      });
    }

    this.Rest.getWithToken('contact-types').subscribe({
      next: (res: any) => {
        if (res) {
          let confirmation = this._confirmationQuestionBase;
          confirmation[18].options = [];
          res?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            confirmation[18].options.push(obj);
          });
          confirmation[18].options.unshift({ key: null, value: '--Select--' });
          this._confirmationQuestionBase = [...confirmation];
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
    });
  }

  getTriggerInput(event: any) {
    this.selectedInternalSearchData = event;
  }


  firstStepValidation() {
    if (this.firstStepValue.length == 0) {
      setTimeout(() => {
        this.api.setProperty(this._questionBase[6].key, "errors", [
          "This field is required"
        ]);
      }, 100);
      return true;
    } else {
      for (let i = 0; i < this._questionBase.length; i++) {
        if (this._questionBase[i].required) {
          for (let j = 0; j <= this.firstStepValue.length - 1; j++) {
            let checkData = this.firstStepValue.findIndex((obj => obj.key == this._questionBase[i].key));
            if (checkData == -1) {
              setTimeout(() => {
                this.api.setProperty(this._questionBase[i].key, "errors", [
                  "This field is required"
                ]);
              }, 100);
              return true;
            } else {
              if (this.firstStepValue[checkData].key == "date_of_birth" && this.firstStepValue[checkData].value.text == "undefined-undefined-undefined") {
                setTimeout(() => {
                  this.api.setProperty(this._questionBase[i].key, "errors", [
                    "This field is required"
                  ]);
                }, 100);
                return true;
              }
              if (this.firstStepValue[checkData].value == '' || this.firstStepValue[checkData].value == 'null') {
                setTimeout(() => {
                  this.api.setProperty(this._questionBase[i].key, "errors", [
                    "This field is required"
                  ]);
                }, 100);
                return true;
              }
            }
          }
        }

      }
    }
  }

  addStudentValidation() {
    if (this.addStudentData.length == 0) {
      setTimeout(() => {
        this.api.setProperty(this._confirmationData[9].key, "errors", [
          "This field is required"
        ]);
      }, 100);
      return true;
    } else {
      for (let i = 0; i < this._confirmationData.length; i++) {
        if (this._confirmationData[i].required) {
          for (let j = 0; j <= this.addStudentData.length - 1; j++) {
            let checkData = this.addStudentData.findIndex((obj => obj.key == this._confirmationData[i].key));
            if (checkData == -1) {
              setTimeout(() => {
                this.api.setProperty(this._confirmationData[i].key, "errors", [
                  "This field is required"
                ]);
              }, 100);
              return true;
            }
          }
        }

      }
    }
  }

  checkInternalSearch() {
    this.selectedFirstStepValue = {};
    this.firstStepValue.forEach((element: any) => {
      this.selectedFirstStepValue[element.key] = element.value;
    });

    let nationalityName = '';
    let identityName = '';
    this._questionBase[1].options.forEach((element: any) => {
      if (element.key == this.selectedFirstStepValue?.nationality) {
        if (element.value == "--Select--") {
          nationalityName = '';
        } else {
          nationalityName = element.value;
        }
      }
    })
    this._questionBase[2].options.forEach((element: any) => {
      if (element.key == this.selectedFirstStepValue?.identity_type) {
        if (element.value == "--Select--") {
          identityName = '';
        } else {
          identityName = element.value;
        }
      }
    })
    let obj = {
      "page": 1,
      "limit": 10,
      "first_name": this.selectedFirstStepValue?.first_name,
      "last_name": this.selectedFirstStepValue?.last_name,
      "date_of_birth": this.selectedFirstStepValue.date_of_birth.text,
      "identity_number": this.selectedFirstStepValue.identity_number ? this.selectedFirstStepValue.identity_number : '',
      "institution_id": this.institution_id,
      "user_type_id": "1",
      "nationality_id": this.selectedFirstStepValue?.nationality,
      "nationality_name": nationalityName ? nationalityName : '',
      "identity_type_name": identityName,
      "identity_type_id": this.selectedFirstStepValue?.identity_type,
      "gender_id": this.selectedFirstStepValue?.gender
    }
    console.log(obj, "obj");

    this.Rest.postWithToken('users/basic-information', obj).subscribe({
      next: (res: any) => {
        if (res) {
          this.internalSearchData = res;
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
    });

  }

  detectValue(event: any) {
    if (event.key == "nationality") {
      let questionBase = this._questionBase;
      questionBase[1].value = event?.value;
      questionBase[2].value = 161;
      questionBase[3].label = 'School';
      if (this.firstStepValue.length > 0) {
        for (let i = 0; i < this.firstStepValue.length; i++) {
          questionBase.forEach((element: any) => {
            if (element.key == this.firstStepValue[i].key) {
              if (this.firstStepValue[i].key == "date_of_birth") {
                element.value = this.firstStepValue[i].value.obj;
              } else {
                element.value = this.firstStepValue[i].value;
              }
            }
          })
        }
      }
      let eventData = {
        key: "identity_type",
        value: 161
      }
      this.firstStepValue.push(eventData);
      this._questionBase = [...questionBase];
    }

    if (event.key == "identity_type") {
      let questionBase = this._questionBase;
      questionBase[2].options.forEach((element: any) => {
        if (element.key == event.value) {
          questionBase[3].label = element.value
        }
      });
      if (this.firstStepValue.length > 0) {
        for (let i = 0; i < this.firstStepValue.length; i++) {
          questionBase.forEach((element: any) => {
            if (element.key == this.firstStepValue[i].key) {
              if (this.firstStepValue[i].key == "date_of_birth") {
                element.value = this.firstStepValue[i].value.obj;
              } else {
                element.value = this.firstStepValue[i].value;
              }
            }
          })
        }
      }
      questionBase[2].value = event.value;
      this._questionBase = [...questionBase];
    }
    let eventData = {
      key: event?.key,
      value: event?.value
    }

    if (this.firstStepValue.length == 0) {
      this.firstStepValue.push(eventData);
    } else {
      let getIndex = this.firstStepValue.findIndex((obj => obj.key == eventData.key));
      if (getIndex == -1) {
        this.firstStepValue.push(eventData);
      } else {
        this.firstStepValue.splice(getIndex, 1, eventData);
      }
    }
    this.selectedFirstStepValue = {};
    this.firstStepValue.forEach((element: any) => {
      this.selectedFirstStepValue[element.key] = element.value;
    });
  }

  addStudentApi() {
    this.addStudentData.push({
      key: 'student_status',
      value: 'Pending'
    })
    this.addStudentData.push({
      key: 'user_name',
      value: this._confirmationQuestionBase[2].value
    })
    this.addStudentData.push({
      key: 'first_name',
      value: this._confirmationQuestionBase[3].value
    })
    this.addStudentData.push({
      key: 'last_name',
      value: this._confirmationQuestionBase[6].value
    })
    let confirmation = this._confirmationQuestionBase;
    confirmation.forEach((element: any) => {
      let staffQuestion = this._addStudentQuestionBase;
      for (let i = 0; i < this._addStudentQuestionBase.length; i++) {
        if (element.key == this._addStudentQuestionBase[i].key) {
          staffQuestion[i].value = element.value;
          let eventData = {
            key: element?.key,
            value: element?.value
          }
          this.addStudentData.push(eventData);
        }
      }

      this._addStudentQuestionBase = [...staffQuestion];
    })

    let studentQuestion = this._addStudentQuestionBase;
    studentQuestion[2].value = confirmation[3].value + ' ' + confirmation[6].value;
    studentQuestion[6].value = confirmation[2].value;
    this._addStudentQuestionBase = [...studentQuestion];

    this.Rest.getWithToken('users/generate-password').subscribe({
      next: (res: any) => {
        if (res) {
          let staffQuestion = this._addStudentQuestionBase;
          staffQuestion[7].value = res?.data?.password;
          this._addStudentQuestionBase = [...staffQuestion];
          this.addStudentData.push({
            key: 'password',
            value: res?.data?.password
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
    });

    this.Rest.getWithToken('student-custom-fields').subscribe({
      next: (res: any) => {
        console.log(res, "res");
        let newKey = '';
        for (let key in res?.data) {
          newKey = key;
          if (res?.data[newKey].length > 0) {
            this.customFieldData = [];
            this.customFieldData.push({
              controlType: "section",
              key: newKey,
              label: newKey,
              visible: true
            });
            res?.data[newKey].forEach((element: any) => {
              let obj = {}
              if (element.field_type == 'TEXT') {
                obj = {
                  key: element.name,
                  label: element.name,
                  visible: true,
                  required: element.is_mandatory == 0 ? false : true,
                  order: element.order,
                  controlType: 'text',
                  value: "",
                  fieldType: 'custom',
                  student_custom_field_id: element.student_custom_field_id
                }
              } else if (element.field_type == 'DROPDOWN') {
                obj = {
                  key: element.name,
                  label: element.name,
                  visible: true,
                  required: element.is_mandatory == 0 ? false : true,
                  order: element.order,
                  controlType: 'dropdown',
                  value: "",
                  fieldType: 'custom',
                  options: element?.option,
                  student_custom_field_id: element.student_custom_field_id
                }
              }
              this.customFieldData.push(obj);
            });

            this._confirmationData = [...this._addStudentQuestionBase, ...this.customFieldData];
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
    });
  }

  findIndex2(data: any) {
    for (let i = 0; i < this.firstStepData.length; i++) {
      if (this.firstStepData[i].key == data) {
        return i
      }
    }
  }

  findIndexData(data: any) {
    for (let i = 0; i < this.confirmationStepValue.length; i++) {
      if (this.confirmationStepValue[i].key == data) {
        return i
      }
    }
  }

  private readBase64(file: any): Promise<any> {
    const reader = new FileReader();
    const future = new Promise((resolve, reject) => {
      reader.addEventListener('load', function () {
        resolve(reader.result);
      }, false);
      reader.addEventListener('error', function (event) {
        reject(event);
      }, false);

      reader.readAsDataURL(file);
    });
    return future;
  }

  detectConfirmationValue(event: any) {
    if (event.key == "image") {
      this.readBase64(event.value).then((data) => {
        this._viewQuestion[1].value.src = data;
        let eventData = {
          key: event?.key,
          value: data
        }
        let getIndex = this.confirmationStepValue.findIndex((obj => obj.key == eventData.key));
        if (getIndex == -1) {
          this.confirmationStepValue.push(eventData);
        } else {
          this.confirmationStepValue.splice(getIndex, 1, eventData);
        }
      });
    }
    let eventData = {
      key: event?.key,
      value: event?.value
    }

    if (this.confirmationStepValue.length == 0) {
      this.confirmationStepValue.push(eventData);
    } else {
      let getIndex = this.confirmationStepValue.findIndex((obj => obj.key == eventData.key));
      if (getIndex == -1) {
        this.confirmationStepValue.push(eventData);
      } else {
        this.confirmationStepValue.splice(getIndex, 1, eventData);
      }
    }
  }

  addStudentValue(event: any) {
    let eventData = {
      key: event?.key,
      value: event?.value,
    }
    if (this.addStudentData.length == 0) {
      this._confirmationData.forEach((element: any) => {
        let count = 0;
        if (element?.key == eventData?.key && element?.fieldType == 'custom') {
          eventData['fieldType'] = 'custom';
          this.addStudentData.push(eventData);
        } else {
          if (count == 0) {
            this.addStudentData.push(eventData);
            count = count + 1;
          }
        }
      })
    } else {
      let getIndex = this.addStudentData.findIndex((obj => obj.key == eventData.key));
      if (getIndex == -1) {
        let count = 0;
        this._confirmationData.forEach((element: any) => {
          if (element?.key == eventData?.key && element?.fieldType == 'custom') {
            if (count === 0) {
              eventData['fieldType'] = 'custom';
              this.addStudentData.push(eventData);
              count = count + 1;
            }
          } else {
            if (count === 0) {
              this.addStudentData.push(eventData);
              count = count + 1;
            }
          }
        })
      } else {
        this._confirmationData.forEach((element: any) => {
          if (element?.key == eventData?.key && element?.fieldType == 'custom') {
            eventData['fieldType'] = 'custom';
            this.addStudentData.splice(getIndex, 1, eventData);
          } else {
            this.addStudentData.splice(getIndex, 1, eventData);
          }
        })
      }
    }
  }

  saveStudentData() {
    let studentObj: any = {
      custom: []
    }
    console.log(this.addStudentData, "this.addStudentData");
    console.log(this.confirmationStepValue, "confirmationStepValue");
    this.confirmationStepValue.forEach((element: any) => {
      studentObj[element.key] = element.value;
    });
    this.addStudentData.forEach((element: any, index: any) => {
      if (element?.key == "date_of_birth" || element?.key == "start_date" || element?.key == "end_date") {
        studentObj[element.key] = element?.value;
      } else if (element?.fieldType == 'custom') {
        let checkData = this.customFieldData.findIndex((obj => obj.key == element.key));
        if (checkData) {
          this.customFieldData[checkData]['text_value'] = element?.value;
        }
      } else {
        studentObj[element.key] = element.value;
      }
    });
    this.customFieldData.forEach((element: any, index: any) => {
      if (index != 0) {
        let customObj = {
          "student_custom_field_id": element?.student_custom_field_id,
          "text_value": element?.text_value ? element?.text_value : '',
          "number_value": "",
          "decimal_value": "",
          "textarea_value": "",
          "time_value": "",
          "file": "",
          "created_user_id": 1,
          "created": "22-01-20 08:59:35"
        }
        studentObj.custom.push(customObj);
      }
    });
    let nationalityName = '';
    let identityName = '';
    this._questionBase[1].options.forEach((element: any) => {
      if (element.key == this.selectedFirstStepValue?.nationality) {
        if (element.value == "--Select--") {
          nationalityName = '';
        } else {
          nationalityName = element.value;
        }
      }
    })
    this._questionBase[2].options.forEach((element: any) => {
      if (element.key == this.selectedFirstStepValue?.identity_type) {
        if (element.value == "--Select--") {
          identityName = '';
        } else {
          identityName = element.value;
        }
      }
    })

    let studentParams = {
      "institution_id": this.institution_id,
      "openemis_no": studentObj?.openEMIS_id,
      "first_name": studentObj?.first_name,
      "middle_name": studentObj?.middle_name ? studentObj?.middle_name : '',
      "third_name": studentObj?.third_name ? studentObj?.third_name : '',
      "last_name": studentObj?.last_name,
      "preferred_name": studentObj?.preferred_name ? studentObj?.preferred_name : '',
      "gender_id": studentObj?.gender,
      "date_of_birth": studentObj?.date_of_birth,
      "identity_number": studentObj?.identity_number ? studentObj?.identity_number : '',
      "nationality_id": studentObj?.nationality ? studentObj?.nationality : '',
      "nationality_name": nationalityName,
      "username": studentObj?.user_name,
      "password": studentObj?.password,
      "postal_code": studentObj?.postal_code ? studentObj?.postal_code : '',
      "address": studentObj?.address ? studentObj?.address : '',
      "birthplace_area_id": "2",
      "address_area_id": "2",
      "identity_type_id": studentObj?.identity_type ? studentObj?.identity_type : '',
      "identity_type_name": identityName,
      "education_grade_id": studentObj?.education_grade,
      "academic_period_id": studentObj?.academic_period ? studentObj?.academic_period : '',
      "start_date": studentObj?.start_date?.text ? studentObj?.start_date?.text : '',
      "end_date": studentObj?.end_date?.text ? studentObj?.end_date?.text : '',
      "institution_class_id": studentObj?.class ? studentObj?.class : '',
      "student_status_id": 1,
      "photo_name": "ravi",
      "photo_base_64": "base64_encode",
      "is_diff_school": "0",
      "student_id": "1234",
      "previous_institution_id": "1",
      "previous_academic_period_id": "31",
      "previous_education_grade_id": "1",
      "student_transfer_reason_id": "1",
      "comment": "Hello",
      "custom": studentObj?.custom
    }

    console.log(studentObj, "studentObj");
    let viewQuestion = this._viewQuestion;
    viewQuestion[2].value = studentObj?.openEMIS_id;
    viewQuestion[3].value = studentObj?.first_name;
    viewQuestion[4].value = studentObj?.middle_name ? studentObj?.middle_name : '';
    viewQuestion[5].value = studentObj?.third_name ? studentObj?.third_name : '';
    viewQuestion[6].value = studentObj?.last_name ? studentObj?.last_name : '';
    viewQuestion[7].value = studentObj?.preferred_name ? studentObj?.preferred_name : '';
    viewQuestion[8].value = studentObj?.gender == 1 ? 'Male' : 'Female';
    viewQuestion[9].value = studentObj?.date_of_birth;
    viewQuestion[10].value = studentObj?.email ? studentObj?.email : '';
    this._viewQuestion = [...viewQuestion];

    this.Rest.postWithToken('institutions/save-student', studentParams).subscribe({
      next: (res: any) => {
        console.log(res, "res");

      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    });
  }

  public disableNextButton(): void {
    setTimeout(() => {
      this.wizardApi.disableButton('next');
    }, 200)
  }

  public enableNextButton(): void {
    setTimeout(() => {
      this.wizardApi.enableButton('next');
    }, 200)
  }

  ngOnDestroy(): void {
    localStorage.removeItem("student_first_step");
  }
}
