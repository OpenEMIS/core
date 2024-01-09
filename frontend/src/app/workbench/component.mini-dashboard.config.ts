// note that this is not the full list of chart config avaliable from Chart.js
// for more, please refer to http://www.chartjs.org/docs/latest/charts/doughnut.html and http://www.chartjs.org/docs/latest/configuration/

interface IChartConfig {
    title?: {
        display?: boolean,
        text?: string,
    };
    legend?: {
        display?: boolean,
    };
    tooltips?: boolean;
}

interface IChartValue {
    label: string;
    data: number;
    color?: string;
}

interface ITransformedChartData {
    labels: Array<string>;
    datasets: Array<{
        data: Array<number>;
        backgroundColor: Array<string>;
        hoverBackgroundColor: Array<string>;
        hoverBorderColor?: Array<string>;
        hoverBorderWidth?: Array<number>;
    }>;
}

export interface IMiniDashboardItem {
    type: string;
    icon?: string;
    label: string;
    value: number | string | Array<ITransformedChartData> | Array<IChartValue>;
    config?: IChartConfig;
}

export interface IMiniDashboardConfig {
    closeButtonDisabled?: boolean;
    rtl?: boolean;
}

export const MINI_DASHBOARD_CONFIG: IMiniDashboardConfig = {
    closeButtonDisabled: false,
    rtl: true,
};

export const MINI_DASHBOARD_DATA: Array<IMiniDashboardItem> = [];
