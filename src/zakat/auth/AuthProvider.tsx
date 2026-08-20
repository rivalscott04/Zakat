import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { api, ensureCsrfCookie, getData, resetCsrfCookie } from "../api/client";
import type { CurrentUser, OrganizationSummary } from "../api/types";

interface AuthContextValue {
  user: CurrentUser | null;
  /** true selama pengecekan sesi awal, sebelum status login diketahui. */
  initialising: boolean;
  organizations: OrganizationSummary[];
  login: (email: string, password: string, remember: boolean) => Promise<void>;
  logout: () => Promise<void>;
  switchOrganization: (organizationId: string) => Promise<void>;
  refresh: () => Promise<void>;
  /** PRD 01 §27 — hanya untuk UX. Backend tetap yang menentukan izin sebenarnya. */
  can: (...permissions: string[]) => boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [user, setUser] = useState<CurrentUser | null>(null);
  const [organizations, setOrganizations] = useState<OrganizationSummary[]>([]);
  const [initialising, setInitialising] = useState(true);

  const loadSession = useCallback(async () => {
    try {
      const current = await getData<CurrentUser>("/auth/me");
      setUser(current);
      setOrganizations(await getData<OrganizationSummary[]>("/organizations/available"));
    } catch {
      setUser(null);
      setOrganizations([]);
    }
  }, []);

  useEffect(() => {
    loadSession().finally(() => setInitialising(false));
  }, [loadSession]);

  const login = useCallback(
    async (email: string, password: string, remember: boolean) => {
      await ensureCsrfCookie();
      await api.post("/auth/login", { email, password, remember });
      await loadSession();
    },
    [loadSession],
  );

  const logout = useCallback(async () => {
    try {
      await api.post("/auth/logout");
    } finally {
      resetCsrfCookie();
      setUser(null);
      setOrganizations([]);
    }
  }, []);

  const switchOrganization = useCallback(
    async (organizationId: string) => {
      await api.post("/auth/switch-organization", { organization_id: organizationId });
      await loadSession();
    },
    [loadSession],
  );

  const can = useCallback(
    (...permissions: string[]) => permissions.some((permission) => user?.permissions.includes(permission) ?? false),
    [user],
  );

  const value = useMemo<AuthContextValue>(
    () => ({ user, initialising, organizations, login, logout, switchOrganization, refresh: loadSession, can }),
    [user, initialising, organizations, login, logout, switchOrganization, loadSession, can],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = (): AuthContextValue => {
  const context = useContext(AuthContext);

  if (context === null) {
    throw new Error("useAuth harus dipakai di dalam AuthProvider.");
  }

  return context;
};
