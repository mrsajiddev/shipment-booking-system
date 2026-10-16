import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  DashboardStats,
  NewShipment,
  Quote,
  QuoteRequest,
  Shipment,
  ShipmentQuery,
  ShipmentStatus,
} from '../models/shipment.model';

/**
 * All shipment API calls live here, so components never build URLs themselves.
 * Each method returns a cold Observable: nothing is sent until someone subscribes.
 */
@Injectable({ providedIn: 'root' })
export class ShipmentService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  dashboard(): Observable<DashboardStats> {
    return this.http.get<ApiResponse<DashboardStats>>(`${this.api}/dashboard`).pipe(map((r) => r.data));
  }

  list(query: ShipmentQuery): Observable<Paginated<Shipment>> {
    let params = new HttpParams()
      .set('page', query.page ?? 1)
      .set('per_page', query.per_page ?? 10);

    if (query.status) {
      params = params.set('status', query.status);
    }
    if (query.search?.trim()) {
      params = params.set('search', query.search.trim());
    }

    return this.http.get<Paginated<Shipment>>(`${this.api}/shipments`, { params });
  }

  get(id: number): Observable<Shipment> {
    return this.http.get<ApiResponse<Shipment>>(`${this.api}/shipments/${id}`).pipe(map((r) => r.data));
  }

  quote(payload: QuoteRequest): Observable<Quote> {
    return this.http.post<ApiResponse<Quote>>(`${this.api}/shipments/quote`, payload).pipe(map((r) => r.data));
  }

  create(payload: NewShipment): Observable<Shipment> {
    return this.http.post<ApiResponse<Shipment>>(`${this.api}/shipments`, payload).pipe(map((r) => r.data));
  }

  cancel(id: number): Observable<Shipment> {
    return this.http
      .post<ApiResponse<Shipment>>(`${this.api}/shipments/${id}/cancel`, {})
      .pipe(map((r) => r.data));
  }

  /** Admin only. Laravel checks the role:admin middleware and the policy again. */
  updateStatus(id: number, status: ShipmentStatus): Observable<Shipment> {
    return this.http
      .patch<ApiResponse<Shipment>>(`${this.api}/admin/shipments/${id}/status`, { status })
      .pipe(map((r) => r.data));
  }
}
