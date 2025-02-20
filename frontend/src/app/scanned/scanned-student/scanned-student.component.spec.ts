import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ScannedStudentComponent } from './scanned-student.component';

describe('ScannedStudentComponent', () => {
  let component: ScannedStudentComponent;
  let fixture: ComponentFixture<ScannedStudentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ScannedStudentComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ScannedStudentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
