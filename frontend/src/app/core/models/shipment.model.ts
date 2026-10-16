export type ShipmentStatus = 'pending' | 'booked' | 'in_transit' | 'delivered' | 'cancelled';

export const SHIPMENT_STATUSES: { value: ShipmentStatus; label: string }[] = [
  { value: 'pending', label: 'Pending' },
  { value: 'booked', label: 'Booked with courier' },
  { value: 'in_transit', label: 'In transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
];

/**
 * Same rules as the Laravel ShipmentStatus enum. Used ONLY to fill the admin dropdown.
 * The server still checks every change, so the front end can never break the rules.
 */
export const NEXT_STATUSES: Record<ShipmentStatus, ShipmentStatus[]> = {
  pending: ['booked', 'cancelled'],
  booked: ['in_transit', 'cancelled'],
  in_transit: ['delivered'],
  delivered: [],
  cancelled: [],
};

export function statusLabel(status: ShipmentStatus): string {
  return SHIPMENT_STATUSES.find((s) => s.value === status)?.label ?? status;
}

export function isShipmentStatus(value: unknown): value is ShipmentStatus {
  return SHIPMENT_STATUSES.some((s) => s.value === value);
}

export interface ShipmentHistory {
  from: ShipmentStatus | null;
  to: ShipmentStatus;
  to_label: string;
  changed_by: string;
  at: string;
}

export interface Shipment {
  id: number;
  tracking_number: string;
  sender: { name: string; city: string };
  receiver: { name: string; phone: string; address: string; city: string };
  weight_kg: number;
  price: number;
  status: { value: ShipmentStatus; label: string };
  courier_reference: string | null;
  booked_at: string | null;
  created_at: string;
  can: { cancel: boolean; update_status: boolean };
  history?: ShipmentHistory[];
}

export interface NewShipment {
  sender_name: string;
  sender_city: string;
  receiver_name: string;
  receiver_phone: string;
  receiver_address: string;
  receiver_city: string;
  weight_kg: number;
}

export interface QuoteRequest {
  weight_kg: number;
  sender_city: string;
  receiver_city: string;
}

export interface Quote {
  base_fee: number;
  weight_charge: number;
  same_city: boolean;
  total: number;
}

export interface ShipmentQuery {
  status?: ShipmentStatus | '';
  search?: string;
  page?: number;
  per_page?: number;
}

export interface DashboardStats {
  total: number;
  by_status: Record<ShipmentStatus, number>;
}
