import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StudentMealImportComponent } from './student-meal-import.component';

describe('StudentMealImportComponent', () => {
  let component: StudentMealImportComponent;
  let fixture: ComponentFixture<StudentMealImportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StudentMealImportComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StudentMealImportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
