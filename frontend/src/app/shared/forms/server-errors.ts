import { FormGroup } from '@angular/forms';

/**
 * Puts Laravel's 422 messages onto the matching form controls.
 *   { errors: { receiver_phone: ["Enter a valid mobile number..."] } }
 * becomes an error under the receiver_phone input.
 *
 * Returns messages that had no matching field, so the page can show them at the top.
 */
export function applyServerErrors(form: FormGroup, errors: Record<string, string[]> | undefined): string[] {
  const leftOver: string[] = [];

  Object.entries(errors ?? {}).forEach(([field, messages]) => {
    const control = form.get(field);
    if (control) {
      control.setErrors({ server: messages[0] });
      control.markAsTouched();
    } else {
      leftOver.push(...messages);
    }
  });

  return leftOver;
}
