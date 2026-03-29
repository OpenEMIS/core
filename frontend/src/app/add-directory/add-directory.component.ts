import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { IDynamicFormApi, ITreeConfig, IWizardApi, KdPageBase, KdPageBaseEvent, KdToolbarEvent, KdWizardEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { IWizardConfig, WIZARD_TEXT } from './add-directory.config';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-add-directory',
  templateUrl: './add-directory.component.html',
  styleUrls: ['./add-directory.component.css']
})
export class AddDirectoryComponent extends KdPageBase implements OnInit {
  public displayLoading: boolean = true;

  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Directory',
      path: '',
    }]
  };

  public pageheader = {
    leftBtn: [{
      type: "back",
      callback: (): void => {
        this.backToData();
      }
    }],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Directory",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public wizardConfig: IWizardConfig = WIZARD_TEXT;
  public wizardType: string = 'html';
  public wizardApi: IWizardApi = {};
  public _step: number = 1;
  public wizardId: string = 'wizard';
  private _wizardActionSub: Subscription;
  private _wizardLastStepSub: Subscription;
  _status: any;
  _nextStatusSub: Subscription;
  public _formButtons: Array<any> = [];
  public api: IDynamicFormApi = {};
  public customFieldCounter: number = 0;
  public maxDate = new Date();

  public _questionBase: Array<any> = [
    {
      key: 'user_type',
      label: 'User Type',
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
    {
      key: 'is_refugee',
      label: 'Is Refugee',
      visible: true,
      required: false,
      order: 1,
      readonly: false,
      controlType: 'dropdown',
      options: [
        { key: '', value: '--Select--'},
        { key: 'Enable', value: 'Enable Refugee'},
      ],
      events: true,
    },
    {
      key: 'refugee_number',
      label: 'Refugee Number',
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
      options: [
        {
          key: 'null',
          value: '--Select--'
        }],
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
  public _confirmationQuestionBase: Array<any> = [
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
      options: [
        {
          key: 'null',
          value: '--Select--'
        }],
      events: true,
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
      key: 'birthplacearea',
      label: 'Birthplace Area',
      visible: true,
      required: false,
      controlType: 'tree',
      config: this.TREE_CONFIG_SINGLE
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
  counter: number = 0;
  firstStepValue: any = [];
  selectedFirstStepValue: any;
  confirmationStepValue: any = [];
  selectedConfirmationStepValue: any;
  public _confirmationData: any[];
  internalSearchData: any;
  showConfirmation: boolean = false;

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
          if (this.confirmationStepValue.length > 0) {
            this.firstStepValue = this.confirmationStepValue;
          }
          let questionBase = this._questionBase;
          console.log(questionBase, "----questionBase----");
          questionBase.forEach((element: any, index: any) => {
            const indexInArray = this.findIndex2(element?.key);
            if (indexInArray != undefined) {
              if (questionBase[index].key == "date_of_birth") {
                questionBase[index].value = this.firstStepValue[indexInArray].value.obj
              } else {
                console.log(this.firstStepValue[indexInArray].value,"this.firstStepValue[indexInArray].value");
                
                questionBase[index].value = this.firstStepValue[indexInArray].value
              }
            }
          });
          this._questionBase = [...questionBase];
        }
        this.wizardApi.updateSteps(_event.action);
      }
      if (_event?.step?.label == "User Details" && _event.action != "previous") {
        let firstValidation = this.firstStepValidation();
        if (firstValidation == undefined) {
          this.wizardApi.updateSteps(_event.action);
          this.internalSearchApi();
        }
      } else if (_event?.step?.content == "internal-search" && _event.action != "previous") {
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "external-search" && _event.action != "previous") {
        this.confirmationApi();
        this.wizardApi.updateSteps(_event.action);
      } else if (_event?.step?.content == "confirmation" && _event.action != "previous") {
        this.wizardApi.updateSteps(_event.action);
        this.wizardApi.disableButton('previous');
        this.finalStepApi();
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
    });

    this.loginData();
  }

  findIndex2(data: any) {
    for (let i = 0; i < this.firstStepValue.length; i++) {
      if (this.firstStepValue[i].key == data) {
        return i
      }
    }
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
    this.Rest.getWithToken('user-types').subscribe({
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
          confirmation[17].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[2].options.push(obj);
            confirmation[17].options.push(obj);
          });
          question[2].options.unshift({ key: null, value: '--Select--' });
          confirmation[17].options.unshift({ key: null, value: '--Select--' });
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
          confirmation[18].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[3].options.push(obj);
            confirmation[18].options.push(obj);
          });
          question[3].options.unshift({ key: null, value: '--Select--' });
          confirmation[18].options.unshift({ key: null, value: '--Select--' });
          this._confirmationQuestionBase = [...confirmation];
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
    })

    this.Rest.getWithToken('users/genders').subscribe({
      next: (res: any) => {
        if (res) {
          let question = this._questionBase;
          let confirmation = this._confirmationQuestionBase;
          question[12].options = [];
          confirmation[7].options = [];
          res.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            question[12].options.push(obj);
            confirmation[7].options.push(obj);
          });
          question[12].options.unshift({ key: null, value: '--Select--' });
          this._confirmationQuestionBase = confirmation;
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
  }

  detectValue(event: any) {
    if (event.key == "nationality") {
      let questionBase = this._questionBase;
      console.log(event?.value,"event?.value");
      
      questionBase[2].value = event?.value;
      questionBase[3].value = 161;
      questionBase[4].label = 'School';
      console.log(this.firstStepValue, "firstStepValue");

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

      questionBase[3].options.forEach((element: any) => {
        if (element.key == event.value) {
          questionBase[3].value = element.key;
          questionBase[4].label = element.value;
        }
      });
      if (this.firstStepValue.length > 0) {
        for (let i = 0; i < this.firstStepValue.length; i++) {
          questionBase.forEach((element: any) => {
            if (element.key == this.firstStepValue[i].key) {
              if (this.firstStepValue[i].key == "date_of_birth") {
                element.value = this.firstStepValue[i].value.obj;
              } else {
                if (this.firstStepValue[i].key != "identity_type") {
                  element.value = this.firstStepValue[i].value;
                }
              }
            }
          })
        }
      }
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
      console.log(getIndex, "getIndex");
      if (getIndex == -1) {
        this.firstStepValue.push(eventData);
      } else {
        this.firstStepValue.splice(getIndex, 1, eventData);
        console.log(this.firstStepValue, "this.firstStepValue");

      }
    }
    this.selectedFirstStepValue = {};
    this.firstStepValue.forEach((element: any) => {
      this.selectedFirstStepValue[element.key] = element.value;
    });
    console.log(this.selectedFirstStepValue, "this.selectedFirstStepValue");

  }

  firstStepValidation() {
    console.log(this.firstStepValue, "this.firstStepValue");
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
            let newValue: any = this.firstStepValue[j].value;
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

  internalSearchApi() {
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
      "user_type_id": this.selectedFirstStepValue?.user_type ? this.selectedFirstStepValue?.user_type : '',
      "nationality_id": this.selectedFirstStepValue?.nationality,
      "nationality_name": nationalityName ? nationalityName : '',
      "identity_type_name": identityName,
      "identity_type_id": this.selectedFirstStepValue?.identity_type ? this.selectedFirstStepValue?.identity_type : '',
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

  confirmationApi() {
    let confirmation = this._confirmationQuestionBase;
    confirmation[2].value = this.selectedFirstStepValue?.first_name;
    confirmation[3].value = this.selectedFirstStepValue?.middle_name;
    confirmation[4].value = this.selectedFirstStepValue?.third_name;
    confirmation[5].value = this.selectedFirstStepValue?.last_name;
    confirmation[6].value = this.selectedFirstStepValue?.preferred_name;
    confirmation[7].value = this.selectedFirstStepValue?.gender;
    confirmation[8].value = this.selectedFirstStepValue?.date_of_birth?.text;
    confirmation[17].value = this.selectedFirstStepValue?.nationality;
    if (this.selectedFirstStepValue?.nationality) {
      confirmation[18].value = this.selectedFirstStepValue?.identity_type ? this.selectedFirstStepValue?.identity_type : 161;
    }
    confirmation[19].value = this.selectedFirstStepValue?.identity_number;

    if (this.selectedFirstStepValue?.user_type) {
      let eventData = {
        key: "user_type",
        value: this.selectedFirstStepValue?.user_type
      }
      this.confirmationStepValue.push(eventData);
    }

    confirmation.forEach((element: any) => {
      if (element.value) {
        let eventData = {
          key: element?.key,
          value: element?.value
        }
        this.confirmationStepValue.push(eventData);
      }
    })

    this.Rest.getWithToken('users/generate-openemis-id').subscribe({
      next: (res: any) => {
        if (res) {
          let confirmation = this._confirmationQuestionBase;
          confirmation[1].value = res?.data?.openemis_no;
          confirmation[23].value = res?.data?.openemis_no;
          this._confirmationQuestionBase = [...confirmation];
          let eventData = {
            key: "username",
            value: res?.data?.openemis_no
          }
          this.confirmationStepValue.push(eventData);
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
          let confirmation = this._confirmationQuestionBase;
          confirmation[24].value = res?.data?.password;
          this._confirmationQuestionBase = [...confirmation];
          let eventData = {
            key: "password",
            value: res?.data?.password
          }
          this.confirmationStepValue.push(eventData);
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

    this.Rest.getWithToken('contact-types').subscribe({
      next: (res: any) => {
        if (res) {
          let confirmation = this._confirmationQuestionBase;
          confirmation[21].options = [];
          res?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            confirmation[21].options.push(obj);
          });
          confirmation[21].options.unshift({ key: null, value: '--Select--' });
          this._confirmationQuestionBase = [...confirmation];

          if (this.selectedFirstStepValue.user_type == 3) {
            this._confirmationData = this._confirmationQuestionBase;
            console.log(this._confirmationData, "this._confirmationData");

            this.showConfirmation = true;
          } else if (this.selectedFirstStepValue.user_type == 2) {
            this.Rest.getWithToken('staff-custom-fields').subscribe({
              next: (res: any) => {
                this._confirmationData = this._confirmationQuestionBase;
                if (res?.data?.length > 0) {
                  let key = 'Parents and Guardian Informations';
                  if (res?.data[key].length > 0) {
                    let customFieldData = [];
                    customFieldData.push({
                      controlType: "section",
                      key: "parent_guardian_info",
                      label: key,
                      visible: true
                    });
                    res?.data[key].forEach((element: any) => {
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
                      customFieldData.push(obj);
                    });

                    this._confirmationData = [...this._confirmationQuestionBase, ...customFieldData];
                    this.showConfirmation = true;
                    console.log(this._confirmationData, "this._confirmationData");
                  }
                } else {
                  this.showConfirmation = true;
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
          } else if (this.selectedFirstStepValue.user_type == 1) {
            this.Rest.getWithToken('student-custom-fields').subscribe({
              next: (res: any) => {
                console.log(res, "res");
                let key = 'Parents and Guardian Informations';
                if (res?.data[key].length > 0) {
                  console.log(this._confirmationQuestionBase, "_confirmationQuestionBase");
                  let customFieldData = [];
                  customFieldData.push({
                    controlType: "section",
                    key: "parent_guardian_info",
                    label: key,
                    visible: true
                  });
                  res?.data[key].forEach((element: any) => {
                    let obj = {}
                    if (element.field_type == 'TEXT') {
                      obj = {
                        key: element.name,
                        label: element.name,
                        visible: true,
                        required: element.is_mandatory == 0 ? false : true,
                        order: element.order,
                        controlType: 'text',
                        value: ""
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
                    customFieldData.push(obj);
                  });
                  console.log(customFieldData, "customFieldData -------");

                  this._confirmationData = [...this._confirmationQuestionBase, ...customFieldData];
                  this.showConfirmation = true;
                  console.log(this._confirmationData, "this._confirmationData");


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


    if (this.confirmationStepValue.length == 0) {
      this._confirmationQuestionBase.forEach((element: any) => {
        if (element.value) {
          let eventData = {
            key: element?.key,
            value: element?.value
          }
          this.confirmationStepValue.push(eventData);
        }
      })
    } else {
      let eventData = {
        key: event?.key,
        value: event?.value
      }

      let getIndex = this.confirmationStepValue.findIndex((obj => obj.key == eventData.key));

      if (getIndex == -1) {
        this.confirmationStepValue.push(eventData);
      } else {
        this.confirmationStepValue.splice(getIndex, 1, eventData);
      }
    }

    this.selectedConfirmationStepValue = {};
    this.confirmationStepValue.forEach((element: any) => {
      this.selectedConfirmationStepValue[element.key] = element.value;
    });

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

  finalStepApi() {
    console.log(this.confirmationStepValue, "this.confirmationStepValue");
    let finalObj: any = {};
    let obj: any = {};
    this.confirmationStepValue.forEach((element: any) => {
      finalObj[element.key] = element.value;
    });

    console.log(finalObj, "finalObj");
    if (finalObj?.user_type == 2) {
      obj = {
        "is_same_school": "0",
        "openemis_no": finalObj?.username,
        "first_name": finalObj?.first_name,
        "middle_name": finalObj?.middle_name ? finalObj?.middle_name : '',
        "third_name": finalObj?.third_name ? finalObj?.third_name : '',
        "last_name": finalObj?.last_name,
        "preferred_name": finalObj?.preferred_name ? finalObj?.preferred_name : '',
        "gender_id": finalObj?.gender,
        "date_of_birth": finalObj?.date_of_birth ? finalObj?.date_of_birth : '',
        "identity_number": finalObj?.identity_number ? finalObj?.identity_number : '',
        "nationality_id": finalObj?.nationality ? finalObj?.nationality : '',
        "username": finalObj?.username,
        "password": finalObj?.password,
        "postal_code": finalObj?.postal_code ? finalObj?.postal_code : '',
        "address": finalObj?.address ? finalObj?.address : '',
        "birthplace_area_id": "2",
        "address_area_id": "2",
        "photo_name": "ravi",
        "photo_base_64": finalObj?.image ? finalObj?.image : '',
        "custom": []
      }
      let viewQuestion = this._viewQuestion;
      viewQuestion[2].value = finalObj?.username;
      viewQuestion[3].value = finalObj?.first_name;
      viewQuestion[4].value = finalObj?.middle_name ? finalObj?.middle_name : '';
      viewQuestion[5].value = finalObj?.third_name ? finalObj?.third_name : '';
      viewQuestion[6].value = finalObj?.last_name ? finalObj?.last_name : '';
      viewQuestion[7].value = finalObj?.preferred_name ? finalObj?.preferred_name : '';
      viewQuestion[8].value = finalObj?.gender_id == 1 ? 'Male' : 'Female';
      viewQuestion[9].value = finalObj?.date_of_birth?.text;
      viewQuestion[10].value = finalObj?.email ? finalObj?.email : '';
      this._viewQuestion = [...viewQuestion];
      this.saveDetails('institutions/save-staff', obj);
    } else if (finalObj?.user_type == 3) {
      obj = {
        "openemis_no": finalObj?.username,
        "first_name": finalObj?.first_name,
        "middle_name": finalObj?.middle_name ? finalObj?.middle_name : '',
        "third_name": finalObj?.third_name ? finalObj?.third_name : '',
        "last_name": finalObj?.last_name,
        "preferred_name": finalObj?.preferred_name ? finalObj?.preferred_name : '',
        "gender_id": finalObj?.gender,
        "date_of_birth": finalObj?.date_of_birth,
        "identity_number": finalObj?.identity_number ? finalObj?.identity_number : '',
        "nationality_id": finalObj?.nationality ? finalObj?.nationality : '',
        "username": finalObj?.username,
        "password": finalObj?.password,
        "postal_code": finalObj?.postal_code ? finalObj?.postal_code : '',
        "address": finalObj?.address ? finalObj?.address : '',
        "birthplace_area_id": "16",
        "address_area_id": "24",
        "identity_type_id": finalObj?.identity_type ? finalObj?.identity_type : ''
      }
      let viewQuestion = this._viewQuestion;
      viewQuestion[2].value = finalObj?.username;
      viewQuestion[3].value = finalObj?.first_name;
      viewQuestion[4].value = finalObj?.middle_name ? finalObj?.middle_name : '';
      viewQuestion[5].value = finalObj?.third_name ? finalObj?.third_name : '';
      viewQuestion[6].value = finalObj?.last_name ? finalObj?.last_name : '';
      viewQuestion[7].value = finalObj?.preferred_name ? finalObj?.preferred_name : '';
      viewQuestion[8].value = finalObj?.gender_id == 1 ? 'Male' : 'Female';
      viewQuestion[9].value = finalObj?.date_of_birth?.text;
      viewQuestion[10].value = finalObj?.email ? finalObj?.email : '';
      this._viewQuestion = [...viewQuestion];
      this.saveDetails('institutions/save-guardian', obj);
    } else if (finalObj?.user_type == 1) {
      obj = {
        "openemis_no": finalObj?.username,
        "first_name": finalObj?.first_name,
        "middle_name": finalObj?.middle_name ? finalObj?.middle_name : '',
        "third_name": finalObj?.third_name ? finalObj?.third_name : '',
        "last_name": finalObj?.last_name,
        "preferred_name": finalObj?.preferred_name ? finalObj?.preferred_name : '',
        "gender_id": finalObj?.gender,
        "date_of_birth": finalObj?.date_of_birth,
        "identity_number": finalObj?.identity_number ? finalObj?.identity_number : '',
        "nationality_id": finalObj?.nationality ? finalObj?.nationality : '',
        "username": finalObj?.username,
        "password": finalObj?.password,
        "postal_code": finalObj?.postal_code ? finalObj?.postal_code : '',
        "address": finalObj?.address ? finalObj?.address : '',
        "birthplace_area_id": "16",
        "address_area_id": "24",
        "identity_type_id": finalObj?.identity_type ? finalObj?.identity_type : ''
      }
      this.saveDetails('institutions/save-student', obj);
    }
  }

  saveDetails(link: any, obj: any) {
    this.Rest.postWithToken(link, obj).subscribe({
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

  backToData() {

  }

}
