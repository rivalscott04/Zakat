import React from "react";
import { Badge } from "reactstrap";

/**
 * Pemetaan status ke warna semantik Velzon (CLAUDE.md §16).
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

const StatusBadge = ({ status }: { status: string }) => (
  <Badge color={COLORS[status] ?? "light"} className="text-uppercase">
    {status}
  </Badge>
);

export default StatusBadge;
