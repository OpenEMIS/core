import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-summary',
  templateUrl: './summary.component.html',
  styleUrls: ['./summary.component.css']
})
export class SummaryComponent implements OnInit {

  _viewQuestion = [
    { key: 'information', label: 'Information', visible: true, controlType: 'section' },
    {
      'key': 'profile_photo',
            'label': 'Photo Content',
            'visible': true,
            'required': false,
            'controlType': 'file-input',
            'config': {
                'preview': true,
                'fileType': 'image',
                'leftToolbar': false,
                'imageConfig': {
                    type: 'student',
                    height: '150',
                    width: '150',
                    isExpandable: false,
                    title: 'Tony_chan.jpg'
                },
            },
            'value': {
                'data': 'Thisisthetestdataoftonychanwithnametongchanjpg',
                'extension': 'jpg',
                'filename': 'Tony_chan.jpg',
                'src': 'data:image/png;base64,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'
            }
    },
    {
      'value': 1472460657,
      'key': 'openemis_no',
      'visible': true,
      'label': 'Openemis No.',
      'type': 'string'
    },
    {
      'key': 'first_name',
      'label': 'First Name',
      'visible': true,
      'value': 'Administrator',
    },
    {
      'key': 'middle_name',
      'label': 'Middle Name',
      'visible': true,
      'controlType': 'text',
      'value': 'Admin',
    },
    {
      'key': 'third_name',
      'label': 'Third Name',
      'visible': true,
      'controlType': 'text',
      'type': 'string',
      'value': 'Super',
    },
    {
      'key': 'last_name',
      'label': 'Last Name',
      'visible': true,
      'controlType': 'text',
      'value': 'User',
    },
    {
      'key': 'preferred_name',
      'label': 'Preferred Name',
      'visible': true,
      'controlType': 'text',
      'value': 'Test User'
    },
    {
      'key': 'gender',
      'label': 'Gender',
      'visible': true,
      'controlType': 'text',
      'value': 'Male'
    },
    {
      'key': 'dob',
      'label': 'Date of Birth',
      'visible': true,
      'controlType': 'text',
      'value': '01-01-1995'
    },
    {
      'key': 'email',
      'label': 'Email',
      'visible': true,
      'controlType': 'text',
      'value': 'test@mailinator.com'
    },
    { key: 'identities_nationalities', label: 'Identities / Nationalities', visible: true, controlType: 'section' },
    {
      'key': 'details',
      'label': 'Details',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'location', label: 'Location', visible: true, controlType: 'section' },
    {
      'key': 'address',
      'label': 'Address',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    {
      'key': 'postal_code',
      'label': 'Postal Code',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'address_area', label: 'Address Area', visible: true, controlType: 'section' },
    {
      'key': 'addressArea',
      'label': 'Address Area',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'birthplace_area', label: 'Birthplace Area', visible: true, controlType: 'section' },
    {
      'key': 'birthplaceArea',
      'label': 'Birthplace Area',
      'visible': true,
      'controlType': 'text',
      'value': ''
    },
    { key: 'other_information', label: 'Other Information', visible: true, controlType: 'section' },
    {
      'key': 'modified_by',
      'label': 'Modified By',
      'visible': true,
      'controlType': 'text',
      'value': 'System Administrator'
    },
    {
      'key': 'modified_on',
      'label': 'Modified On',
      'visible': true,
      'controlType': 'text',
      'value': '2024-01-11 10:06:16'
    },
    {
      'key': 'created_by',
      'label': 'Created By',
      'visible': true,
      'controlType': 'text',
      'value': 'System Administrator'
    },
    {
      'key': 'created_on',
      'label': 'Created On',
      'visible': true,
      'controlType': 'text',
      'value': '2024-01-11 10:06:16'
    },
  ]

  constructor() { }

  ngOnInit(): void {
  }

}
