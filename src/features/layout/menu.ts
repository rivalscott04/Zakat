/**
 * Satu sumber kebenaran untuk menu utama sekaligus penentuan halaman awal.
 *
 * PRD 01 §27 — permission di frontend hanya untuk mengatur tampilan. Penjaga
 * sebenarnya tetap `RequirePermission` di router dan middleware `permission`
 * di backend.
 *
 * Permission tiap entri harus sama dengan yang dipakai route-nya di
 * `src/app/routes/allRoutes.tsx`.
 */
export interface MenuItem {
  to: string;
  label: string;
  permission: string;
}

export const MENU: MenuItem[] = [
  { to: "/muzakis", label: "Muzaki", permission: "muzaki.view" },
  { to: "/organizations", label: "Organisasi", permission: "organizations.view" },
  { to: "/amils", label: "Amil", permission: "amils.view" },
  { to: "/users", label: "User", permission: "users.view" },
  { to: "/roles", label: "Role", permission: "roles.view" },
  { to: "/zakat", label: "Zakat", permission: "zakat.view" },
  { to: "/zakat/calculator", label: "Kalkulator", permission: "zakat.calculation.create" },
  { to: "/collections", label: "Collection", permission: "collection.view" },
  { to: "/payments", label: "Payment", permission: "payment.view" },
  { to: "/funds", label: "Fund", permission: "fund.view" },
  { to: "/accounting", label: "Accounting", permission: "accounting.account.view" },
  { to: "/mustahiks", label: "Mustahik", permission: "mustahik.view" },
  { to: "/assessments", label: "Assessment", permission: "assessment.view" },
  { to: "/programs", label: "Program", permission: "program.view" },
  { to: "/distributions", label: "Penyaluran", permission: "distribution.view" },
  { to: "/distribution-batches", label: "Batch Penyaluran", permission: "distribution.batch.view" },
  { to: "/bank-reconciliation", label: "Rekonsiliasi Bank", permission: "bank_reconciliation.view" },
  { to: "/documents", label: "Dokumen", permission: "document.view" },
  { to: "/reports", label: "Laporan", permission: "report.view" },
  { to: "/transparency", label: "Transparansi", permission: "transparency.view" },
  { to: "/notifications", label: "Notifikasi", permission: "notification.view" },
  { to: "/audit-logs", label: "Audit Trail", permission: "audit.view" },
  { to: "/settings", label: "Pengaturan Sistem", permission: "setting.view" },
];

export type PermissionCheck = (...permissions: string[]) => boolean;

export function visibleMenu(can: PermissionCheck): MenuItem[] {
  return MENU.filter((item) => can(item.permission));
}

/**
 * Halaman pertama yang boleh dibuka user.
 *
 * Tidak boleh dipatok ke `/dashboard`: route itu membutuhkan `muzaki.view`,
 * sedangkan enam dari delapan role bawaan tidak memilikinya. Mengarahkan mereka
 * ke sana membuat redirect berputar dan layar menjadi kosong.
 *
 * `null` berarti user tidak punya satu pun halaman yang boleh dibuka.
 */
export function landingPath(can: PermissionCheck): string | null {
  return visibleMenu(can)[0]?.to ?? null;
}
