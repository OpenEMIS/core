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

        this.displayTable = true;
        this.getSchedulesTimetableLesson();
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

  getSchedulesTimetableLesson(){
    this.Rest.getWithToken('schedules/timetables/1/lessons').subscribe({
      next: (response: any) => {
        console.log(response,"response Topaaa");
        
      
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
