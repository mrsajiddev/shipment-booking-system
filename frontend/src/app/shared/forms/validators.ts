import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

/** Same rule as Laravel: a Pakistani mobile number like 03001234567. */
export const pakistanMobile: ValidatorFn = (control: AbstractControl): ValidationErrors | null => {
  const value = (control.value ?? '').toString().trim();
  if (!value) {
    return null; // 'required' handles empty values
  }
  return /^03\d{9}$/.test(value) ? null : { pakistanMobile: true };
};

/** Group validator: two fields must match (password + confirmation). */
export function matchFields(field: string, confirmField: string): ValidatorFn {
  return (group: AbstractControl): ValidationErrors | null => {
    const a = group.get(field)?.value;
    const b = group.get(confirmField)?.value;
    return a && b && a !== b ? { fieldsMismatch: true } : null;
  };
}
