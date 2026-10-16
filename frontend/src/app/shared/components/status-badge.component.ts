import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

import { ShipmentStatus, statusLabel } from '../../core/models/shipment.model';

/** Small reusable child component. Parent passes data in with an input. */
@Component({
  selector: 'app-status-badge',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `<span class="badge" [class]="'badge badge--' + status()">{{ text() }}</span>`,
})
export class StatusBadgeComponent {
  readonly status = input.required<ShipmentStatus>();
  readonly label = input<string>();

  readonly text = computed(() => this.label() ?? statusLabel(this.status()));
}
