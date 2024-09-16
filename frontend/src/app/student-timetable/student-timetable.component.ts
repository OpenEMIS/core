import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { DialogOpenComponent } from '../dialog-open/dialog-open.component';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-student-timetable',
  templateUrl: './student-timetable.component.html',
  styleUrls: ['./student-timetable.component.css']
})
export class StudentTimetableComponent implements OnInit {
  displayLoading: boolean = false;

  public pageheader = {
    leftBtn: [{
      type: "back",
      callback: (): void => {
        this.backToData();
      }
    },
    {
      custom: true,
      icon: 'fa fa-info',
      tooltip: 'Overview',
      callback: (): void => {
        this.overViewData();
      }
    },
    {
      custom: true,
      icon: 'fa fa-download',
      tooltip: 'Download',
      callback: () => {
        this.overViewData();
      }
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Schedule Timetable",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  isMouseOver: boolean = false;
  currentRowIndex: number | null = null;
  currentCellIndex: number | null = null;
  public days = [];
  public timetableData: Array<any> = [
    // {
    //   time: "07:00 AM - 07:30 AM",
    //   data: [
    //     {
    //       day: this.days[0],
    //       subject: "Spanish",
    //       room: "Room 3",
    //     },
    //     {
    //       day: this.days[1],
    //       subject: "Science",
    //       room: "Room 1",
    //     },
    //     {
    //       day: this.days[2],
    //       subject: null,
    //       room: null,
    //     },
    //     {
    //       day: this.days[3],
    //       subject: null,
    //       room: null,
    //     },
    //     {
    //       day: this.days[4],
    //       subject: null,
    //       room: null,
    //     },
    //   ]
    // },
    // {
    //   time: "07:30 AM - 08:00 AM",
    //   data: [
    //     {
    //       day: this.days[0],
    //       subject: "Science",
    //       room: "Room 1",
    //     },
    //     {
    //       day: this.days[1],
    //       subject: "Spanish",
    //       room: "Room 3",
    //     },
    //     {
    //       day: this.days[2],
    //       subject: null,
    //       room: null,
    //     },
    //     {
    //       day: this.days[3],
    //       subject: null,
    //       room: null,
    //     },
    //     {
    //       day: this.days[4],
    //       subject: null,
    //       room: null,
    //     },
    //   ]
    // },
    // {
    //   time: "08:00 AM - 08:30 AM",
    //   data: [
    //     {
    //       day: this.days[0],
    //       subject: "Physics",
    //       room: "Room 9",
    //     },
    //     {
    //       day: this.days[1],
    //       subject: "English",
    //       room: "Room 10",
    //     },
    //     {
    //       day: this.days[2],
    //       subject: null,
    //       room: null,
    //     },
    //     {
    //       day: this.days[3],
    //       subject: null,
    //       room: null,
    //     },
    //     {
    //       day: this.days[4],
    //       subject: null,
    //       room: null,
    //     },
    //   ]
    // },
  ]
  counter: number = 0;
  displayTable: boolean = false;
  timeTableStatus: any;

  constructor(
    public dialog: MatDialog,
    private Rest: ApiService
  ) { }

  ngOnInit(): void {
    this.counter = 0;
    this.loginData();
   
    // responseData.forEach((element: any, index: any) => {
    //         this.timetableData[element.institution_schedule_timeslot_id - 1].data[element.day_of_week - 1].subject = element?.schedule_lesson_details;
        
    //   });
    //   console.log(this.timetableData,"this.timetableData 11");
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
      this.getAPIData();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
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

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  getAPIData() {
    this.Rest.getWithToken('weekdays').subscribe({
      next: (response: any) => {
        if (response) {
          this.days = response?.data;
          // this.timeTableById();
          this.timeSlotById(); //just for testing after that remove this
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

  timeTableById() {
    this.Rest.getWithToken('schedules/timetables/3').subscribe({
      next: (response: any) => {
        if (response) {
          console.log(response, "response");

          this.timeSlotById();
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

  timeSlotById() {
    this.Rest.getWithToken('schedules/timeslots/1').subscribe({
      next: (response: any) => {
        if (response) {
          response?.data.forEach((element: any) => {
            let obj = {
              time: `${element?.start_time} - ${element?.end_time}`,
              data: [
                {
                  day: this.days[0],
                  subject: []
                },
                {
                  day: this.days[1],
                  subject: [],
                },
                {
                  day: this.days[2],
                  subject: [],
                },
                {
                  day: this.days[3],
                  subject: [],
                },
                {
                  day: this.days[4],
                  subject: []
                },
              ]
            }
            this.timetableData.push(obj);
          });
          this.getClassGrade();
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

  getClassGrade() {
    this.Rest.getWithToken('institutions/classes/568/grades').subscribe({
      next: (response: any) => {
        if (response) {

          this.getTimeTableStatus();
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

  getTimeTableStatus() {
    this.Rest.getWithToken('schedules/timetables/statuses').subscribe({
      next: (response: any) => {

        this.timeTableStatus = response?.data;
        this.getTimeTableLesson();
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

  getTimeTableLesson() {
    this.Rest.getWithToken('schedules/timetables/1/lessons').subscribe({
      next: (response: any) => {
        console.log(response, "response 09");
        console.log(this.timetableData, "response 09");
        let responseData = [
          {
              "id": "f3176ea4064dc47ccab85eb06a79460c0a07325bfdc3da7863dbd974031ee89b",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 1,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": 2,
              "modified": "February 11, 2020 - 07:41:12",
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:36:59",
              "schedule_lesson_details": [],
              "timeslot": {
                  "id": 1,
                  "interval": 30,
                  "order": 1,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:00:00",
                  "end_time": "07:30:00"
              }
          },
          {
              "id": "45db9b8e2fb4f8f4f06a8045741a53708a309db363193bff7f70a3d1b1401cb8",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 2,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": 2,
              "modified": "February 11, 2020 - 07:41:13",
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:41:09",
              "schedule_lesson_details": [
                  {
                      "id": 4,
                      "lesson_type": 2,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 2,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:41:28",
                      "schedule_lesson_room": {
                          "id": 4,
                          "institution_schedule_lesson_detail_id": 4,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 3,
                          "name": "Flag Raising Ceremony",
                          "institution_schedule_lesson_detail_id": 4
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 2,
                  "interval": 30,
                  "order": 2,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:30:00",
                  "end_time": "08:00:00"
              }
          },
          {
              "id": "99dfcc40ae439febf31ff7b9c58a6d9b5a2d64d0920687cc4f964bc5102897db",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 3,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:42:07",
              "schedule_lesson_details": [
                  {
                      "id": 9,
                      "lesson_type": 1,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 3,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:42:24",
                      "schedule_lesson_room": {
                          "id": 9,
                          "institution_schedule_lesson_detail_id": 9,
                          "institution_room_id": 236,
                          "institution_room": {
                              "id": 236,
                              "code": "P1002-01020101",
                              "name": "Room 1",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 85,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": 164,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "December 05, 2019 - 20:46:34",
                              "code_name": "P1002-01020101 - Room 1"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 2,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 9,
                          "institution_subject_id": 3833,
                          "institution_subject": {
                              "id": 3833,
                              "name": "Mathematics",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 91,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "baee1023-8aed-4e9b-a692-b8f325941969",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3833,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "MA",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 3,
                  "interval": 30,
                  "order": 3,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:00:00",
                  "end_time": "08:30:00"
              }
          },
          {
              "id": "4e36dd0faa173c6496d6ecfce1ca5d2b2562e30bafd7fe544d6a1f4747939674",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 4,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:42:27",
              "schedule_lesson_details": [
                  {
                      "id": 10,
                      "lesson_type": 1,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 4,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:42:37",
                      "schedule_lesson_room": {
                          "id": 10,
                          "institution_schedule_lesson_detail_id": 10,
                          "institution_room_id": 236,
                          "institution_room": {
                              "id": 236,
                              "code": "P1002-01020101",
                              "name": "Room 1",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 85,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": 164,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "December 05, 2019 - 20:46:34",
                              "code_name": "P1002-01020101 - Room 1"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 3,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 10,
                          "institution_subject_id": 3833,
                          "institution_subject": {
                              "id": 3833,
                              "name": "Mathematics",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 91,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "baee1023-8aed-4e9b-a692-b8f325941969",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3833,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "MA",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 4,
                  "interval": 30,
                  "order": 4,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:30:00",
                  "end_time": "09:00:00"
              }
          },
          {
              "id": "cba0a5efec1608356183473f0dcd27d17c7e42a64cc2d267467a3ccde686e368",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 5,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": 2,
              "modified": "February 11, 2020 - 07:50:00",
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:42:49",
              "schedule_lesson_details": [
                  {
                      "id": 30,
                      "lesson_type": 1,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 5,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:50:12",
                      "schedule_lesson_room": {
                          "id": 30,
                          "institution_schedule_lesson_detail_id": 30,
                          "institution_room_id": 236,
                          "institution_room": {
                              "id": 236,
                              "code": "P1002-01020101",
                              "name": "Room 1",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 85,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": 164,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "December 05, 2019 - 20:46:34",
                              "code_name": "P1002-01020101 - Room 1"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 12,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 30,
                          "institution_subject_id": 3836,
                          "institution_subject": {
                              "id": 3836,
                              "name": "Science",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 97,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "65db93fe-f487-42f6-aa66-485cd5af47ea",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3836,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "SCI",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 5,
                  "interval": 30,
                  "order": 5,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:00:00",
                  "end_time": "09:30:00"
              }
          },
          {
              "id": "3860870a232f29256c20c0fc0b27603c141e6ce1f498a3a22ca596ca2b3cdf55",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 6,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": 2,
              "modified": "February 11, 2020 - 07:48:12",
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:42:55",
              "schedule_lesson_details": [
                  {
                      "id": 19,
                      "lesson_type": 2,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 6,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:47:57",
                      "schedule_lesson_room": {
                          "id": 19,
                          "institution_schedule_lesson_detail_id": 19,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 8,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 19
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 6,
                  "interval": 30,
                  "order": 6,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:30:00",
                  "end_time": "10:00:00"
              }
          },
          {
              "id": "93cfe5600f26328a5d169c2bdd3172ffa291d35ff291f80802bc66ad4cbf812b",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 7,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:49:03",
              "schedule_lesson_details": [
                  {
                      "id": 25,
                      "lesson_type": 2,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 7,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:49:13",
                      "schedule_lesson_room": {
                          "id": 25,
                          "institution_schedule_lesson_detail_id": 25,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 14,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 25
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 7,
                  "interval": 30,
                  "order": 7,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:00:00",
                  "end_time": "10:30:00"
              }
          },
          {
              "id": "cd4a761dc9a49e56b9cdb26c3ff208e72c5f8506958e5f656e98b0a421519d16",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 8,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:50:13",
              "schedule_lesson_details": [
                  {
                      "id": 31,
                      "lesson_type": 1,
                      "day_of_week": 1,
                      "institution_schedule_timeslot_id": 8,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:50:24",
                      "schedule_lesson_room": {
                          "id": 31,
                          "institution_schedule_lesson_detail_id": 31,
                          "institution_room_id": 236,
                          "institution_room": {
                              "id": 236,
                              "code": "P1002-01020101",
                              "name": "Room 1",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 85,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": 164,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "December 05, 2019 - 20:46:34",
                              "code_name": "P1002-01020101 - Room 1"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 13,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 31,
                          "institution_subject_id": 3836,
                          "institution_subject": {
                              "id": 3836,
                              "name": "Science",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 97,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "65db93fe-f487-42f6-aa66-485cd5af47ea",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3836,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "SCI",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 8,
                  "interval": 30,
                  "order": 8,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:30:00",
                  "end_time": "11:00:00"
              }
          },
          {
              "id": "ee104568abf5df5cc5e8f6e5d307bb9f8b7bc0b1fdedf633c6838c5a9ed90c65",
              "day_of_week": 1,
              "institution_schedule_timeslot_id": 9,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:49:57",
              "schedule_lesson_details": [],
              "timeslot": {
                  "id": 9,
                  "interval": 30,
                  "order": 9,
                  "institution_schedule_interval_id": 1,
                  "start_time": "11:00:00",
                  "end_time": "11:30:00"
              }
          },
          {
              "id": "8f3c0900cb6e4619f0be1fdf6cb57042b816bf3023056997d49a425e5b866b97",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 1,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:40:55",
              "schedule_lesson_details": [],
              "timeslot": {
                  "id": 1,
                  "interval": 30,
                  "order": 1,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:00:00",
                  "end_time": "07:30:00"
              }
          },
          {
              "id": "e6894ec9e71ffa2f2cbe43a66d608c90fc5ebbeeb8fb7bcc15f776ecf26ac2d7",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 2,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:41:29",
              "schedule_lesson_details": [
                  {
                      "id": 5,
                      "lesson_type": 2,
                      "day_of_week": 2,
                      "institution_schedule_timeslot_id": 2,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:41:36",
                      "schedule_lesson_room": {
                          "id": 5,
                          "institution_schedule_lesson_detail_id": 5,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 4,
                          "name": "Flag Raising Ceremony",
                          "institution_schedule_lesson_detail_id": 5
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 2,
                  "interval": 30,
                  "order": 2,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:30:00",
                  "end_time": "08:00:00"
              }
          },
          {
              "id": "8839da4e63454536f6cb98c52edd7952805074fefdfcc45e492ea2602c915cd5",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 3,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:43:17",
              "schedule_lesson_details": [
                  {
                      "id": 11,
                      "lesson_type": 1,
                      "day_of_week": 2,
                      "institution_schedule_timeslot_id": 3,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:43:26",
                      "schedule_lesson_room": {
                          "id": 11,
                          "institution_schedule_lesson_detail_id": 11,
                          "institution_room_id": 237,
                          "institution_room": {
                              "id": 237,
                              "code": "P1002-01020102",
                              "name": "Room 2",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 85,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": 165,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "December 05, 2019 - 20:46:56",
                              "code_name": "P1002-01020102 - Room 2"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 4,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 11,
                          "institution_subject_id": 3836,
                          "institution_subject": {
                              "id": 3836,
                              "name": "Science",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 97,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "65db93fe-f487-42f6-aa66-485cd5af47ea",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3836,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "SCI",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 3,
                  "interval": 30,
                  "order": 3,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:00:00",
                  "end_time": "08:30:00"
              }
          },
          {
              "id": "ed4f722d57de1ffacaff9f615b0d6be2bf9ffc8e6e55c29cd70397c7ae1387eb",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 4,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:43:28",
              "schedule_lesson_details": [
                  {
                      "id": 12,
                      "lesson_type": 1,
                      "day_of_week": 2,
                      "institution_schedule_timeslot_id": 4,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:43:36",
                      "schedule_lesson_room": {
                          "id": 12,
                          "institution_schedule_lesson_detail_id": 12,
                          "institution_room_id": 237,
                          "institution_room": {
                              "id": 237,
                              "code": "P1002-01020102",
                              "name": "Room 2",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 85,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": 165,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "December 05, 2019 - 20:46:56",
                              "code_name": "P1002-01020102 - Room 2"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 5,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 12,
                          "institution_subject_id": 3836,
                          "institution_subject": {
                              "id": 3836,
                              "name": "Science",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 97,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "65db93fe-f487-42f6-aa66-485cd5af47ea",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3836,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "SCI",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 4,
                  "interval": 30,
                  "order": 4,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:30:00",
                  "end_time": "09:00:00"
              }
          },
          {
              "id": "f5df853cfe3bd4068999ca7c5b55fa0c6836b4a86d7ad731b4a3079edf02f20d",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 5,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": 2,
              "modified": "February 11, 2020 - 07:47:30",
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:43:16",
              "schedule_lesson_details": [],
              "timeslot": {
                  "id": 5,
                  "interval": 30,
                  "order": 5,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:00:00",
                  "end_time": "09:30:00"
              }
          },
          {
              "id": "827ab97992acedde345bfa172ba8cf4895185e9cd565ffbfc5f05304c19c677e",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 6,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": 2,
              "modified": "February 11, 2020 - 07:48:16",
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:43:15",
              "schedule_lesson_details": [
                  {
                      "id": 21,
                      "lesson_type": 2,
                      "day_of_week": 2,
                      "institution_schedule_timeslot_id": 6,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:48:27",
                      "schedule_lesson_room": {
                          "id": 21,
                          "institution_schedule_lesson_detail_id": 21,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 10,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 21
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 6,
                  "interval": 30,
                  "order": 6,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:30:00",
                  "end_time": "10:00:00"
              }
          },
          {
              "id": "2c74ec5bef042801c98d1b1d1b1e52c5a4e4524a3cfcd85c0d5ffbad74d92d5c",
              "day_of_week": 2,
              "institution_schedule_timeslot_id": 7,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:49:16",
              "schedule_lesson_details": [
                  {
                      "id": 26,
                      "lesson_type": 2,
                      "day_of_week": 2,
                      "institution_schedule_timeslot_id": 7,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:49:22",
                      "schedule_lesson_room": {
                          "id": 26,
                          "institution_schedule_lesson_detail_id": 26,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 15,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 26
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 7,
                  "interval": 30,
                  "order": 7,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:00:00",
                  "end_time": "10:30:00"
              }
          },
          {
              "id": "ade5aa392903732cb785f91e714f9aa4002eefd9183d394aa6f89c649d95f8e7",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 2,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:41:38",
              "schedule_lesson_details": [
                  {
                      "id": 6,
                      "lesson_type": 2,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 2,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:41:45",
                      "schedule_lesson_room": {
                          "id": 6,
                          "institution_schedule_lesson_detail_id": 6,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 5,
                          "name": "Flag Raising Ceremony",
                          "institution_schedule_lesson_detail_id": 6
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 2,
                  "interval": 30,
                  "order": 2,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:30:00",
                  "end_time": "08:00:00"
              }
          },
          {
              "id": "aeaedb510d1d46b01a6a9e7d3e5e41fffe7c5b7f69bfaa911df29463ae299662",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 3,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:43:37",
              "schedule_lesson_details": [
                  {
                      "id": 13,
                      "lesson_type": 1,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 3,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:43:50",
                      "schedule_lesson_room": {
                          "id": 13,
                          "institution_schedule_lesson_detail_id": 13,
                          "institution_room_id": 243,
                          "institution_room": {
                              "id": 243,
                              "code": "P1002-01030106",
                              "name": "Room 3",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 86,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 2,
                              "area": null,
                              "previous_institution_room_id": 215,
                              "modified_user_id": 2,
                              "modified": "December 11, 2019 - 15:35:22",
                              "created_user_id": 2,
                              "created": "December 11, 2019 - 15:31:25",
                              "code_name": "P1002-01030106 - Room 3"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 6,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 13,
                          "institution_subject_id": 3829,
                          "institution_subject": {
                              "id": 3829,
                              "name": "Social Studies",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 60,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "63eceea4-4e36-4bb3-bc1c-3ff425a63e1b",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3829,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "SSMC",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 3,
                  "interval": 30,
                  "order": 3,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:00:00",
                  "end_time": "08:30:00"
              }
          },
          {
              "id": "62784519b6d59bfcb482e828916b7d4dd5d00b11064cf3f30879a4506d0ee3db",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 4,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:43:51",
              "schedule_lesson_details": [
                  {
                      "id": 14,
                      "lesson_type": 1,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 4,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:44:00",
                      "schedule_lesson_room": {
                          "id": 14,
                          "institution_schedule_lesson_detail_id": 14,
                          "institution_room_id": 243,
                          "institution_room": {
                              "id": 243,
                              "code": "P1002-01030106",
                              "name": "Room 3",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 86,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 2,
                              "area": null,
                              "previous_institution_room_id": 215,
                              "modified_user_id": 2,
                              "modified": "December 11, 2019 - 15:35:22",
                              "created_user_id": 2,
                              "created": "December 11, 2019 - 15:31:25",
                              "code_name": "P1002-01030106 - Room 3"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 7,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 14,
                          "institution_subject_id": 3829,
                          "institution_subject": {
                              "id": 3829,
                              "name": "Social Studies",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 60,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "63eceea4-4e36-4bb3-bc1c-3ff425a63e1b",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3829,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "SSMC",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 4,
                  "interval": 30,
                  "order": 4,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:30:00",
                  "end_time": "09:00:00"
              }
          },
          {
              "id": "b075fde58fd769430a29a3d1f3a00888a13b25b04c054c69f9500329bb9f9ef4",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 5,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:50:31",
              "schedule_lesson_details": [
                  {
                      "id": 32,
                      "lesson_type": 1,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 5,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:50:43",
                      "schedule_lesson_room": {
                          "id": 32,
                          "institution_schedule_lesson_detail_id": 32,
                          "institution_room_id": 241,
                          "institution_room": {
                              "id": 241,
                              "code": "P1002-01030104",
                              "name": "Room 6",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 86,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 3,
                              "area": null,
                              "previous_institution_room_id": 213,
                              "modified_user_id": 2,
                              "modified": "December 11, 2019 - 15:37:00",
                              "created_user_id": 2,
                              "created": "December 11, 2019 - 15:29:40",
                              "code_name": "P1002-01030104 - Room 6"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 14,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 32,
                          "institution_subject_id": 3830,
                          "institution_subject": {
                              "id": 3830,
                              "name": "Expressive Arts",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 72,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "fdc52897-42b1-4750-acfb-310ce5466ed0",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3830,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "EA",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 5,
                  "interval": 30,
                  "order": 5,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:00:00",
                  "end_time": "09:30:00"
              }
          },
          {
              "id": "f845e6f5e1f05f5049222421d8511ad7707aa775dd0ab1b5ddce5ab37facff68",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 6,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:48:28",
              "schedule_lesson_details": [
                  {
                      "id": 22,
                      "lesson_type": 2,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 6,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:48:38",
                      "schedule_lesson_room": {
                          "id": 22,
                          "institution_schedule_lesson_detail_id": 22,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 11,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 22
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 6,
                  "interval": 30,
                  "order": 6,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:30:00",
                  "end_time": "10:00:00"
              }
          },
          {
              "id": "0ca52d8bbffaed01354f61d8f452bc80fd704771c737e7bb21ba8a9e74e2b05b",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 7,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:49:24",
              "schedule_lesson_details": [
                  {
                      "id": 27,
                      "lesson_type": 2,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 7,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:49:32",
                      "schedule_lesson_room": {
                          "id": 27,
                          "institution_schedule_lesson_detail_id": 27,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 16,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 27
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 7,
                  "interval": 30,
                  "order": 7,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:00:00",
                  "end_time": "10:30:00"
              }
          },
          {
              "id": "75e20e36f02a7f832e868a542fae2bd460495a4a811a5458d9fd1310a55aaa88",
              "day_of_week": 3,
              "institution_schedule_timeslot_id": 8,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:50:45",
              "schedule_lesson_details": [
                  {
                      "id": 33,
                      "lesson_type": 1,
                      "day_of_week": 3,
                      "institution_schedule_timeslot_id": 8,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:50:53",
                      "schedule_lesson_room": {
                          "id": 33,
                          "institution_schedule_lesson_detail_id": 33,
                          "institution_room_id": 241,
                          "institution_room": {
                              "id": 241,
                              "code": "P1002-01030104",
                              "name": "Room 6",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 86,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 3,
                              "area": null,
                              "previous_institution_room_id": 213,
                              "modified_user_id": 2,
                              "modified": "December 11, 2019 - 15:37:00",
                              "created_user_id": 2,
                              "created": "December 11, 2019 - 15:29:40",
                              "code_name": "P1002-01030104 - Room 6"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 15,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 33,
                          "institution_subject_id": 3830,
                          "institution_subject": {
                              "id": 3830,
                              "name": "Expressive Arts",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 72,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "fdc52897-42b1-4750-acfb-310ce5466ed0",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3830,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "EA",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 8,
                  "interval": 30,
                  "order": 8,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:30:00",
                  "end_time": "11:00:00"
              }
          },
          {
              "id": "008cd0d45678aa671ae928f83169ff292de9ece91d6297f58ae9acb8587a0b72",
              "day_of_week": 4,
              "institution_schedule_timeslot_id": 2,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:41:46",
              "schedule_lesson_details": [
                  {
                      "id": 7,
                      "lesson_type": 2,
                      "day_of_week": 4,
                      "institution_schedule_timeslot_id": 2,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:41:54",
                      "schedule_lesson_room": {
                          "id": 7,
                          "institution_schedule_lesson_detail_id": 7,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 6,
                          "name": "Flag Raising Ceremony",
                          "institution_schedule_lesson_detail_id": 7
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 2,
                  "interval": 30,
                  "order": 2,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:30:00",
                  "end_time": "08:00:00"
              }
          },
          {
              "id": "db2287eddb1b45fa3cfcde893cfc77ca4aa2954edc7da38f27e7cbe632b1c265",
              "day_of_week": 4,
              "institution_schedule_timeslot_id": 3,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:44:03",
              "schedule_lesson_details": [
                  {
                      "id": 15,
                      "lesson_type": 1,
                      "day_of_week": 4,
                      "institution_schedule_timeslot_id": 3,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:44:23",
                      "schedule_lesson_room": {
                          "id": 15,
                          "institution_schedule_lesson_detail_id": 15,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 8,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 15,
                          "institution_subject_id": 3831,
                          "institution_subject": {
                              "id": 3831,
                              "name": "Physical Education",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 82,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "2d2cafb4-2cc2-494f-a70d-746265252fa8",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3831,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "PE",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 3,
                  "interval": 30,
                  "order": 3,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:00:00",
                  "end_time": "08:30:00"
              }
          },
          {
              "id": "04fead96d5fdb7780e369eec4e6a4c4467ba1ccab07b1e110572e3cb8c4d1ea8",
              "day_of_week": 4,
              "institution_schedule_timeslot_id": 4,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:44:24",
              "schedule_lesson_details": [
                  {
                      "id": 16,
                      "lesson_type": 1,
                      "day_of_week": 4,
                      "institution_schedule_timeslot_id": 4,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:44:37",
                      "schedule_lesson_room": {
                          "id": 16,
                          "institution_schedule_lesson_detail_id": 16,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 9,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 16,
                          "institution_subject_id": 3831,
                          "institution_subject": {
                              "id": 3831,
                              "name": "Physical Education",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 82,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "2d2cafb4-2cc2-494f-a70d-746265252fa8",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3831,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "PE",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 4,
                  "interval": 30,
                  "order": 4,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:30:00",
                  "end_time": "09:00:00"
              }
          },
          {
              "id": "a1ddab6e29d1e470070ae3a55300c1c9d8abde352dbae5aed46e693f9fcd816b",
              "day_of_week": 4,
              "institution_schedule_timeslot_id": 6,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:48:41",
              "schedule_lesson_details": [
                  {
                      "id": 23,
                      "lesson_type": 2,
                      "day_of_week": 4,
                      "institution_schedule_timeslot_id": 6,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:48:50",
                      "schedule_lesson_room": {
                          "id": 23,
                          "institution_schedule_lesson_detail_id": 23,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 12,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 23
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 6,
                  "interval": 30,
                  "order": 6,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:30:00",
                  "end_time": "10:00:00"
              }
          },
          {
              "id": "331ecb0ea8585b19e6d01d8f527f0a2db4a38ad45d77cbdbf176f773f449036c",
              "day_of_week": 4,
              "institution_schedule_timeslot_id": 7,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:49:34",
              "schedule_lesson_details": [
                  {
                      "id": 28,
                      "lesson_type": 2,
                      "day_of_week": 4,
                      "institution_schedule_timeslot_id": 7,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:49:43",
                      "schedule_lesson_room": {
                          "id": 28,
                          "institution_schedule_lesson_detail_id": 28,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 17,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 28
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 7,
                  "interval": 30,
                  "order": 7,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:00:00",
                  "end_time": "10:30:00"
              }
          },
          {
              "id": "ba470a65045bcfa94d6dfa4f1f300684b9dbf999a0525c44fd304732d222954f",
              "day_of_week": 5,
              "institution_schedule_timeslot_id": 2,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:41:55",
              "schedule_lesson_details": [
                  {
                      "id": 8,
                      "lesson_type": 2,
                      "day_of_week": 5,
                      "institution_schedule_timeslot_id": 2,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:42:03",
                      "schedule_lesson_room": {
                          "id": 8,
                          "institution_schedule_lesson_detail_id": 8,
                          "institution_room_id": 365,
                          "institution_room": {
                              "id": 365,
                              "code": "P1002-01080101",
                              "name": "Parade Square",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 7,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:40:25",
                              "code_name": "P1002-01080101 - Parade Square"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 7,
                          "name": "Flag Raising Ceremony",
                          "institution_schedule_lesson_detail_id": 8
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 2,
                  "interval": 30,
                  "order": 2,
                  "institution_schedule_interval_id": 1,
                  "start_time": "07:30:00",
                  "end_time": "08:00:00"
              }
          },
          {
              "id": "a6aa8b58bffbbcc679db8afcf466d20f5da8c2b2e3192bf1c7e642ac2fb17555",
              "day_of_week": 5,
              "institution_schedule_timeslot_id": 3,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:44:39",
              "schedule_lesson_details": [
                  {
                      "id": 17,
                      "lesson_type": 1,
                      "day_of_week": 5,
                      "institution_schedule_timeslot_id": 3,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:44:51",
                      "schedule_lesson_room": {
                          "id": 17,
                          "institution_schedule_lesson_detail_id": 17,
                          "institution_room_id": 240,
                          "institution_room": {
                              "id": 240,
                              "code": "P1002-01030103",
                              "name": "Room 5",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 86,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 2,
                              "area": null,
                              "previous_institution_room_id": 212,
                              "modified_user_id": 2,
                              "modified": "December 11, 2019 - 15:36:09",
                              "created_user_id": 2,
                              "created": "December 11, 2019 - 15:28:56",
                              "code_name": "P1002-01030103 - Room 5"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 10,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 17,
                          "institution_subject_id": 3832,
                          "institution_subject": {
                              "id": 3832,
                              "name": "Creative Arts",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 83,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "e9654ff5-e623-4abd-a2e0-00f35529d60d",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3832,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "CA",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 3,
                  "interval": 30,
                  "order": 3,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:00:00",
                  "end_time": "08:30:00"
              }
          },
          {
              "id": "bb2348ca92658936ab855a850ec06a10c76d3e1ae4cd09d871caf2de672dc34f",
              "day_of_week": 5,
              "institution_schedule_timeslot_id": 4,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:44:53",
              "schedule_lesson_details": [
                  {
                      "id": 18,
                      "lesson_type": 1,
                      "day_of_week": 5,
                      "institution_schedule_timeslot_id": 4,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:45:04",
                      "schedule_lesson_room": {
                          "id": 18,
                          "institution_schedule_lesson_detail_id": 18,
                          "institution_room_id": 240,
                          "institution_room": {
                              "id": 240,
                              "code": "P1002-01030103",
                              "name": "Room 5",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 0,
                              "comment": "",
                              "room_type_id": 1,
                              "room_status_id": 1,
                              "institution_floor_id": 86,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 2,
                              "area": null,
                              "previous_institution_room_id": 212,
                              "modified_user_id": 2,
                              "modified": "December 11, 2019 - 15:36:09",
                              "created_user_id": 2,
                              "created": "December 11, 2019 - 15:28:56",
                              "code_name": "P1002-01030103 - Room 5"
                          }
                      },
                      "schedule_non_curriculum_lesson": null,
                      "schedule_curriculum_lesson": {
                          "id": 11,
                          "code_only": 0,
                          "institution_schedule_lesson_detail_id": 18,
                          "institution_subject_id": 3832,
                          "institution_subject": {
                              "id": 3832,
                              "name": "Creative Arts",
                              "no_of_seats": null,
                              "total_male_students": 12,
                              "total_female_students": 25,
                              "institution_id": 6,
                              "education_grade_id": 87,
                              "education_subject_id": 83,
                              "academic_period_id": 29,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 8805,
                              "created": "January 08, 2020 - 02:28:30",
                              "teachers": [],
                              "classes": [
                                  {
                                      "id": 496,
                                      "name": "Primary 1-A",
                                      "class_number": 1,
                                      "capacity": 50,
                                      "total_male_students": 12,
                                      "total_female_students": 25,
                                      "staff_id": 8815,
                                      "institution_shift_id": 172,
                                      "institution_id": 6,
                                      "institution_unit_id": null,
                                      "institution_course_id": null,
                                      "academic_period_id": 29,
                                      "modified_user_id": 1,
                                      "modified": "August 12, 2021 - 04:48:11",
                                      "created_user_id": 8805,
                                      "created": "January 08, 2020 - 02:28:30",
                                      "_joinData": {
                                          "id": "e9654ff5-e623-4abd-a2e0-00f35529d60d",
                                          "status": 1,
                                          "institution_class_id": 496,
                                          "institution_subject_id": 3832,
                                          "modified_user_id": null,
                                          "modified": null,
                                          "created_user_id": 8805,
                                          "created": "2020-01-08T02:28:30+00:00"
                                      }
                                  }
                              ],
                              "education_subject_code": "CA",
                              "class_name": "Primary 1-A"
                          }
                      }
                  }
              ],
              "timeslot": {
                  "id": 4,
                  "interval": 30,
                  "order": 4,
                  "institution_schedule_interval_id": 1,
                  "start_time": "08:30:00",
                  "end_time": "09:00:00"
              }
          },
          {
              "id": "07f4e40d8f32a03e753b129956006f3c363e7b849f4239e443267e5e1252a123",
              "day_of_week": 5,
              "institution_schedule_timeslot_id": 6,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:48:51",
              "schedule_lesson_details": [
                  {
                      "id": 24,
                      "lesson_type": 2,
                      "day_of_week": 5,
                      "institution_schedule_timeslot_id": 6,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:49:00",
                      "schedule_lesson_room": {
                          "id": 24,
                          "institution_schedule_lesson_detail_id": 24,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 13,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 24
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 6,
                  "interval": 30,
                  "order": 6,
                  "institution_schedule_interval_id": 1,
                  "start_time": "09:30:00",
                  "end_time": "10:00:00"
              }
          },
          {
              "id": "e4955e78f77d4132723eee64147a861d5e8fe173d75e323867ccb5e83c3e7d61",
              "day_of_week": 5,
              "institution_schedule_timeslot_id": 7,
              "institution_schedule_timetable_id": 1,
              "modified_user_id": null,
              "modified": null,
              "created_user_id": 2,
              "created": "February 11, 2020 - 07:49:44",
              "schedule_lesson_details": [
                  {
                      "id": 29,
                      "lesson_type": 2,
                      "day_of_week": 5,
                      "institution_schedule_timeslot_id": 7,
                      "institution_schedule_timetable_id": 1,
                      "modified_user_id": null,
                      "modified": null,
                      "created_user_id": 2,
                      "created": "February 11, 2020 - 07:49:52",
                      "schedule_lesson_room": {
                          "id": 29,
                          "institution_schedule_lesson_detail_id": 29,
                          "institution_room_id": 366,
                          "institution_room": {
                              "id": 366,
                              "code": "P1002-01080102",
                              "name": "Canteen",
                              "start_date": "January 01, 2020",
                              "start_year": 2020,
                              "end_date": "December 31, 2020",
                              "end_year": 2020,
                              "accessibility": 1,
                              "comment": "",
                              "room_type_id": 8,
                              "room_status_id": 1,
                              "institution_floor_id": 134,
                              "institution_id": 6,
                              "academic_period_id": 29,
                              "infrastructure_condition_id": 1,
                              "area": null,
                              "previous_institution_room_id": null,
                              "modified_user_id": null,
                              "modified": null,
                              "created_user_id": 2,
                              "created": "February 11, 2020 - 07:46:27",
                              "code_name": "P1002-01080102 - Canteen"
                          }
                      },
                      "schedule_non_curriculum_lesson": {
                          "id": 18,
                          "name": "Recess",
                          "institution_schedule_lesson_detail_id": 29
                      },
                      "schedule_curriculum_lesson": null
                  }
              ],
              "timeslot": {
                  "id": 7,
                  "interval": 30,
                  "order": 7,
                  "institution_schedule_interval_id": 1,
                  "start_time": "10:00:00",
                  "end_time": "10:30:00"
              }
          }
      ]
      responseData.forEach((element: any, index: any) => {
          // if(element.institution_schedule_timeslot_id == )
        //   this.timetableData[element.institution_schedule_timeslot_id - 1].data[element.day_of_week-1].subject.push({subject: `Subject ${index}`});
          // console.log(this.timetableData[element.institution_schedule_timeslot_id - 1].data[element.institution_schedule_timetable_id],"09");
          this.timetableData[element.institution_schedule_timeslot_id - 1].data[element.day_of_week - 1].subject = element?.schedule_lesson_details;
          
          // let newData = this.timetableData[element.institution_schedule_timetable_id - 1].subject[element.institution_schedule_timeslot_id - 1];
          // console.log(newData,"newData");
          
        });
        this.displayTable = true;
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

  backToData() {
  }

  overViewData() { }

  downloadClick() { }
  openDialog(indexOfRow: number, indexOfDay: number, schedule: any) {
    console.log(indexOfRow, indexOfDay, "index of day", schedule);

    const dialogRef = this.dialog.open(DialogOpenComponent, {
      disableClose: true,
      width: '30%'
    }).afterClosed().subscribe((res) => {
      console.log(res, "res Data");
      if (res) {
        console.log(res,"dialog res");
        this.timetableData[indexOfRow].data[indexOfDay].subject.push(res);
        
        // if (res.typeId == 1) {
        //   this.timetableData[indexOfRow].data[indexOfDay].subject = res.subject;
        //   this.timetableData[indexOfRow].data[indexOfDay].room = res.room;
        // } else {
        //   this.timetableData[indexOfRow].data[indexOfDay].subject = res.name;
        //   this.timetableData[indexOfRow].data[indexOfDay].room = res.room;
        // }

        this.addLesson(res);
      }
    });
  }

  addLesson(data: any) {
    let obj = {
      "day_of_week": 1,
      "institution_schedule_timeslot_id": 31,
      "institution_schedule_timetable_id": 3,
      "lesson_type": 2,
      "schedule_non_curriculum_lesson": {
        "name": "dfg"
      },
      "schedule_lesson_room": {
        "institution_schedule_lesson_detail_id": "1",
        "institution_room_id": JSON.stringify(data?.roomId)
      },
      "action_type": "default",
      "institution_id": 6
    }

    this.Rest.postWithToken('schedules/timetables/lessons', obj).subscribe({
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
    })
  }

  onRemoveClick(indexOfRow: number, indexOfDay: number, rowIndex: number) {
    this.timetableData[indexOfRow].data[indexOfDay].subject.splice(rowIndex, 1);
    // this.Rest.deleteWithToken(`institutions/6/schedules/timetables/lessons/95`).subscribe({
    //   next: (res: any) => {
    //     console.log(res, "delete res");
    //     if (res.message == 'Successful.') {
    //       alert();
    //     }
    //   },
    //   error: (error: any) => {
    //     if (error) {
    //       if (error.message == "Token has expired") {
    //         localStorage.removeItem("loginToken");
    //         this.loginData();
    //       }
    //     }
    //   }
    // })
    console.log(this.timetableData)
  }

  closeDialog() {
    setTimeout(() => {
      this.dialog.closeAll();      
    }, 0);
  }

  resetMouseOver() {
    this.isMouseOver = false;
    this.currentRowIndex = null;
    this.currentCellIndex = null;
  }

  setMouseOver(rowIndex: number, cellIndex: number) {
    this.isMouseOver = true
    this.currentRowIndex = rowIndex;
    this.currentCellIndex = cellIndex;
  }
}
