import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { catchError, of } from 'rxjs';

import { AuthService } from '../../core/auth/auth.service';
import { SHIPMENT_STATUSES } from '../../core/models/shipment.model';
import { ShipmentService } from '../../core/services/shipment.service';
import { StatusBadgeComponent } from '../../shared/components/status-badge.component';

@Component({
  selector: 'app-dashboard',
  imports: [RouterLink, StatusBadgeComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  protected readonly auth = inject(AuthService);
  private readonly shipments = inject(ShipmentService);

  /**
   * toSignal() subscribes for us and unsubscribes when the component is destroyed.
   * undefined = still loading, null = failed, object = loaded.
   */
  readonly stats = toSignal(this.shipments.dashboard().pipe(catchError(() => of(null))));

  /** computed(): recalculated automatically whenever stats() changes. */
  readonly cards = computed(() => {
    const stats = this.stats();
    return SHIPMENT_STATUSES.map((s) => ({ ...s, count: stats?.by_status[s.value] ?? 0 }));
  });

  readonly activeCount = computed(() => {
    const by = this.stats()?.by_status;
    return by ? by.pending + by.booked + by.in_transit : 0;
  });
}
