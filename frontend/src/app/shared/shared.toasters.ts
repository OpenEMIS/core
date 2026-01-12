import { IToasterConfig } from './shared.interfaces';

export const saveSuccess: IToasterConfig = {
  title: 'Success',
  type: 'success',
  timeout: 2000,
  body: 'Saved Successfully',
  tapToDismiss: true
};

export const saveFail: IToasterConfig = {
  title: 'Error While Saving',
  type: 'error',
  timeout: 2000,
  tapToDismiss: true
};

export const serverError: IToasterConfig = {
  title: 'Server Error',
  type: 'error',
  timeout: 2000,
  tapToDismiss: true
};

export const updateSuccess: IToasterConfig = {
  title: 'Success',
  type: 'success',
  timeout: 2000,
  body: 'Updated Successfully',
  tapToDismiss: true
};

export const missingFieldsError: IToasterConfig = {
  title: 'Missing Required Fields',
  type: 'error',
  timeout: 2000,
  tapToDismiss: true
};

export const invalidIdError: IToasterConfig = {
  title: 'Incorrect/No ID Selected',
  type: 'error',
  timeout: 2000,
  tapToDismiss: true
};

export const detailsNotFound: IToasterConfig = {
  title: 'No details found',
  type: 'error',
  tapToDismiss: true,
  timeout: 2000
};

export const alreadyAtRoot: IToasterConfig = {
  title: 'Already at top Level',
  type: 'success',
  tapToDismiss: true,
  timeout: 2000
};

export const deleteSuccess: IToasterConfig = {
  title: 'Deleted successfully',
  type: 'success',
  tapToDismiss: true,
  timeout: 2000
};

export const deleteFail: IToasterConfig = {
  title: 'Failed to delete',
  type: 'error',
  tapToDismiss: true,
  timeout: 2000
};

export const deleteError: IToasterConfig = {
  title: 'Error while deleting',
  type: 'error',
  tapToDismiss: true,
  timeout: 2000
};

export const listNotFound: IToasterConfig = {
  title: 'No List Found',
  type: 'error',
  tapToDismiss: true,
  timeout: 2000
};

export const copySuccess: IToasterConfig = {
  title: 'Success',
  type: 'success',
  timeout: 2000,
  body: 'Copied Successfully',
  tapToDismiss: true
};

export const missingFilters: IToasterConfig = {
  title: 'Filters Missing',
  type: 'error',
  timeout: 2000,
  body: 'Please select all filters!',
  tapToDismiss: true
};
