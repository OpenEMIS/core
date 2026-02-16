import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StudentAttendanceArchiveComponent } from './student-attendance-archive.component';

describe('StudentAttendanceArchiveComponent', () => {
  let component: StudentAttendanceArchiveComponent;
  let fixture: ComponentFixture<StudentAttendanceArchiveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StudentAttendanceArchiveComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StudentAttendanceArchiveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
