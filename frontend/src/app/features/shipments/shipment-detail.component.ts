import { DatePipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { takeUntilDestroyed, toObservable } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { Subject, catchError, combineLatest, exhaustMap, finalize, of, startWith, switchMap } from 'rxjs';

import { AuthService } from '../../core/auth/auth.service';
import { Shipment, ShipmentStatus, statusLabel } from '../../core/models/shipment.model';
import { ShipmentService } from '../../core/services/shipment.service';
import { ToastService } from '../../core/services/toast.service';
import { StatusBadgeComponent } from '../../shared/components/status-badge.component';
import { PkrPipe } from '../../shared/pipes/pkr.pipe';
import { HistoryTimelineComponent } from './history-timeline.component';
import { StatusChangerComponent } from './status-changer.component';

@Component({
  selector: 'app-shipment-detail',
  imports: [RouterLink, DatePipe, PkrPipe, StatusBadgeComponent, StatusChangerComponent, HistoryTimelineComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './shipment-detail.component.html',
})
export class ShipmentDetailComponent {
  private readonly shipments = inject(ShipmentService);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);

  /** The ':id' route parameter arrives here as an input (withComponentInputBinding). */
  readonly id = input.required<string>();

  readonly shipment = signal<Shipment | null>(null);
  readonly loading = signal(true);
  readonly notFound = signal(false);
  readonly busy = signal(false);

  private readonly reload$ = new Subject<void>();
  private readonly cancelClicks$ = new Subject<void>();

  constructor() {
    // Load the shipment when the id changes, or when reload$ fires.
    combineLatest([toObservable(this.id), this.reload$.pipe(startWith(undefined))])
      .pipe(
        switchMap(([id]) =>
          this.shipments.get(Number(id)).pipe(
            catchError((err: HttpErrorResponse) => {
              this.notFound.set(err.status === 404 || err.status === 403);
              return of(null);
            }),
          ),
        ),
        takeUntilDestroyed(), // unsubscribe automatically when leaving the page
      )
      .subscribe((shipment) => {
        if (shipment) this.shipment.set(shipment);
        this.loading.set(false);
      });

    // exhaustMap: while a cancel request is running, extra clicks are IGNORED.
    // That stops double requests from a double-click.
    this.cancelClicks$
      .pipe(
        exhaustMap(() => {
          this.busy.set(true);
          return this.shipments.cancel(this.shipment()!.id).pipe(
            catchError(() => of(null)),
            finalize(() => this.busy.set(false)),
          );
        }),
        takeUntilDestroyed(),
      )
      .subscribe((shipment) => {
        if (shipment) {
          this.shipment.set(shipment);
          this.toast.success('Shipment cancelled.');
        }
      });

    // While the shipment is 'pending', the Laravel queue worker is booking it in the background.
    // Check again every 3 seconds so the page updates by itself when it becomes 'booked'.
    effect((onCleanup) => {
      if (this.shipment()?.status.value !== 'pending') return;
      const timer = setTimeout(() => this.reload$.next(), 3000);
      onCleanup(() => clearTimeout(timer));
    });
  }

  cancel(): void {
    if (confirm('Cancel this shipment? This cannot be undone.')) {
      this.cancelClicks$.next();
    }
  }

  /** Called by the child component's (statusChange) output. */
  changeStatus(status: ShipmentStatus): void {
    const current = this.shipment();
    if (!current) return;

    this.busy.set(true);
    this.shipments
      .updateStatus(current.id, status)
      .pipe(finalize(() => this.busy.set(false)))
      .subscribe({
        next: (updated) => {
          this.shipment.set(updated);
          this.toast.success(`Status changed to "${statusLabel(status)}".`);
        },
        error: (err: HttpErrorResponse) => {
          if (err.status === 422) {
            this.toast.error(err.error?.errors?.status?.[0] ?? 'That status change is not allowed.');
          }
        },
      });
  }

  refresh(): void {
    this.reload$.next();
  }
}
