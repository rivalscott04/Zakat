import React from "react";
import { Badge } from "reactstrap";

/**
 * Pemetaan status ke warna semantik ZETRA (CLAUDE.md §16).
 * Tidak ada warna baru yang diperkenalkan.
 */
const COLORS: Record<string, string> = {
  active: "success",
  pending: "warning",
  draft: "secondary",
  inactive: "secondary",
  suspended: "danger",
  locked: "danger",
  terminated: "danger",
  ended: "dark",
  archived: "dark",
};

const STATUS_LABELS: Record<string, string> = {
  active: "Aktif",
  approved: "Disetujui",
  cancelled: "Dibatalkan",
  closed: "Ditutup",
  completed: "Selesai",
  draft: "Draft",
  expired: "Kedaluwarsa",
  failed: "Gagal",
  inactive: "Tidak aktif",
  pending: "Menunggu",
  pending_approval: "Menunggu persetujuan",
  rejected: "Ditolak",
  reserved: "Dicadangkan",
  submitted: "Diajukan",
  suspended: "Ditangguhkan",
  terminated: "Diakhiri",
  withdrawn: "Ditarik",
};

export function statusLabel(status: string): string {
  return STATUS_LABELS[status] ?? status.replaceAll("_", " ");
}

const StatusBadge = ({ status }: { status: string }) => (
  <Badge color={COLORS[status] ?? "light"} title={status}>
    {statusLabel(status)}
  </Badge>
);

export default StatusBadge;
