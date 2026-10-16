import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { ShipmentHistory } from '../../core/models/shipment.model';

/** Loaded lazily with @defer in the detail page. */
@Component({
  selector: 'app-history-timeline',
  imports: [DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <ol class="timeline">
      @for (item of items(); track $index) {
        <li class="timeline__item" [class]="'timeline__item timeline__item--' + item.to">
          <div class="timeline__dot"></div>
          <div>
            <strong>{{ item.to_label }}</strong>
            <div class="muted small">{{ item.at | date: 'd MMM y, h:mm a' }} · by {{ item.changed_by }}</div>
          </div>
        </li>
      } @empty {
        <li class="muted">No history yet.</li>
      }
    </ol>
  `,
})
export class HistoryTimelineComponent {
  readonly items = input.required<ShipmentHistory[]>();
}
