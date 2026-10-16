/** Laravel wraps single API Resources in { data: ... }. */
export interface ApiResponse<T> {
  data: T;
}

/** Laravel's paginated resource collection. */
export interface Paginated<T> {
  data: T[];
  meta: PageMeta;
}

export interface PageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

/** Laravel's 422 response body. */
export interface ValidationErrorBody {
  message: string;
  errors?: Record<string, string[]>;
}
