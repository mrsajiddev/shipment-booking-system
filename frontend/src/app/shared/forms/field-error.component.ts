import { Component, input } from '@angular/core';
import { AbstractControl } from '@angular/forms';

/**
 * Shows the first error of a form control, in plain words.
 *
 * Note: this component uses the DEFAULT change detection on purpose.
 * A control's "touched" state is not a signal or an @Input change, so an OnPush
 * component would not notice it.
 */
@Component({
  selector: 'app-field-error',
  template: `
    @if (message; as text) {
      <small class="field-error">{{ text }}</small>
    }
  `,
})
export class FieldErrorComponent {
  readonly control = input.required<AbstractControl>();
  readonly label = input('This field');

  get message(): string | null {
    const control = this.control();
    if (!control.errors || !(control.touched || control.dirty)) {
      return null;
    }

    const e = control.errors;
    if (e['server']) return e['server'];
    if (e['required']) return `${this.label()} is required.`;
    if (e['email']) return 'Enter a valid email address.';
    if (e['minlength']) return `${this.label()} must be at least ${e['minlength'].requiredLength} characters.`;
    if (e['maxlength']) return `${this.label()} can be at most ${e['maxlength'].requiredLength} characters.`;
    if (e['min']) return `${this.label()} must be at least ${e['min'].min}.`;
    if (e['max']) return `${this.label()} can be at most ${e['max'].max}.`;
    if (e['pakistanMobile']) return 'Use the format 03001234567.';
    return `${this.label()} is not valid.`;
  }
}
