import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Spinner } from "reactstrap";
import { useAuth } from "./AuthProvider";

const FullPageSpinner = () => (
  <div className="d-flex justify-content-center align-items-center" style={{ minHeight: "100vh" }}>
    <Spinner color="primary">Loading...</Spinner>
  </div>
);

/** Menahan route sampai status sesi diketahui, lalu mengarahkan tamu ke login. */
export const RequireAuth = ({ children }: { children: React.ReactNode }) => {
  const { user, initialising } = useAuth();
  const location = useLocation();

  if (initialising) return <FullPageSpinner />;

  if (!user) {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />;
  }

  return <>{children}</>;
};

/**
 * PRD 01 §27 — menyembunyikan halaman yang tidak diizinkan. Ini murni UX;
 * backend tetap menolak request tanpa permission.
 */
export const RequirePermission = ({
  permission,
  children,
}: {
  permission: string | string[];
  children: React.ReactNode;
}) => {
  const { can, initialising } = useAuth();
  const needed = Array.isArray(permission) ? permission : [permission];

  if (initialising) return <FullPageSpinner />;

  if (!can(...needed)) {
    return <Navigate to="/dashboard" replace />;
  }

  return <>{children}</>;
};
