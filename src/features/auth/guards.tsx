import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Spinner } from "reactstrap";
import { useAuth } from "./AuthProvider";
import { landingPath } from "../layout/menu";

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

  if (needed.length === 0) return <>{children}</>;

  if (!can(...needed)) {
    const fallback = landingPath(can);

    // Tanpa halaman yang boleh dibuka, mengarahkan ke mana pun hanya akan
    // berputar dan menghasilkan layar kosong. Lebih baik dikatakan terus terang.
    if (fallback === null) {
      return <NoAccess />;
    }

    return <Navigate to={fallback} replace />;
  }

  return <>{children}</>;
};

/** Ditampilkan saat role user tidak memberi akses ke satu halaman pun. */
const NoAccess = () => {
  const { user, logout } = useAuth();

  return (
    <div className="d-flex flex-column min-vh-100 align-items-center justify-content-center text-center px-3">
      <h5 className="mb-2">Akun Anda belum memiliki akses halaman</h5>
      <p className="text-muted mb-4">
        Role yang melekat pada akun {user?.email} belum mengizinkan pembukaan halaman mana pun.
        Hubungi administrator organisasi Anda.
      </p>
      <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => void logout()}>
        Keluar
      </button>
    </div>
  );
};
