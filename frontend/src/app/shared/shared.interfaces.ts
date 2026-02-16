export interface IDropdownOptions {
  key: number | string;
  value: string;
}

export interface IFilterItem {
  id: number;
  name: string;
}

export interface IToasterConfig {
  type: string;
  title: string;
  body?: string;
  showCloseButton?: boolean;
  tapToDismiss?: boolean;
  timeout: number;
}

export interface ITableQuestionFormatInterface {
  id?: string;
  questionKey?: string;
  question?: {
    key?: string;
    label?: string;
    required?: boolean;
    visible?: boolean;
    controlType?: string;
    row?: any;
    [key: string]: any;
  };
}

export interface IKdTabs {
  tabName?: string;
  tabContent?: string;
  tabId?: string;
  isActive?: boolean;
  disabled?: boolean;
  routerPath?: string;
}

export interface IModalConfig {
  title?: string;
  body?: string;
  button: Array<IModalButton>;
}

export interface IModalButton {
  text?: string;
  class?: string;
  callback?: (event?) => void;
}

export interface IFetchListParams {
  //TODO  Remove later for simpler pagination param
  startRow?: number;
  //TODO Remove later for simpler pagination param
  endRow?: number;
  page?: number;
  sort?: { colId: string; order: string };
  // TODO Create simpler filter param structure
  filter?: any;
}
