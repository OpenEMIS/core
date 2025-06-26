import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { ApiService } from '../api.service';
import { IDynamicFormApi, KdAlertEvent, KdSplitterEvent } from 'openemis-styleguide-lib';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

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
                this.downloadData();
            }
        }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: "",
        searchBtn: false,
        searchEvent: ['change', 'keyup']
    }

    isMouseOver: boolean = false;
    currentRowIndex: number | null = null;
    currentCellIndex: number | null = null;
    public days = [];
    public timetableData: Array<any> = []
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
    academicPeriodApi: IDynamicFormApi = {};
    termApi: IDynamicFormApi = {};
    nameApi: IDynamicFormApi = {};
    gradeApi: IDynamicFormApi = {};
    classApi: IDynamicFormApi = {};
    intervalApi: IDynamicFormApi = {};
    themeArray = DEFAULT_TEMPLATE_THEME;

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
            'key': 'academic_period',
            'label': 'Academic Period:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '',
            'readonly': true
        }
    ]

    public term: Array<any> = [
        {
            'key': 'term',
            'label': 'Term:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '',
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
            'key': 'name',
            'label': 'Name:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '',
            'readonly': false
        },
    ]

    public grade: Array<any> = [
        {
            'key': 'grade',
            'label': 'Grade:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '',
            'readonly': true
        },
    ]

    public class: Array<any> = [
        {
            'key': 'class',
            'label': 'Class:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '',
            'readonly': true
        }
    ]

    public interval: Array<any> = [
        {
            'key': 'interval',
            'label': 'Interval:',
            'visible': true,
            'required': false,
            'controlType': 'text',
            'type': 'text',
            'placeholder': 'Text input',
            'value': '',
            'readonly': true
        }
    ]
    academic_period_id: any;
    institution_class_id: any;
    institution_id: any;
    timetable_id: any;
    institution_name: any = '';
    timetable_name: string = '';

    constructor(
        public dialog: MatDialog,
        private Rest: ApiService,
        private _kdSplitterEvent: KdSplitterEvent,
        private _kdAlertEvent: KdAlertEvent
    ) { }

    ngOnInit(): void {
        setTimeout(() => {
            this._kdSplitterEvent.toggleSubPane(false);
        }, 0);
        this.counter = 0;
        this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
        // this.institution_id = 6; //need to comment
        this.timetable_id = JSON.parse(localStorage.getItem("timetable_id"));
        // this.timetable_id = 1; //need to comment
        this.institution_name = localStorage.getItem("institutionName");
        this.pageheader.pageheaderText = `${this.institution_name} - Schedule Timetable`
        this.loginData();
    }

    loginData() {
        this.Rest.setSession();
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
                decodedPassword = decodedPassword.replace(/^"(.*)"$/, '$1');
                let cleanedStr = decodedPassword.replace(/[\[\]"]/g, '');
                console.log(cleanedStr, "cleanedStr");
                if (userName && cleanedStr) {
                    this.loginApi(userName, cleanedStr);
                } else {
                    this.removeSession();
                }
            }
        } else {
            this.setTheme();
            this.getAPIData();
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
                    this.timeTableById();
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
        this.Rest.getWithToken(`schedules/timetables/${this.timetable_id}`).subscribe({
            next: (response: any) => {
                if (response) {
                    console.log(response, "response");
                    if (response) {
                        this.timetable_name = response?.data?.name;
                        this.academic_period_id = response?.data?.academic_period_id;
                        this.institution_class_id = response?.data?.institution_class_id;
                        this.timeSlotById();
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
        })
    }

    timeSlotById() {
        this.Rest.getWithToken(`schedules/timeslots/${this.timetable_id}`).subscribe({
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
        this.Rest.getWithToken(`institutions/classes/${this.institution_class_id}/grades`).subscribe({
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
        this.Rest.getWithToken(`schedules/timetables/${this.timetable_id}/lessons`).subscribe({
            next: (response: any) => {
                response?.data.forEach((element: any, index: any) => {
                    this.timetableData[element.institution_schedule_timeslot_id - 1].data[element.day_of_week - 1].subject = element?.schedule_lesson_details;
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
        this.Rest.getWithToken(`schedule/timetable-overview?limit=10&page=1&academic_period_id=${this.academic_period_id}&institution_id=${this.institution_id}&institution_class_id=${this.institution_class_id}&institution_schedule_term_id=${this.timetable_id}`).subscribe({
            next: (response: any) => {
                console.log(response.data.data[0], "response");
                if (response.data.data[0]) {
                    let responseData = response.data.data[0];
                    // this.timetable_name = responseData.name ? responseData.name : 'NA';
                    this.academicPeriodApi.setProperty('academic_period', 'value', responseData.academic_period_name ? responseData.academic_period_name : 'NA');
                    this.termApi.setProperty('term', 'value', responseData.schedule_term ? responseData.schedule_term : 'NA')
                    this.nameApi.setProperty('name', 'value', responseData.name ? responseData.name : 'NA');
                    this.gradeApi.setProperty('grade', 'value', responseData.institution_grade_name ? responseData.institution_grade_name : 'NA');
                    this.classApi.setProperty('class', 'value', responseData.institution_class_name ? responseData.institution_class_name : 'NA');
                    this.intervalApi.setProperty('interval', 'value', responseData.institution_schedule_interval_name ? responseData.institution_schedule_interval_name : 'NA');
                }
            },
            error: (error) => {
                console.log(error, "error");

            }
        })
    }

    downloadData() {
        this.Rest.getItemExport(`schedule/timetable-download?timetable_id=${this.timetable_id}`).subscribe({
            next: (response: any) => {
                if (response) {
                    let url = window.URL.createObjectURL(response);
                    let a = document.createElement('a');
                    document.body.appendChild(a);
                    a.setAttribute('style', 'display: none');
                    a.href = url;
                    a.download = response.filename || 'Timetable';
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                }
            },
            error: (error) => {
                console.log(error, "error");
            }
        })
    }

    onRemoveClick(event: any, indexOfRow: number, indexOfDay: number, rowIndex: number, curriculumData: any) {
        event.stopPropagation();
        console.log(curriculumData, "curriculumData");
        let dataId;
        if (curriculumData?.schedule_curriculum_lesson) {
            dataId = curriculumData?.schedule_curriculum_lesson?.institution_schedule_lesson_detail_id;
        } else {
            dataId = curriculumData?.schedule_non_curriculum_lesson?.institution_schedule_lesson_detail_id;
        }
        this.Rest.deleteWithToken(`institutions/${this.institution_id}/schedules/timetables/lessons/${dataId}`).subscribe({
            next: (res: any) => {
                console.log(res, "delete res");
                this.timetableData[indexOfRow].data[indexOfDay].subject.splice(rowIndex, 1);
                if (res.message == 'Successful.') {
                    let toasterConfig: any = {
                        title: 'Record deleted successfully!',
                        showCloseButton: true,
                        tapToDismiss: true,
                    };

                    this._kdAlertEvent.info(toasterConfig);
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
        this.Rest.getWithToken(`institutions/${this.institution_id}/academicperiods/${this.academic_period_id}/rooms`).subscribe({
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
        this.Rest.getWithToken(`institutions/classes/${this.institution_class_id}/subjects`).subscribe({
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
                    "institution_schedule_timetable_id": this.timetable_id,
                    "lesson_type": 1,
                    "schedule_curriculum_lesson": {
                        "code_only": null,
                        "institution_subject_id": this.addNewLesson[index].subject
                    },
                    "schedule_lesson_room": {
                        "institution_schedule_lesson_detail_id": this.timetable_id,
                        "institution_room_id": this.addNewLesson[index].room
                    },
                    "action_type": "default",
                    "institution_id": this.institution_id
                }
            } else {
                obj = {
                    "day_of_week": this.indexOfDay + 1,
                    "institution_schedule_timeslot_id": this.indexOfRow + 1,
                    "institution_schedule_timetable_id": this.timetable_id,
                    "lesson_type": 2,
                    "schedule_non_curriculum_lesson": {
                        "name": this.addNewLesson[index]?.subject
                    },
                    "schedule_lesson_room": {
                        "institution_schedule_lesson_detail_id": this.timetable_id,
                        "institution_room_id": this.addNewLesson[index]?.room
                    },
                    "action_type": "default",
                    "institution_id": this.institution_id
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

    _submitEvent(event: any, key?: string) {
        // console.log(key, event)
        let data = {};
        data[key] = event.target.value;
        // console.log(data)
        this.Rest.putWithToken(`institution-schedule-timetables/${this.timetable_id}`, data, true).subscribe({
            next: (response: any) => {
                // console.log(response)
                if(key == 'name'){
                    this.timetable_name = response?.data?.name;
                }
                let toasterConfig: any = {
                    title: response.message,
                    showCloseButton: true,
                    tapToDismiss: true,
                };
        
                this._kdAlertEvent.success(toasterConfig);
            },
            error: (error: any) => {
                if (error) {
                    if (error.message == "Token has expired") {
                        localStorage.removeItem("loginToken");
                        this.loginData();
                    }
                    let toasterConfig: any = {
                        title: 'Something went wrong, Please try again later',
                        showCloseButton: true,
                        tapToDismiss: true,
                    };
            
                    this._kdAlertEvent.warn(toasterConfig);
                }
            }
        })
    }
}
