import { Pipe, PipeTransform } from '@angular/core';

const formatter = new Intl.NumberFormat('en-PK', { maximumFractionDigits: 0 });

/**
 * {{ 1250 | pkr }}  ->  "Rs 1,250"
 * A PURE pipe (the default): Angular only re-runs it when the input value changes.
 */
@Pipe({ name: 'pkr' })
export class PkrPipe implements PipeTransform {
  transform(value: number | null | undefined): string {
    if (value === null || value === undefined || Number.isNaN(value)) {
      return '—';
    }
    return `Rs ${formatter.format(value)}`;
  }
}
