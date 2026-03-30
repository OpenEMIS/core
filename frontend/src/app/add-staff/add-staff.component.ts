import { Component, OnInit } from '@angular/core';
import { IWizardConfig, STAFF_TEXT } from './add-staff.config';
import { IDynamicFormApi, ITreeConfig, IWizardApi, KdPageBase, KdPageBaseEvent, KdToolbarEvent, KdWizardEvent } from 'openemis-styleguide-lib';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../api.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-add-staff',
  templateUrl: './add-staff.component.html',
  styleUrls: ['./add-staff.component.css']
})
export class AddStaffComponent extends KdPageBase implements OnInit {
  public displayLoading: boolean = true;
  public breadcrumbList: any;
  _nextStatusSub: Subscription;
  _status: any;

  public pageheader: any = {}
  public wizardConfig: IWizardConfig = STAFF_TEXT;
  public wizardType: string = 'html';
  public wizardApi: IWizardApi = {};
  public _step: number = 1;
  public wizardId: string = 'wizard';

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
      controlType: 'text'
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

  public _addStaffQuestionBase: Array<any> = [
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
      key: 'staff',
      label: 'Staff',
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
      value: "",
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
      key: 'staff_status',
      label: 'Staff Status',
      visible: true,
      required: true,
      readonly: true,
      value: 'Pending',
      order: 1,
      controlType: 'text',
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
      controlType: 'date',
      readonly: false,
      minDate: "1970-01-01",
      maxDate: `${this.maxDate.getFullYear()}-${this.maxDate.getMonth() + 1
        }-${this.maxDate.getDate()}`
    },
    {
      key: 'position_type',
      label: 'Position Type',
      visible: true,
      required: true,
      readonly: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '1',
          value: 'Full-Time'
        },
        {
          key: '2',
          value: 'Part-Time'
        }],
      events: true,
    },
    {
      key: 'is_homeroom',
      label: 'Is Homeroom',
      visible: true,
      required: true,
      readonly: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '1',
          value: 'No'
        },
        {
          key: '2',
          value: 'Yes'
        }],
      events: true,
    },
    {
      key: 'position',
      label: 'Position',
      visible: true,
      required: true,
      readonly: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '1',
          value: 'Teaching'
        },
        {
          key: '2',
          value: 'Non-Teaching'
        }],
      events: true,
    },
    {
      key: 'position_grade',
      label: 'Position Grade',
      visible: true,
      required: true,
      readonly: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '1',
          value: 'Pay scale 1'
        },
        {
          key: '2',
          value: 'Pay scale 2'
        }],
      events: true,
    },
    {
      key: 'staff_type',
      label: 'Staff Type',
      visible: true,
      required: true,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
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
  confirmationStepValue: any = [];
  addStaffData: any = [];
  firstStepData: any;
  counter: any = 0;
  selectedFirstStepValue: any;
  internalSearchData: any;
  selectedInternalSearchData: any;
  login_user_id: string;
  institution_name: string;
  institution_id: string;


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
      console.log('action clicked event', _event);
      if (_event.action == "previous") {
        if (_event.step.label == 'Internal Search') {
          console.log(this.confirmationStepValue, "this.confirmationStepValue");
          this.firstStepData = localStorage.getItem("staff_first_step");
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
                questionBase[index].value = this.firstStepData[indexInArray].value.obj
              } else {
                questionBase[index].value = this.firstStepData[indexInArray].value
              }
            }
          })
          this._questionBase = [...questionBase]
          this.wizardApi.updateSteps(_event.action);

        } else if (_event.step.content == 'add-staff') {
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

      if (_event?.step?.label == "User Details" && _event.action != "previous") {
        let firstValidation = this.firstStepValidation();
        console.log(firstValidation, "firstValidation");
        if (firstValidation == undefined) {
          this.wizardApi.updateSteps(_event.action);
          this.checkInternalSearch();
          localStorage.setItem("staff_first_step", JSON.stringify(this.firstStepValue));
        }
      } else if (_event?.step?.content == "internal-search" && _event.action != "previous") {
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "external-search" && _event.action != "previous") {
        this.confirmationApi();
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "confirmation" && _event.action != "previous") {
        this.addStaffApi();
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "add-staff" && _event.action != "previous") {
        let addStaffValidation = this.addStaffValidation();
        if (addStaffValidation == undefined) {
          this.wizardApi.updateSteps(_event.action);
          this.wizardApi.disableButton('previous');
          this.saveStaffData();
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
          res.data.forEach((element: any) => {
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
          let staffQuestion = this._addStaffQuestionBase;
          question[11].options = [];
          confirmation[8].options = [];
          staffQuestion[5].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[11].options.push(obj);
            confirmation[8].options.push(obj);
            staffQuestion[5].options.push(obj);
          });
          question[11].options.unshift({ key: null, value: '--Select--' });
          confirmation[8].options.unshift({ key: null, value: '--Select--' });
          staffQuestion[5].options.unshift({ key: null, value: '--Select--' });
          this._questionBase = [...question];
          this._confirmationQuestionBase = [...confirmation];
          this._addStaffQuestionBase = [...staffQuestion];
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


  firstStepValidation() {
    console.log(this.firstStepValue, "this.firstStepValue");
    if (this.firstStepValue.length == 0) {
      setTimeout(() => {
        this.api.setProperty(this._questionBase[6].key, "errors", [
          "This field is required"
        ]);
      }, 100);
      return true;
    } else {
      console.log(this._questionBase, "this._questionBase");

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

  addStaffValidation() {
    console.log(this.addStaffData, "this.addStaffData");

    if (this.addStaffData.length == 0) {
      setTimeout(() => {
        this.api.setProperty(this._addStaffQuestionBase[6].key, "errors", [
          "This field is required"
        ]);
      }, 100);
      return true;
    } else {
      for (let i = 0; i < this._addStaffQuestionBase.length; i++) {
        if (this._addStaffQuestionBase[i].required) {
          for (let j = 0; j <= this.addStaffData.length - 1; j++) {

            let checkData = this.addStaffData.findIndex((obj => obj.key == this._addStaffQuestionBase[i].key));
            if (checkData == -1 && (this._addStaffQuestionBase[i].key != 'staff_status' || this._addStaffQuestionBase[i].key != 'end_date')) {
              setTimeout(() => {
                this.api.setProperty(this._addStaffQuestionBase[i].key, "errors", [
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
      "user_type_id": "2",
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
          console.log(res, "res");
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
    console.log(event, "event----");
    if (event.key == "nationality") {
      let questionBase = this._questionBase;
      questionBase[1].value = event?.value;
      questionBase[2].value = 161;
      questionBase[3].label = 'School';
      questionBase[3].value = '';
      if (this.firstStepValue.length > 0) {
        for (let i = 0; i < this.firstStepValue.length; i++) {
          questionBase.forEach((element: any) => {
            if (element.key == this.firstStepValue[i].key) {
              if (this.firstStepValue[i].key == "date_of_birth") {
                element.value = this.firstStepValue[i].value.obj;
              } else {
                if (this.firstStepValue[i].key != "identity_type" || this.firstStepValue[i].key != "identity_number") {
                  element.value = this.firstStepValue[i].value;
                }
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
      console.log(questionBase[3], "questionBase 000");

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
    console.log(this.firstStepValue, "this.firstStepValue");

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
    console.log(this.confirmationStepValue, "confirmationStepValue");

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

  confirmationApi() {
    this.selectedFirstStepValue = {};
    this.firstStepValue.forEach((element: any) => {
      this.selectedFirstStepValue[element.key] = element.value;
    });
    let confirmationQuestion = this._confirmationQuestionBase;
    console.log(this.selectedFirstStepValue, "this.selectedFirstStepValue");
    confirmationQuestion[3].value = this.selectedFirstStepValue?.first_name;
    confirmationQuestion[4].value = this.selectedFirstStepValue?.middle_name;
    confirmationQuestion[5].value = this.selectedFirstStepValue?.third_name;
    confirmationQuestion[6].value = this.selectedFirstStepValue?.last_name;
    confirmationQuestion[7].value = this.selectedFirstStepValue?.preferred_name;
    confirmationQuestion[8].value = this.selectedFirstStepValue?.gender;
    confirmationQuestion[9].value = this.selectedFirstStepValue?.date_of_birth?.text;
    confirmationQuestion[20].value = this.selectedFirstStepValue?.nationality;
    if (this.selectedFirstStepValue?.nationality) {
      confirmationQuestion[21].value = this.selectedFirstStepValue?.identity_type ? this.selectedFirstStepValue?.identity_type : 161;
    }
    confirmationQuestion[22].value = this.selectedFirstStepValue?.identity_number;
    this._confirmationQuestionBase = [...confirmationQuestion];

    for (const property in this.selectedFirstStepValue) {
      console.log(this.selectedFirstStepValue[property]);
      if (property == 'date_of_birth') {
        let obj = {
          key: property,
          value: {
            text: this.selectedFirstStepValue[property]?.obj?.year + '-' + this.selectedFirstStepValue[property]?.obj?.month + '-' + this.selectedFirstStepValue[property]?.obj?.day,
            obj: this.selectedFirstStepValue[property].obj
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
    console.log(event, "event");
    this.selectedInternalSearchData = event;
  }

  addStaffApi() {
    this.addStaffData.push({
      key: 'staff_status',
      value: 'Pending'
    });
    this.addStaffData.push({
      key: 'user_name',
      value: this._confirmationQuestionBase[2].value
    });
    this.addStaffData.push({
      key: "first_name",
      value: this._confirmationQuestionBase[3].value
    });
    this.addStaffData.push({
      key: "last_name",
      value: this._confirmationQuestionBase[6].value
    });
    let confirmation = this._confirmationQuestionBase;
    confirmation.forEach((element: any) => {
      let staffQuestion = this._addStaffQuestionBase;
      for (let i = 0; i < this._addStaffQuestionBase.length; i++) {
        if (element.key == this._addStaffQuestionBase[i].key) {
          staffQuestion[i].value = element.value;
          let eventData = {
            key: element?.key,
            value: element?.value
          }
          this.addStaffData.push(eventData);
        }
      }
      this._addStaffQuestionBase = [...staffQuestion];
    })

    let staffQuestion = this._addStaffQuestionBase;
    staffQuestion[2].value = confirmation[3].value + ' ' + confirmation[6].value;
    staffQuestion[6].value = confirmation[2].value;
    this._addStaffQuestionBase = [...staffQuestion];

    this.Rest.getWithToken('users/generate-password').subscribe({
      next: (res: any) => {
        if (res) {
          let staffQuestion = this._addStaffQuestionBase;
          staffQuestion[7].value = res?.data?.password;
          this._addStaffQuestionBase = [...staffQuestion];
          this.addStaffData.push({
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

    this.Rest.getWithToken('staff-types').subscribe({
      next: (res: any) => {
        if (res) {
          let staffQuestion = this._addStaffQuestionBase;
          let staffData = [];
          res?.data?.list.forEach((element: any) => {
            let staffObj = {
              key: element?.id,
              value: element?.name
            }
            staffData.push(staffObj);
          });
          staffQuestion[15].options = staffData;
          staffQuestion[15].options.unshift({ key: null, value: '--Select--' });
          this._addStaffQuestionBase = [...staffQuestion];
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

  addStaffValue(event: any) {
    let eventData = {
      key: event?.key,
      value: event?.value
    }
    if (this.addStaffData.length == 0) {
      this.addStaffData.push(eventData);
    } else {
      let getIndex = this.addStaffData.findIndex((obj => obj.key == eventData.key));
      if (getIndex == -1) {
        this.addStaffData.push(eventData);
      } else {
        this.addStaffData.splice(getIndex, 1, eventData);
      }
    }
  }

  saveStaffData() {
    let staffObj: any = {};
    console.log(this.addStaffData,"this.addStaffData");
    
    this.addStaffData.forEach((element: any) => {
      if (element?.key == "date_of_birth" || element?.key == "start_date" || element?.key == "end_date") {
        staffObj[element.key] = element?.value?.obj?.year + '-' + element?.value?.obj?.month + '-' + element?.value?.obj?.day
      } else {
        staffObj[element.key] = element.value;
      }
    });

    console.log(this.confirmationStepValue,"this.confirmationStepValue");
    
    this.confirmationStepValue.forEach((element: any) => {
      staffObj[element.key] = element.value;
    });
    console.log(staffObj, "staffObj");

    let obj = {
      "institution_id": this.institution_id,
      "is_same_school": "0",
      "openemis_no": staffObj?.openEMIS_id,
      "first_name": staffObj?.first_name,
      "middle_name": staffObj?.middle_name ? staffObj?.middle_name : '',
      "third_name": staffObj?.third_name ? staffObj?.third_name : '',
      "last_name": staffObj?.last_name,
      "preferred_name": staffObj?.preferred_name ? staffObj?.preferred_name : '',
      "gender_id": staffObj?.gender,
      "date_of_birth": staffObj?.date_of_birth?.text ? staffObj?.date_of_birth?.text : '',
      "identity_number": staffObj?.identity_number ? staffObj?.identity_number : '',
      "nationality_id": staffObj?.nationality ? staffObj?.nationality : '',
      "username": staffObj?.user_name,
      "password": staffObj?.password,
      "postal_code": staffObj?.postal_code ? staffObj?.postal_code : '',
      "address": staffObj?.address ? staffObj?.address : '',
      "birthplace_area_id": "2",
      "address_area_id": "2",
      "identity_type_id": "160",
      "academic_period_id": "30",
      "start_date": staffObj?.start_date,
      "end_date": staffObj?.end_date,
      "staff_type_id": staffObj?.staff_type,
      "institution_position_id": staffObj?.position,
      "fte": 1,
      "staff_id": 506,
      "previous_institution_id": null,
      "staff_position_grade_id": staffObj?.position_grade,
      "photo_name": "ravi",
      "photo_base_64": staffObj?.image ? staffObj?.image : '',
      "custom": []
    }
    console.log(obj, "obj");


    let viewQuestion = this._viewQuestion;
    viewQuestion[2].value = staffObj?.openEMIS_id;
    viewQuestion[3].value = staffObj?.first_name;
    viewQuestion[4].value = staffObj?.middle_name ? staffObj?.middle_name : '';
    viewQuestion[5].value = staffObj?.third_name ? staffObj?.third_name : '';
    viewQuestion[6].value = staffObj?.last_name ? staffObj?.last_name : '';
    viewQuestion[7].value = staffObj?.preferred_name ? staffObj?.preferred_name : '';
    viewQuestion[8].value = staffObj?.gender_id == 1 ? 'Male' : 'Female';
    viewQuestion[9].value = staffObj?.date_of_birth?.text;
    viewQuestion[10].value = staffObj?.email ? staffObj?.email : '';
    this._viewQuestion = [...viewQuestion];

    this.Rest.postWithToken('institutions/save-staff', obj).subscribe({
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
    localStorage.removeItem("staff_first_step");
  }

}
