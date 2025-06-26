export const LEFT_NAV = [
  {
    header: "Registrations",
    icon: "fa fa-users",
    list: [
      {
        header: "Candidates",
        path: "main/registration/candidates",
        permissionKey: "Candidates",
        parentKey: "Registrations",
      },
      {
        header: "Fees",
        path: "main/registration/fees",
        permissionKey: "Fees",
        parentKey: "Registrations",
      },
    ],
    // permissionKey: 'Registrations'
  },
  {
    header: "Marks",
    icon: "kd-header-row",
    list: [
      {
        header: "Components",
        path: "main/marks/components",
        permissionKey: "Components",
        parentKey: "Marks",
      },
      {
        header: "Multiple Choices",
        path: "main/marks/multiple-choice",
        permissionKey: "Multiple Choice",
        parentKey: "Marks",
      },
    ],
  },
  {
    header: "Results",
    icon: "fa fa-graduation-cap",
    list: [
      {
        header: "Forecast Grades",
        path: "main/results/forecast-grades",
        permissionKey: "Forecast Grades",
        parentKey: "Results",
      },
      // { header: 'Forecast Grades', path: 'main/results/forecast-grades', permissionKey: 'Forecast-Grades', parentKey:'Results' },
      {
        header: "Grade Reviews",
        path: "main/results/grade-reviews",
        permissionKey: "Grade Reviews",
        parentKey: "Results",
      },
      {
        header: "Final Grades",
        path: "main/results/final-grades",
        permissionKey: "Final Grades",
        parentKey: "Results",
      },
    ],
  },
  {
    header: "Certificates",
    icon: "fa fa-certificate",
    list: [
      {
        header: "Statements",
        path: "main/certificates/statements",
        permissionKey: "Statements",
        parentKey: "Certificates",
      }, //missing permissions in API
      {
        header: "Collections",
        path: "main/certificates/collections",
        permissionKey: "Collections",
        parentKey: "Certificates",
      },
      {
        header: "Issuances",
        path: "main/certificates/issuances",
        permissionKey: "Issuances",
        parentKey: "Certificates",
      },
      // {
      //   header: "Issuances",
      //   path: "main/certificates/issuances",
      //   permissionKey: "Issuances",
      //   parentKey: "Certificates",
      // },
      {
        header: "Verifications",
        path: "main/certificates/verifications",
        permissionKey: "Verifications",
        parentKey: "Certificates",
      },
    ],
  },
  {
    header: "Reports",
    icon: " kd-reports",
    list: [
      {
        header: "Registrations",
        path: "main/reports",
        permissionKey: "Registrations",
        parentKey: "Reports",
      },
      // {
      //   header: 'Form',
      //   list: [
      //     { header: 'MS Examiner', path: 'main/reports/form' },
      //     { header: 'Grades', path: 'main/reports/form/grade' },
      //     { header: 'Multiple Choice', path: 'main/reports/form/multichoice' }
      //   ]
      // },
      {
        header: "Marks",
        path: "main/reports/marks",
        permissionKey: "Marks",
        parentKey: "Reports",
      },
      {
        header: "Results",
        path: "main/reports/results",
        permissionKey: "Results",
        parentKey: "Reports",
      },
      {
        header: "Certificates",
        path: "main/reports/certificates",
        permissionKey: "Certificates",
        parentKey: "Reports",
      },
      {
        header: "Statistics",
        path: "main/reports/statistics",
        // list: [
        //   { header: 'Exam-Statistics', path: 'main/reports/statistics/statisticexam', permissionKey: 'Statistics', parentKey:'Reports' },
        //   { header: 'Centre-Statistics', path: 'main/reports/statistics/statisticentre', permissionKey: 'Statistics', parentKey:'Reports' }
        // ],
        permissionKey: "Statistics",
        parentKey: "Reports",
      },
      {
        header: " Data Quality",
        path: "main/reports/data-quality",
        permissionKey: "DataQuality",
        parentKey: "Reports",
      },
      {
        header: "Custom",
        path: "main/reports/custom",
        permissionKey: "Statistics",
        parentKey: "Reports",
      },
    ],
  },
  {
    header: "Administration",
    icon: "fa fa-cogs",
    list: [
      {
        header: "System Setup",
        path: "main/system-setup",
        list: [
          {
            header: "Administrative Boundaries",
            path: "main/system-setup/administrative-boundaries",
            permissionKey: ["Areas", "Area Levels"],
            parentKey: "Administrative Boundaries",
          }, //needs array
          {
            header: "Academic Period",
            path: "main/system-setup/academic-period",
            permissionKey: ["Academic Periods", "Academic Period Levels"],
            parentKey: "Academic Periods",
          }, //needs array
          {
            header: "Education Structure",
            path: "main/system-setup/education-structure",
            permissionKey: [
              "Education Systems",
              "Education Cycles",
              "Education Grades",
              "Grade Subjects",
              "Education Levels",
              "Education Programmes",
              "Setup",
            ],
            parentKey: "Education",
          }, //needs array
          {
            header: "Field Options",
            path: "main/system-setup/field-options",
            permissionKey: "Field Options",
            parentKey: "Field Options",
          },
          {
            header: "System Configurations",
            path: "main/system-setup/system-configurations",
            permissionKey: "Configurations",
            parentKey: "System Configurations",
          },
          {
            header: "Notices",
            path: "main/system-setup/notices",
            permissionKey: "Notices",
            parentKey: "Notices",
          },
        ],
      },
      {
        header: "Security",
        path: "main/security",
        list: [
          {
            header: "Users",
            path: "main/security/users",
            permissionKey: "Users",
            parentKey: "Security",
          },
          {
            header: "Groups",
            path: "main/security/groups",
            permissionKey: "Groups",
            parentKey: "Security",
          },
          {
            header: "Roles",
            path: "main/security/roles",
            permissionKey: ["User Roles", "System Roles"],
            parentKey: "Security",
          }, //needs array
        ],
      },
      {
        header: "Examinations",
        path: "main/examinations",
        list: [
          {
            header: "Exams",
            path: "main/examinations/exams",
            permissionKey: "Examinations",
            parentKey: "Examinations",
          },
          {
            header: "Centres",
            path: "main/examinations/centres",
            permissionKey: "Exam Centres",
            parentKey: "Examinations",
          },
          {
            header: "Grading Types",
            path: "main/examinations/gradingtypes",
            permissionKey: "Grading Types",
            parentKey: "Examinations",
          },
          {
            header: "Scalings",
            path: "main/examinations/scalings",
            permissionKey: "Scalings",
            parentKey: "Examinations",
          },
          {
            header: "Review Criterias",
            path: "main/examinations/review",
            permissionKey: "Review Criterias",
            parentKey: "Examinations",
          },
          {
            header: "Multiple Choice",
            path: "main/examinations/multiple-choice",
            permissionKey: "Multiple Choice",
            parentKey: "Examinations",
          },
          {
            header: "Certificate Template",
            path: "main/examinations/certificate-template",
            permissionKey: "Certificate Template",
            parentKey: "Examinations",
          },
          {
            header: "Markers",
            path: "main/examinations/markers",
            permissionKey: "Markers",
            parentKey: "Examinations",
          },
          {
            header: "Fees",
            path: "main/examinations/fees",
            permissionKey: "Fees",
            parentKey: "Examinations",
          },
          {
            header: "Appointment",
            path: "main/examinations/appointment",
            permissionKey: "Appointment",
            parentKey: "Examinations",
          },
          {
            header: "Allocations",
            path: "main/examinations/allocations",
            permissionKey: "Allocations",
            parentKey: "Examinations",
          },
          {
            header: "Apportionment",
            path: "main/examinations/apportionment",
            permissionKey: "Apportionment",
            parentKey: "Examinations",
          },
        ],
      },
      {
        header: "Processes",
        path: "main/processes",
        permissionKey: "Processes",
        parentKey: "Processes",
      },
    ],
  },
];
