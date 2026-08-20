import axios, { AxiosError, AxiosInstance } from "axios";

/**
 * Client API ZETRA.
 *
 * Memakai instance axios tersendiri, terpisah dari `helpers/api_helper` milik
 * template, karena autentikasinya cookie-based Sanctum (PRD 01 §17) dan bukan
 * bearer token.
 */
export const api: AxiosInstance = axios.create({
  baseURL: "/api/v1",
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: "application/json" },
});

/** Bentuk error envelope backend (PRD 00 §17). */
export interface ApiErrorPayload {
  message: string;
  errors: Record<string, string[]>;
  code: string;
  request_id: string;
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    message: string,
    public readonly errors: Record<string, string[]> = {},
    public readonly requestId?: string,
  ) {
    super(message);
    this.name = "ApiError";
  }

  /** Pesan pertama untuk satu field, dipakai menempelkan error ke input form. */
  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

let csrfReady = false;

/** Laravel mengirim cookie XSRF-TOKEN lewat endpoint ini sebelum request menulis. */
export async function ensureCsrfCookie(): Promise<void> {
  if (csrfReady) return;
  await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
  csrfReady = true;
}

export function resetCsrfCookie(): void {
  csrfReady = false;
}

api.interceptors.request.use(async (config) => {
  const method = (config.method ?? "get").toLowerCase();
  if (method !== "get" && method !== "head") {
    await ensureCsrfCookie();
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiErrorPayload>) => {
    if (!error.response) {
      return Promise.reject(new ApiError(0, "NETWORK_ERROR", "Tidak dapat terhubung ke server."));
    }

    const { status, data } = error.response;

    // Sesi CSRF kedaluwarsa: ambil cookie baru pada percobaan berikutnya.
    if (status === 419) {
      resetCsrfCookie();
    }

    return Promise.reject(
      new ApiError(
        status,
        data?.code ?? "SERVER_ERROR",
        data?.message ?? "Terjadi kesalahan pada server.",
        data?.errors ?? {},
        data?.request_id,
      ),
    );
  },
);

/** Ambil bagian `data` dari envelope sukses. */
export async function getData<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  const response = await api.get(url, { params });
  return response.data.data as T;
}

export async function postData<T>(url: string, payload: Record<string, unknown>): Promise<T> {
  const response = await api.post(url, payload);
  return response.data.data as T;
}

/** Ambil `data` dan `meta` sekaligus untuk endpoint yang berpaginasi. */
export async function getPage<T>(
  url: string,
  params?: Record<string, unknown>,
): Promise<{ data: T[]; meta: PaginationMeta }> {
  const response = await api.get(url, { params });
  return { data: response.data.data as T[], meta: response.data.meta as PaginationMeta };
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
