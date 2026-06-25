import { Component, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { IWizardConfig, WIZARD_HTML } from './add-guardians.config';
import { IDynamicFormApi, ITreeConfig, IWizardApi, KdPageBase, KdPageBaseEvent, KdToolbarEvent, KdWizardEvent } from 'openemis-styleguide-lib';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

@Component({
  selector: 'app-add-guardians',
  templateUrl: './add-guardians.component.html',
  styleUrls: ['./add-guardians.component.css']
})
export class AddGuardiansComponent extends KdPageBase implements OnInit {
  public api: IDynamicFormApi = {};
  public displayLoading: boolean = true;
  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [
      {
        name: 'Institutions',
        path: '',
      },
      {
        name: 'Avory Primary School',
        path: '',
      },
      {
        name: 'Students',
        path: '',
      },
      {
        name: 'Aaron Butler',
        path: '',
      },
      {
        name: 'Add Guardians',
        path: '',
      }
    ]
  };
  _nextStatusSub: Subscription;
  _status: any;

  public pageheader = {
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Aaron Butler - Add Student Guardians",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  public wizardConfig: IWizardConfig = WIZARD_HTML;
  public wizardType: string = 'html';
  public wizardApi: IWizardApi = {};
  public _step: number = 1;
  public wizardId: string = 'wizard';

  public maxDate = new Date();
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
  public _questionBase: Array<any> = [
    {
      key: 'relation_type',
      label: 'Relation Type',
      visible: true,
      required: true,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [],
      events: true,
    },
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
    { key: 'identities_nationalities', label: 'Identities / Nationalities', visible: true, controlType: 'section' },
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
      key: 'username',
      label: 'Username',
      visible: true,
      required: true,
      readonly: true,
      order: 1,
      controlType: 'text',
    }
  ]

  public _formButtons: Array<any> = [];
  firstStepValue: any = [];
  errorKey: any;
  confirmationValue: any = [];

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
          type: 'student',
          height: '150',
          width: '150',
          isExpandable: false,
          title: 'Tony_chan.jpg'
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
      'value': 1472460657,
      'key': 'openemis_no',
      'visible': true,
      'label': 'Openemis No.',
      'type': 'string'
    },
    {
      'key': 'first_name',
      'label': 'First Name',
      'visible': true,
      'value': 'Administrator',
    },
    {
      'key': 'middle_name',
      'label': 'Middle Name',
      'visible': true,
      'controlType': 'text',
      'value': 'Admin',
    },
    {
      'key': 'third_name',
      'label': 'Third Name',
      'visible': true,
      'controlType': 'text',
      'type': 'string',
      'value': 'Super',
    },
    {
      'key': 'last_name',
      'label': 'Last Name',
      'visible': true,
      'controlType': 'text',
      'value': 'User',
    },
    {
      'key': 'preferred_name',
      'label': 'Preferred Name',
      'visible': true,
      'controlType': 'text',
      'value': 'Test User'
    },
    {
      'key': 'gender',
      'label': 'Gender',
      'visible': true,
      'controlType': 'text',
      'value': 'Male'
    },
    {
      'key': 'dob',
      'label': 'Date of Birth',
      'visible': true,
      'controlType': 'text',
      'value': '01-01-1995'
    },
    {
      'key': 'email',
      'label': 'Email',
      'visible': true,
      'controlType': 'text',
      'value': 'test@mailinator.com'
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
  counter: any = 0;
  selectedFirstStepValue: any;
  confirmationStepValue: any = [];
  firstStepData: any;
  selectedImage: any;
  internalSearchData: any;
  selectedInternalSearchData: any;
  themeArray = DEFAULT_TEMPLATE_THEME;

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

    this._kdWizardEvent.onActionClicked(this.wizardId).subscribe((_event: any): void => {
      console.log('action clicked event', _event);
      if (_event.action == "previous") {
        if (_event.step.label == 'Internal Search') {
          this.firstStepData = localStorage.getItem("gurdian_first_step");
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
        } else {
          this.wizardApi.updateSteps(_event.action);
        }
        return;
      }
      if (_event?.step?.label == "User Details" && _event.action != "previous") {
        let firstValidation = this.firstStepValidation();
        if (firstValidation == undefined) {
          this.wizardApi.updateSteps(_event.action);
          this.checkInternalSearch();
          localStorage.setItem("gurdian_first_step", JSON.stringify(this.firstStepValue));
        }
      } else if (_event?.step?.content == "internal-search" && _event.action != "previous") {
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "external-search" && _event.action != "previous") {
        this.confirmationApi();
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "confirmation" && _event.action != "previous") {
        this.saveGuardianData();
        this.wizardApi.updateSteps(_event.action);
        this.wizardApi.disableButton('previous');
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
          this.setTheme();
          this.removeSession();
        }
      }
    } else {
      this.setTheme();
      this.getUserType();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.getUserType();
          this.setTheme();
          this.removeSession();
        }
      },
      error: (error: any) => {

      }
    })
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

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  findIndex2(data: any) {
    for (let i = 0; i < this.firstStepData.length; i++) {
      if (this.firstStepData[i].key == data) {
        return i
      }
    }
  }

  getUserType() {
    this.Rest.getWithToken('relationship-types').subscribe({
      next: (res: any) => {
        if (res) {
          let question = this._questionBase;
          question[0].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[0].options.push(obj);
          });
          question[0].options.unshift({ key: null, value: '--Select--' });
          this._questionBase = [...question];
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

    this.Rest.getWithToken('nationalities').subscribe({
      next: (res: any) => {
        if (res) {
          let question = this._questionBase;
          let confirmation = this._confirmationQuestionBase;
          question[2].options = [];
          confirmation[18].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[2].options.push(obj);
            confirmation[18].options.push(obj);
          });
          question[2].options.unshift({ key: null, value: '--Select--' });
          confirmation[18].options.unshift({ key: null, value: '--Select--' });
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
          question[3].options = [];
          confirmation[19].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[3].options.push(obj);
            confirmation[19].options.push(obj);
          });
          question[3].options.unshift({ key: null, value: '--Select--' });
          confirmation[19].options.unshift({ key: null, value: '--Select--' });
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
          question[12].options = [];
          confirmation[8].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[12].options.push(obj);
            confirmation[8].options.push(obj);
          });
          question[12].options.unshift({ key: null, value: '--Select--' });
          confirmation[8].options.unshift({ key: null, value: '--Select--' });
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
  }

  checkInternalSearch() {
    this.selectedFirstStepValue = {};
    this.firstStepValue.forEach((element: any) => {
      this.selectedFirstStepValue[element.key] = element.value;
    });

    let nationalityName = '';
    let identityName = '';
    this._questionBase[2].options.forEach((element: any) => {
      if (element.key == this.selectedFirstStepValue?.nationality) {
        if (element.value == "--Select--") {
          nationalityName = '';
        } else {
          nationalityName = element.value;
        }
      }
    })
    this._questionBase[3].options.forEach((element: any) => {
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
      "institution_id": null,
      "user_type_id": "3",
      "nationality_id": this.selectedFirstStepValue?.nationality,
      "nationality_name": nationalityName ? nationalityName : '',
      "identity_type_name": identityName,
      "identity_type_id": this.selectedFirstStepValue?.identity_type,
      "gender_id": this.selectedFirstStepValue?.gender
    }
    console.log(obj, "obj");
    this.internalSearchData = [];
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
    if (event.key == "nationality") {
      let questionBase = this._questionBase;
      questionBase[2].value = event?.value;
      questionBase[3].value = 161;
      questionBase[4].label = 'School';
      questionBase[4].value = '';
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
      questionBase[3].options.forEach((element: any) => {
        if (element.key == event.value) {
          questionBase[4].label = element.value
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
      questionBase[3].value = event.value;
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
  }

  confirmationApi() {
    this.selectedFirstStepValue = {};
    this.firstStepValue.forEach((element: any) => {
      this.selectedFirstStepValue[element.key] = element.value;
    });
    let confirmationQuestion = this._confirmationQuestionBase;
    confirmationQuestion[3].value = this.selectedFirstStepValue?.first_name;
    confirmationQuestion[4].value = this.selectedFirstStepValue?.middle_name;
    confirmationQuestion[5].value = this.selectedFirstStepValue?.third_name;
    confirmationQuestion[6].value = this.selectedFirstStepValue?.last_name;
    confirmationQuestion[7].value = this.selectedFirstStepValue?.preferred_name;
    confirmationQuestion[8].value = this.selectedFirstStepValue?.gender;
    confirmationQuestion[9].value = this.selectedFirstStepValue?.date_of_birth?.text;
    confirmationQuestion[18].value = this.selectedFirstStepValue?.nationality;
    if (this.selectedFirstStepValue?.nationality) {
      confirmationQuestion[19].value = this.selectedFirstStepValue?.identity_type ? this.selectedFirstStepValue?.identity_type : 161;
    }
    confirmationQuestion[20].value = this.selectedFirstStepValue?.identity_number;
    this._confirmationQuestionBase = [...confirmationQuestion];

    for (const property in this.selectedFirstStepValue) {
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
          confirmation[24].value = element?.openemis_no;
          this._confirmationQuestionBase = [...confirmation];
        }

      });
    } else {
      this.Rest.getWithToken('users/generate-openemis-id').subscribe({
        next: (res: any) => {
          if (res) {
            let confirmation = this._confirmationQuestionBase;
            confirmation[2].value = res?.data?.openemis_no;
            confirmation[24].value = res?.data?.openemis_no;
            this._confirmationQuestionBase = [...confirmation];
            let obj = {
              key: 'openemis_no',
              value: res?.data?.openemis_no
            }
            this.confirmationStepValue.push(obj);
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
          confirmation[22].options = [];
          res?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            confirmation[22].options.push(obj);
          });
          confirmation[22].options.unshift({ key: null, value: '--Select--' });
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

    this.Rest.getWithToken('users/generate-password').subscribe({
      next: (res: any) => {
        if (res) {
          this.confirmationStepValue.push({
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

  getTriggerInput(event: any) {
    this.selectedInternalSearchData = event;
  }

  firstStepValidation() {
    if (this.firstStepValue.length == 0) {
      setTimeout(() => {
        this.api.setProperty(this._questionBase[0].key, "errors", [
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

  saveGuardianData() {
    let guardianObj: any = {};
    console.log(this.confirmationStepValue, "this.confirmationStepValue");

    this.confirmationStepValue.forEach((element: any) => {
      guardianObj[element.key] = element.value;
    });
    console.log(guardianObj, "guardianObj");

    let obj = {
      "guardian_relation_id": guardianObj?.relation_type,
      "student_id": "1161",
      "login_user_id": "1",
      "openemis_no": guardianObj?.openemis_no,
      "first_name": guardianObj?.first_name,
      "middle_name": guardianObj?.middle_name ? guardianObj?.middle_name : '',
      "third_name": guardianObj?.third_name ? guardianObj?.third_name : '',
      "last_name": guardianObj?.last_name,
      "preferred_name": guardianObj?.preferred_name ? guardianObj?.preferred_name : '',
      "gender_id": guardianObj?.gender,
      "date_of_birth": guardianObj?.date_of_birth?.text,
      "identity_number": guardianObj?.identity_number ? guardianObj?.identity_number : '',
      "nationality_id": guardianObj?.nationality ? guardianObj?.nationality : '',
      "username": guardianObj?.openemis_no,
      "password": guardianObj?.password,
      "postal_code": guardianObj?.postal_code ? guardianObj?.postal_code : '',
      "address": guardianObj?.address ? guardianObj?.address : '',
      "birthplace_area_id": "16",
      "address_area_id": "24",
      "identity_type_id": guardianObj?.identity_type ? guardianObj?.identity_type : ''
    }

    let viewQuestion = this._viewQuestion;
    viewQuestion[2].value = guardianObj?.openemis_no;
    viewQuestion[3].value = guardianObj?.first_name;
    viewQuestion[4].value = guardianObj?.middle_name ? guardianObj?.middle_name : '';
    viewQuestion[5].value = guardianObj?.third_name ? guardianObj?.third_name : '';
    viewQuestion[6].value = guardianObj?.last_name ? guardianObj?.last_name : '';
    viewQuestion[7].value = guardianObj?.preferred_name ? guardianObj?.preferred_name : '';
    viewQuestion[8].value = guardianObj?.gender_id == 1 ? 'Male' : 'Female';
    viewQuestion[9].value = guardianObj?.date_of_birth?.text;
    viewQuestion[10].value = guardianObj?.email ? guardianObj?.email : '';
    this._viewQuestion = [...viewQuestion];

    console.log(obj, "object data");
    this.Rest.postWithToken('institutions/save-guardian', obj).subscribe({
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
    localStorage.removeItem("gurdian_first_step");
  }

}
