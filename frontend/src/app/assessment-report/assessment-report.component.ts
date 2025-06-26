import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { IDynamicFormApi } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-assessment-report',
  templateUrl: './assessment-report.component.html',
  styleUrls: ['./assessment-report.component.css']
})
export class AssessmentReportComponent implements OnInit {
  displayLoading: boolean = false;
  counter: number = 0;
  public api: IDynamicFormApi = {};

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
      name: 'Report Card Generate',
      path: '',
    }]
  };

  public pageheader = {
    leftBtn: [{
      type: "back",
      callback: (): void => {
        this.backToData();
      }
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Report Card Generate",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public _formButtons: Array<any> = [
    {
      type: 'submit',
      name: 'Save',
      icon: 'kd-check',
      class: 'btn-text'
    },
    {
      type: 'reset',
      name: 'Cancel',
      icon: 'kd-close',
      class: 'btn-outline'
    }
  ];

  public _confirmationData: Array<any> = [
    {
      key: 'academic_period',
      label: 'Academic Period',
      visible: true,
      required: true,
      value: 2024,
      readonly: true,
      controlType: 'dropdown',
      options: [
        {
          key: '2024',
          value: '2024'
        },
        {
          key: '2023',
          value: '2023'
        },
        {
          key: '2022',
          value: '2022'
        }
      ],
      events: true,
    },
    {
      key: 'education_grade',
      label: 'Education Grade',
      visible: true,
      required: true,
      value: 1,
      readonly: true,
      controlType: 'dropdown',
      options: [
        {
          key: '1',
          value: 'Primary - Primary 1'
        },
        {
          key: '2',
          value: 'Primary - Primary 2'
        },
        {
          key: '3',
          value: 'Primary - Primary 3'
        }
      ],
      events: true,
    },
    {
      key: 'institution_classes',
      label: 'Institution Classes',
      visible: true,
      required: true,
      value: 1,
      readonly: true,
      controlType: 'dropdown',
      options: [
        {
          key: '1',
          value: 'Primary 1-A'
        },
        {
          key: '2',
          value: 'Primary 1-B'
        },
        {
          key: '3',
          value: 'Primary 1-C'
        }
      ],
      events: true,
    },
    {
      key: 'student_status',
      label: 'Student Status',
      visible: true,
      required: true,
      value: null,
      readonly: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '1',
          value: 'All Statuses'
        },
        {
          key: '2',
          value: 'Enrolled'
        },
        {
          key: '3',
          value: 'Transferred'
        },
        {
          key: '4',
          value: 'Withdrawn'
        },
        {
          key: '5',
          value: 'Graduated'
        },
        {
          key: '6',
          value: 'Promoted'
        },
        {
          key: '7',
          value: 'Repeated'
        }
      ],
      events: true,
    },
    {
      key: 'student_status',
      label: 'Student Status',
      visible: true,
      required: true,
      value: null,
      readonly: false,
      controlType: 'dropdown',
      options: [
        {
          key: 'null',
          value: '--Select--'
        },
        {
          key: '1',
          value: 'All Students'
        },
        {
          key: '2',
          value: 'Select Students'
        }
      ],
      events: true,
    },
  ]

  constructor(
    private Rest: ApiService,
    private router: Router
  ) { }

  ngOnInit(): void {
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
      // this.getAPIData();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          // this.getAPIData();
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

  backToData() {
    this.router.navigate(['/Institution/Institutions/Results']);
  }

}
