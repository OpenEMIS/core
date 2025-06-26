interface IExampleStep {
    label: string;
    description?: string;
    icon?: string;
    content: string;
}

export interface IWizardConfig {
    steps: Array<IExampleStep>;
    previous?: string;
    next?: string;
    complete?: string;
}

export const STAFF_TEXT: IWizardConfig = {
    steps: [
        {
            label: 'User Details',
            content: 'user-details',
        },
        {
            label: 'Internal Search',
            content: 'internal-search',
        },
        {
            label: 'External Search',
            content: 'external-search'
        },
        {
            label: 'Confirmation',
            content: 'confirmation'
        },
        {
            label: 'Add Staff',
            content: 'add-staff'
        },
        {
            label: 'Summary',
            content: 'summary'
        }
    ],
    next: 'Next',
    complete: 'Close'
};