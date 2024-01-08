import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WorkbenchComponent } from './workbench.component';

describe('WorkbenchComponent', () => {
  let component: WorkbenchComponent;
  let fixture: ComponentFixture<WorkbenchComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WorkbenchComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(WorkbenchComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
