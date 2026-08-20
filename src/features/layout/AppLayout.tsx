import React from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../auth/AuthProvider";

const AppLayout = ({ children }: { children: React.ReactNode }) => {
  const { user, logout } = useAuth();
  return (
    <div className="min-vh-100 bg-light">
      <nav className="navbar navbar-expand-lg bg-white border-bottom px-4">
        <Link className="navbar-brand fw-semibold" to="/muzakis">ZETRA</Link>
        <div className="navbar-nav gap-2"><Link className="nav-link" to="/muzakis">Muzaki</Link><Link className="nav-link" to="/zakat">Zakat</Link></div>
        <div className="ms-auto d-flex align-items-center gap-3"><span className="text-muted small">{user?.name}</span><button className="btn btn-sm btn-outline-secondary" onClick={() => void logout()}>Keluar</button></div>
      </nav>
      {children}
    </div>
  );
};

export default AppLayout;
