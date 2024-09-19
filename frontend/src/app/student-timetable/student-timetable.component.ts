import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { ApiService } from '../api.service';
import { KdSplitterEvent } from 'openemis-styleguide-lib';

@Component({
    selector: 'app-student-timetable',
    templateUrl: './student-timetable.component.html',
    styleUrls: ['./student-timetable.component.css']
})
export class StudentTimetableComponent implements OnInit {
    displayLoading: boolean = false;
    public showFullWidth: boolean = true;

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
    selectLesson: any = '';
    lessonType: any = [];
    addNewLesson: any = [];
    institutionRoomData: any[] = [];
    institutionSubject: any = [];
    showDropdownErrorMsg: boolean = false;
    showTextErrorMsg: boolean = false;
    indexOfRow: any;
    indexOfDay: any;
    displayLessons: boolean = false;
    education_grade_name: any;

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
            'key': 'text',
            'label': 'Academic Period:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '2020',
            'readonly': true
        }
    ]

    public term: Array<any> = [
        {
            'key': 'text',
            'label': 'Term:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': 'Semester 1',
            'readonly': true
        }
    ]

    public status: Array<any> = [
        {
            'key': 'dropdown',
            'label': 'Status:',
            'visible': true,
            'required': false,
            'controlType': 'dropdown',
            'options': []
        }
    ]

    public name: Array<any> = [
        {
            'key': 'text',
            'label': 'Name:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': 'P1A',
            'readonly': false
        },
    ]

    public grade: Array<any> = [
        {
            'key': 'text',
            'label': 'Grade:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': 'Primary 1',
            'readonly': true
        },
    ]

    public class: Array<any> = [
        {
            'key': 'text',
            'label': 'Class:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': 'Primary 1-A',
            'readonly': true
        }
    ]

    public interval: Array<any> = [
        {
            'key': 'text',
            'label': 'Interval:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': 'APS Morning Shift',
            'readonly': true
        }
    ]

    constructor(
        public dialog: MatDialog,
        private Rest: ApiService,
        private _kdSplitterEvent: KdSplitterEvent
    ) { }

    ngOnInit(): void {
        setTimeout(() => {
            this._kdSplitterEvent.toggleSubPane(false);
        }, 0);
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
                    this.getLessonType();
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

    getLessonType() {
        this.Rest.getWithToken('schedules/lessons/types').subscribe({
            next: (response: any) => {
                response?.data.forEach(element => {
                    if (element.id) {
                        let obj = {
                            id: element.id,
                            name: element.name
                        }
                        this.lessonType.push(obj);
                    }
                });
                this.lessonType.unshift({ id: '', name: '--Select--' });
                console.log(this.lessonType, "lessonType");
            },
            error: (error: any) => {
                if (error) {
                    if (error.message == "Token has expired") {
                        localStorage.removeItem("loginToken");
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
        this.Rest.getWithToken('institutions/classes/496/grades').subscribe({
            next: (response: any) => {
                if (response) {
                    console.log(response, "response class grades");
                    if (response) {
                        this.education_grade_name = response?.data[0]?.education_grades?.name;
                    }
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
                console.log(this.timeTableStatus, "timeTableStatus");
                let dataArray = [];
                this.timeTableStatus.forEach((element: any) => {
                    let obj = {
                        key: element.id,
                        value: element.name
                    }
                    dataArray.push(obj);
                });
                let academic_perod = this.status;
                academic_perod[0].options = dataArray;
                this.status = [...academic_perod];

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
                response?.data.forEach((element: any, index: any) => {
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

    overViewData() {
        this._kdSplitterEvent.toggleSubPane(true);
        this.showFullWidth = false;
        this.displayLessons = false;
    }

    downloadClick() { }
    // openDialog(indexOfRow: number, indexOfDay: number, schedule: any) {
    //     console.log(indexOfRow, indexOfDay, "index of day", schedule);

    //     const dialogRef = this.dialog.open(DialogOpenComponent, {
    //         disableClose: true,
    //         width: '30%'
    //     }).afterClosed().subscribe((res) => {
    //         console.log(res, "res Data");
    //         if (res) {
    //             console.log(res, "dialog res");
    //             this.timetableData[indexOfRow].data[indexOfDay].subject.push(res);
    //         }
    //     });
    // }

    // addLesson(data: any) {
    //     let obj = {
    //         "day_of_week": 1,
    //         "institution_schedule_timeslot_id": 31,
    //         "institution_schedule_timetable_id": 3,
    //         "lesson_type": 2,
    //         "schedule_non_curriculum_lesson": {
    //             "name": "dfg"
    //         },
    //         "schedule_lesson_room": {
    //             "institution_schedule_lesson_detail_id": "1",
    //             "institution_room_id": JSON.stringify(data?.roomId)
    //         },
    //         "action_type": "default",
    //         "institution_id": 6
    //     }

    //     this.Rest.postWithToken('schedules/timetables/lessons', obj).subscribe({
    //         next: (res: any) => {
    //             console.log(res, "res");
    //         },
    //         error: (error: any) => {
    //             if (error) {
    //                 if (error.message == "Token has expired") {
    //                     localStorage.removeItem("loginToken");
    //                     this.loginData();
    //                 }
    //             }
    //         }
    //     })
    // }

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

    hideSubContent(): void {
        this._kdSplitterEvent.toggleSubPane(false);
        this.showFullWidth = true;
    }

    showSubContent(indexOfElement: any, innnerIndex: any): void {
        this._kdSplitterEvent.toggleSubPane(true);
        this.showFullWidth = false;
        this.displayLessons = true;
        console.log(indexOfElement + 1, "indexOfElement", innnerIndex + 1);

        this.indexOfRow = indexOfElement;
        this.indexOfDay = innnerIndex;
        this.addNewLesson = [];
        this.selectLesson = '';
    }

    addLessonData() {
        if (this.selectLesson == 2) {
            this.addNewLesson.push({ 'type': 'nonCurriculum', 'subject': '', 'room': '' });
        } else if (this.selectLesson == 1) {
            this.addNewLesson.push({ 'type': 'curriculum', 'subject': '', 'room': '' });
        }
        this.selectLesson = '';
        this.getInstitutionRooms();
        console.log(this.addNewLesson, "this.addNewLesson");
    }

    removeData(index: any) {
        this.addNewLesson.splice(index, 1);
    }

    getInstitutionRooms() {
        this.Rest.getWithToken('institutions/6/academicperiods/32/rooms').subscribe({
            next: (response: any) => {
                if (response) {
                    this.institutionRoomData = [];
                    response?.data.forEach((element: any) => {
                        let obj = {
                            id: element.id,
                            name: element.name
                        }
                        this.institutionRoomData.push(obj);
                    });
                    this.institutionRoomData.unshift({ id: '', name: 'Select room' });
                    this.getInstitutionSubject();
                }
            },
            error: (error: any) => {
                if (error) {
                    if (error.message == "Token has expired") {
                        localStorage.removeItem("loginToken");
                    }
                }
            }
        })
    }

    getInstitutionSubject() {
        this.Rest.getWithToken(`institutions/classes/496/subjects`).subscribe({
            next: (response: any) => {
                if (response) {
                    response?.data.forEach((element: any) => {
                        let obj = {
                            id: element?.institution_subject?.id,
                            name: element?.institution_subject?.name
                        }
                        this.institutionSubject.push(obj);
                    });
                    this.institutionSubject.unshift({ id: '', name: 'Select Subject' });
                }
            },
            error: (error: any) => {
                if (error) {
                    if (error.message == "Token has expired") {
                        localStorage.removeItem("loginToken");
                    }
                }
            }
        })
    }

    onSubjectChanged(pickSubject: HTMLSelectElement, index: any) {
        this.addNewLesson[index].subject = pickSubject.value;
        this.showDropdownErrorMsg = false;
    }

    onRoomSelect(roomSelect: HTMLSelectElement, index: any) {
        this.addNewLesson[index].room = roomSelect.value;
    }

    onRoomSelectNonCurriculum(roomSelect: HTMLSelectElement, index: any) {
        this.addNewLesson[index].room = roomSelect.value;
    }

    onNonCurriculumInput(nonCurriculumName: HTMLInputElement, index: any) {
        this.addNewLesson[index].subject = nonCurriculumName.value;
        this.showTextErrorMsg = false;
    }

    onAddClick(index: any, status: any) {
        console.log(this.addNewLesson[index], "this.addNewLesson[index]");

        if (status == 'curriculum' && (this.addNewLesson[index].subject == '' || this.addNewLesson[index].subject == undefined)) {
            this.showDropdownErrorMsg = true;
        } else if (status == 'nonCurriculum' && (this.addNewLesson[index].subject == '' || this.addNewLesson[index].subject == undefined)) {
            this.showTextErrorMsg = true;
        }
        else {
            // this.dialogRef.close(this.addNewLesson[index]);
            console.log(this.addNewLesson[index], "this.addNewLesson[index]");

            this.timetableData[this.indexOfRow].data[this.indexOfDay].subject.push(this.addNewLesson[index]);
            let obj = {};
            if (this.addNewLesson[index].type == "curriculum") {
                obj = {
                    "day_of_week": this.indexOfDay + 1,
                    "institution_schedule_timeslot_id": this.indexOfRow + 1,
                    "institution_schedule_timetable_id": 1,
                    "lesson_type": 1,
                    "schedule_curriculum_lesson": {
                        "code_only": null,
                        "institution_subject_id": this.addNewLesson[index].subject
                    },
                    "schedule_lesson_room": {
                        "institution_schedule_lesson_detail_id": 1,
                        "institution_room_id": this.addNewLesson[index].room
                    },
                    "action_type": "default",
                    "institution_id": 6
                }
            } else {
                obj = {
                    "day_of_week": this.indexOfDay + 1,
                    "institution_schedule_timeslot_id": this.indexOfRow + 1,
                    "institution_schedule_timetable_id": 1,
                    "lesson_type": 2,
                    "schedule_non_curriculum_lesson": {
                        "name": this.addNewLesson[index]?.subject
                    },
                    "schedule_lesson_room": {
                        "institution_schedule_lesson_detail_id": 1,
                        "institution_room_id": this.addNewLesson[index]?.room
                    },
                    "action_type": "default",
                    "institution_id": 6
                }
            }


            this.Rest.postWithToken(`schedules/timetables/lessons`, obj).subscribe({
                next: (response: any) => {
                    if (response) {
                        console.log(response, "respose Add timetable");
                        this.getTimeTableLesson();
                    }
                },
                error: (error: any) => {
                    if (error) {
                        if (error.message == "Token has expired") {
                            localStorage.removeItem("loginToken");
                        }
                    }
                }
            })

        }
    }

    _submitEvent(event: any) {

    }
}
