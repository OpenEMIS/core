import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InternalSearchComponent } from './internal-search.component';

describe('InternalSearchComponent', () => {
  let component: InternalSearchComponent;
  let fixture: ComponentFixture<InternalSearchComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InternalSearchComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(InternalSearchComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
