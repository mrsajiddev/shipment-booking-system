import { Routes } from '@angular/router';

/**
 * Child routes of /shipments. This whole file is LAZY LOADED:
 * the code only downloads when the user first opens a shipments page.
 */
export const SHIPMENT_ROUTES: Routes = [
  {
    path: '',
    title: 'Shipments | Shipment Portal',
    loadComponent: () => import('./shipment-list.component').then((m) => m.ShipmentListComponent),
  },
  {
    path: 'new',
    title: 'New shipment | Shipment Portal',
    loadComponent: () => import('./shipment-create.component').then((m) => m.ShipmentCreateComponent),
  },
  {
    path: ':id',
    title: 'Shipment | Shipment Portal',
    loadComponent: () => import('./shipment-detail.component').then((m) => m.ShipmentDetailComponent),
  },
];
