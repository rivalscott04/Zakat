import { ApiError } from "./client";

const FRIENDLY_ERROR_CODES: Record<string, string> = {
  FORBIDDEN: "Anda tidak memiliki izin untuk melakukan tindakan ini.",
  INVALID_STATE_TRANSITION:
    "Tindakan ini belum dapat dilakukan pada status saat ini.",
  NETWORK_ERROR:
    "Koneksi ke server terputus. Periksa internet Anda lalu coba lagi.",
  NOT_FOUND: "Data yang dicari tidak ditemukan.",
  PROGRAM_BUDGET_EXCEEDED:
    "Anggaran program tidak mencukupi untuk transaksi ini.",
  PROGRAM_FULL: "Program sudah penuh.",
  PROGRAM_FULL_WAITLISTED:
    "Program sudah penuh. Penerima sudah dimasukkan ke daftar tunggu.",
  SERVER_ERROR: "Terjadi masalah pada layanan. Silakan coba lagi.",
  TOO_MANY_REQUESTS:
    "Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.",
  UNAUTHORIZED: "Sesi Anda sudah berakhir. Silakan masuk kembali.",
  VALIDATION_ERROR: "Periksa kembali isian yang ditandai.",
};

/** Pesan yang aman dan mudah dipahami pengguna untuk semua error API. */
export function getUserErrorMessage(error: unknown): string {
  if (error instanceof ApiError)
    return FRIENDLY_ERROR_CODES[error.code] ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return "Maaf, terjadi masalah. Silakan coba lagi.";
}

export function getFieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError)) return {};
  return Object.fromEntries(
    Object.entries(error.errors).map(([field, messages]) => [
      field,
      messages[0] ?? "Isian ini belum benar.",
    ]),
  );
}
