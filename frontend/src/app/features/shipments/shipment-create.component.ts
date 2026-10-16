import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { catchError, debounceTime, distinctUntilChanged, finalize, map, of, startWith, switchMap } from 'rxjs';

import { AuthService } from '../../core/auth/auth.service';
import { ValidationErrorBody } from '../../core/models/api.model';
import { Quote, QuoteRequest } from '../../core/models/shipment.model';
import { ShipmentService } from '../../core/services/shipment.service';
import { ToastService } from '../../core/services/toast.service';
import { FieldErrorComponent } from '../../shared/forms/field-error.component';
import { applyServerErrors } from '../../shared/forms/server-errors';
import { pakistanMobile } from '../../shared/forms/validators';
import { PkrPipe } from '../../shared/pipes/pkr.pipe';

@Component({
  selector: 'app-shipment-create',
  imports: [ReactiveFormsModule, RouterLink, FieldErrorComponent, PkrPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './shipment-create.component.html',
})
export class ShipmentCreateComponent {
  private readonly fb = inject(NonNullableFormBuilder);
  private readonly shipments = inject(ShipmentService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  readonly cities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta'];
  readonly saving = signal(false);
  readonly formError = signal<string | null>(null);

  // Same rules as Laravel's StoreShipmentRequest. The client checks for speed;
  // the server checks for safety.
  readonly form = this.fb.group({
    sender_name: [inject(AuthService).user()?.name ?? '', [Validators.required, Validators.maxLength(100)]],
    sender_city: ['Lahore', [Validators.required]],
    receiver_name: ['', [Validators.required, Validators.maxLength(100)]],
    receiver_phone: ['', [Validators.required, pakistanMobile]],
    receiver_address: ['', [Validators.required, Validators.maxLength(255)]],
    receiver_city: ['', [Validators.required]],
    weight_kg: [1, [Validators.required, Validators.min(0.1), Validators.max(50)]],
  });

  /**
   * LIVE PRICE: when weight or cities change, wait 400 ms, then ask Laravel for a quote.
   * switchMap drops the old request if the user keeps typing.
   */
  readonly quote = toSignal<Quote | null>(
    this.form.valueChanges.pipe(
      startWith(this.form.getRawValue()),
      map((): QuoteRequest => {
        const v = this.form.getRawValue();
        return { weight_kg: Number(v.weight_kg), sender_city: v.sender_city, receiver_city: v.receiver_city };
      }),
      distinctUntilChanged(
        (a, b) => a.weight_kg === b.weight_kg && a.sender_city === b.sender_city && a.receiver_city === b.receiver_city,
      ),
      debounceTime(400),
      switchMap((q) =>
        this.canQuote(q) ? this.shipments.quote(q).pipe(catchError(() => of(null))) : of(null),
      ),
    ),
    { initialValue: null },
  );

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.formError.set(null);

    const value = this.form.getRawValue();

    this.shipments
      .create({ ...value, weight_kg: Number(value.weight_kg) })
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: (shipment) => {
          this.toast.success(`Shipment ${shipment.tracking_number} created. Booking with the courier…`);
          this.router.navigate(['/shipments', shipment.id]);
        },
        error: (err: HttpErrorResponse) => {
          if (err.status === 422) {
            const rest = applyServerErrors(this.form, (err.error as ValidationErrorBody).errors);
            this.formError.set(rest[0] ?? 'Please fix the highlighted fields.');
          }
        },
      });
  }

  private canQuote(q: QuoteRequest): boolean {
    return q.weight_kg >= 0.1 && q.weight_kg <= 50 && !!q.sender_city && !!q.receiver_city;
  }
}
