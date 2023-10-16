<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ConfigItemOptionsFixture extends TestFixture
{
    public $import = ['table' => 'config_item_options'];
    public $records = [
        [
            'id' => '1',
            'option_type' => 'date_format',
            'option' => "date('Y-n-j')",
            'value' => 'Y-m-d',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '2',
            'option_type' => 'date_format',
            'option' => "date('j-M-Y')",
            'value' => 'd-M-Y',
            'order' => '2',
            'visible' => '1'
        ],
        [
            'id' => '3',
            'option_type' => 'date_format',
            'option' => "date('j-n-Y')",
            'value' => 'd-m-Y',
            'order' => '3',
            'visible' => '1'
        ],
        [
            'id' => '4',
            'option_type' => 'date_format',
            'option' => "date('j/n/Y')",
            'value' => 'd/m/Y',
            'order' => '4',
            'visible' => '1'
        ],
        [
            'id' => '5',
            'option_type' => 'date_format',
            'option' => "date('n/d/Y')",
            'value' => 'm/d/Y',
            'order' => '5',
            'visible' => '1'
        ],
        [
            'id' => '6',
            'option_type' => 'date_format',
            'option' => "date('F j, Y')",
            'value' => 'F d, Y',
            'order' => '6',
            'visible' => '1'
        ],
        [
            'id' => '7',
            'option_type' => 'date_format',
            'option' => "date('jS F Y')",
            'value' => 'dS F Y',
            'order' => '7',
            'visible' => '1'
        ],
        [
            'id' => '10',
            'option_type' => 'authentication_type',
            'option' => 'Local',
            'value' => 'Local',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '11',
            'option_type' => 'authentication_type',
            'option' => 'LDAP',
            'value' => 'LDAP',
            'order' => '2',
            'visible' => '0'
        ],
        [
            'id' => '12',
            'option_type' => 'language',
            'option' => 'Arabic',
            'value' => 'ar',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '13',
            'option_type' => 'language',
            'option' => 'Chinese',
            'value' => 'zh',
            'order' => '2',
            'visible' => '1'
        ],
        [
            'id' => '14',
            'option_type' => 'language',
            'option' => 'English',
            'value' => 'en',
            'order' => '3',
            'visible' => '1'
        ],
        [
            'id' => '15',
            'option_type' => 'language',
            'option' => 'French',
            'value' => 'fr',
            'order' => '4',
            'visible' => '1'
        ],
        [
            'id' => '16',
            'option_type' => 'language',
            'option' => 'Russian',
            'value' => 'ru',
            'order' => '5',
            'visible' => '1'
        ],
        [
            'id' => '17',
            'option_type' => 'language',
            'option' => 'español',
            'value' => 'es',
            'order' => '6',
            'visible' => '1'
        ],
        [
            'id' => '18',
            'option_type' => 'yes_no',
            'option' => 'Yes',
            'value' => '1',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '19',
            'option_type' => 'yes_no',
            'option' => 'No',
            'value' => '0',
            'order' => '2',
            'visible' => '1'
        ],
        [
            'id' => '20',
            'option_type' => 'wizard',
            'option' => 'Non-Mandatory',
            'value' => '0',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '21',
            'option_type' => 'wizard',
            'option' => 'Mandatory',
            'value' => '1',
            'order' => '2',
            'visible' => '1'
        ],
        [
            'id' => '22',
            'option_type' => 'wizard',
            'option' => 'Excluded',
            'value' => '2',
            'order' => '3',
            'visible' => '1'
        ],
        [
            'id' => '23',
            'option_type' => 'database:Country',
            'option' => 'Country.name',
            'value' => 'Country.id',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '24',
            'option_type' => 'database:AcademicPeriod',
            'option' => 'AcademicPeriod.name',
            'value' => 'AcademicPeriod.id',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '25',
            'option_type' => 'yearbook_orientation',
            'option' => 'Portrait',
            'value' => 'P',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '26',
            'option_type' => 'yearbook_orientation',
            'option' => 'Landscape',
            'value' => 'L',
            'order' => '2',
            'visible' => '1'
        ],
        [
            'id' => '27',
            'option_type' => 'first_day_of_week',
            'option' => 'Monday',
            'value' => '1',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '28',
            'option_type' => 'first_day_of_week',
            'option' => 'Tuesday',
            'value' => '2',
            'order' => '2',
            'visible' => '1'
        ],
        [
            'id' => '29',
            'option_type' => 'first_day_of_week',
            'option' => 'Wednesday',
            'value' => '3',
            'order' => '3',
            'visible' => '1'
        ],
        [
            'id' => '30',
            'option_type' => 'first_day_of_week',
            'option' => 'Thursday',
            'value' => '4',
            'order' => '4',
            'visible' => '1'
        ],
        [
            'id' => '31',
            'option_type' => 'first_day_of_week',
            'option' => 'Friday',
            'value' => '5',
            'order' => '5',
            'visible' => '1'
        ],
        [
            'id' => '32',
            'option_type' => 'first_day_of_week',
            'option' => 'Saturday',
            'value' => '6',
            'order' => '6',
            'visible' => '1'
        ],
        [
            'id' => '33',
            'option_type' => 'first_day_of_week',
            'option' => 'Sunday',
            'value' => '0',
            'order' => '7',
            'visible' => '1'
        ],
        [
            'id' => '34',
            'option_type' => 'database:AreaLevel',
            'option' => 'AreaLevel.name',
            'value' => 'AreaLevel.id',
            'order' => '1',
            'visible' => '1'
        ],
        [
            'id' => '35',
            'option_type' => 'authentication_type',
            'option' => 'Google',
            'value' => 'Google',
            'order' => '3',
            'visible' => '1'
        ],
        [
            'id' => '36',
            'option_type' => 'authentication_type',
            'option' => 'Saml2',
            'value' => 'Saml2',
            'order' => '4',
            'visible' => '1'
        ]
    ];
}

