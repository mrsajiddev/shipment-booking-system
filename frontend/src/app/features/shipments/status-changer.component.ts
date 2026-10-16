import { ChangeDetectionStrategy, Component, computed, input, output, signal } from '@angular/core';

import { NEXT_STATUSES, ShipmentStatus, statusLabel } from '../../core/models/shipment.model';

/**
 * CHILD component for admins.
 * Data comes IN with input(), the chosen status goes OUT with output().
 * It does not call the API itself — the parent decides what to do (a "dumb" component).
 */
@Component({
  selector: 'app-status-changer',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (options().length) {
      <div class="status-changer">
        <label for="next-status">Move to</label>
        <select id="next-status" [value]="selected()" (change)="onSelect($event)" [disabled]="busy()">
          <option value="">Choose status…</option>
          @for (o of options(); track o.value) {
            <option [value]="o.value">{{ o.label }}</option>
          }
        </select>
        <button type="button" class="btn btn--primary btn--sm" [disabled]="!selected() || busy()" (click)="apply()">
          {{ busy() ? 'Saving…' : 'Update' }}
        </button>
      </div>
    } @else {
      <p class="muted">This shipment is finished. No more status changes.</p>
    }
  `,
})
export class StatusChangerComponent {
  readonly current = input.required<ShipmentStatus>();
  readonly busy = input(false);
  readonly statusChange = output<ShipmentStatus>();

  readonly selected = signal<ShipmentStatus | ''>('');
  readonly options = computed(() =>
    NEXT_STATUSES[this.current()].map((value) => ({ value, label: statusLabel(value) })),
  );

  onSelect(event: Event): void {
    this.selected.set((event.target as HTMLSelectElement).value as ShipmentStatus | '');
  }

  apply(): void {
    const status = this.selected();
    if (status) {
      this.statusChange.emit(status);
      this.selected.set('');
    }
  }
}
