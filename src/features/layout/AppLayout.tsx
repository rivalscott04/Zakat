import React from "react";
import { Link, useLocation } from "react-router-dom";
import { useAuth } from "../auth/AuthProvider";
import OrganizationSwitcher from "../components/OrganizationSwitcher";
import NotificationBell from "../notifications/NotificationBell";
import { visibleMenu } from "./menu";

const AppLayout = ({ children }: { children: React.ReactNode }) => {
  const { user, logout, can } = useAuth();
  const { pathname } = useLocation();

  const visible = visibleMenu(can);

  return (
    <div className="min-vh-100 bg-light">
      <nav className="navbar navbar-expand-lg bg-white border-bottom px-4">
        <Link className="navbar-brand fw-semibold" to={visible[0]?.to ?? "/"}>
          ZETRA
        </Link>

        <div className="navbar-nav gap-2">
          {visible.map((item) => (
            <Link
              key={item.to}
              className={`nav-link${pathname === item.to ? " active fw-semibold" : ""}`}
              to={item.to}
            >
              {item.label}
            </Link>
          ))}
        </div>

        <div className="ms-auto d-flex align-items-center gap-3">
          <OrganizationSwitcher />
          <NotificationBell />
          <span className="text-muted small">{user?.name}</span>
          <button className="btn btn-sm btn-outline-secondary" onClick={() => void logout()}>
            Keluar
          </button>
        </div>
      </nav>
      {children}
    </div>
  );
};

export default AppLayout;
