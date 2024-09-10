import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DialogOpenComponent } from './dialog-open.component';

describe('DialogOpenComponent', () => {
  let component: DialogOpenComponent;
  let fixture: ComponentFixture<DialogOpenComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DialogOpenComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DialogOpenComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
