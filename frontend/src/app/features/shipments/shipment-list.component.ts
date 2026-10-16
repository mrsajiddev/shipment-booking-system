import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { NonNullableFormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import {
  Subject,
  catchError,
  debounceTime,
  distinctUntilChanged,
  map,
  of,
  startWith,
  switchMap,
  tap,
} from 'rxjs';

import { Paginated } from '../../core/models/api.model';
import {
  SHIPMENT_STATUSES,
  Shipment,
  ShipmentStatus,
  isShipmentStatus,
} from '../../core/models/shipment.model';
import { ShipmentService } from '../../core/services/shipment.service';
import { StatusBadgeComponent } from '../../shared/components/status-badge.component';
import { PkrPipe } from '../../shared/pipes/pkr.pipe';

@Component({
  selector: 'app-shipment-list',
  imports: [ReactiveFormsModule, RouterLink, DatePipe, PkrPipe, StatusBadgeComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './shipment-list.component.html',
})
export class ShipmentListComponent {
  private readonly fb = inject(NonNullableFormBuilder);
  private readonly shipments = inject(ShipmentService);

  readonly statuses = SHIPMENT_STATUSES;
  readonly loading = signal(true);

  // The dashboard links here with ?status=..., so start the filter from the URL.
  private readonly initialStatus = inject(ActivatedRoute).snapshot.queryParamMap.get('status');

  readonly filters = this.fb.group({
    search: '',
    status: this.fb.control<ShipmentStatus | ''>(isShipmentStatus(this.initialStatus) ? this.initialStatus : ''),
  });

  private readonly pageChanges = new Subject<number>();

  /**
   * The whole list is one RxJS pipeline:
   *  1. filter changes -> wait 300 ms after typing stops (debounceTime)
   *  2. ignore if nothing really changed (distinctUntilChanged)
   *  3. a new filter starts again at page 1; page clicks continue inside (inner switchMap)
   *  4. switchMap CANCELS the previous HTTP request if a new one starts
   *     -> an old slow response can never overwrite a newer one.
   */
  readonly result = toSignal<Paginated<Shipment> | null>(
    this.filters.valueChanges.pipe(
      startWith(this.filters.getRawValue()),
      map(() => this.filters.getRawValue()),
      debounceTime(300),
      distinctUntilChanged((a, b) => a.search === b.search && a.status === b.status),
      switchMap((filters) =>
        this.pageChanges.pipe(
          startWith(1),
          map((page) => ({ ...filters, page })),
        ),
      ),
      tap(() => this.loading.set(true)),
      switchMap((query) => this.shipments.list(query).pipe(catchError(() => of(null)))),
      tap(() => this.loading.set(false)),
    ),
    { initialValue: null },
  );

  goToPage(page: number): void {
    this.pageChanges.next(page);
  }

  clearFilters(): void {
    this.filters.setValue({ search: '', status: '' });
  }
}
