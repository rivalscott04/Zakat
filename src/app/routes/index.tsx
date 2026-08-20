import React, { Suspense } from "react";
import { Routes, Route } from "react-router-dom";

//Layouts
import AppLayout from "../../features/layout/AppLayout";
import PublicLayout from "../../features/layout/PublicLayout";

//routes
import { authProtectedRoutes, publicRoutes } from "./allRoutes";
import AuthProtected from "./AuthProtected";
import { RequirePermission } from "../../features/auth/guards";

const Index = () => {
  return (
    <React.Fragment>
      <Routes>
        <Route>
          {publicRoutes.map((route, idx) => (
            <Route
              path={route.path}
              element={
                <PublicLayout>
                  <Suspense fallback={<RouteLoading />}>
                    {route.component}
                  </Suspense>
                </PublicLayout>
              }
              key={idx}
            />
          ))}
        </Route>

        <Route>
          {authProtectedRoutes.map((route, idx) => (
            <Route
              path={route.path}
              element={
                <AuthProtected>
                  <RequirePermission permission={route.permission ?? []}>
                    <AppLayout>
                      <Suspense fallback={<RouteLoading />}>
                        {route.component}
                      </Suspense>
                    </AppLayout>
                  </RequirePermission>
                </AuthProtected>
              }
              key={idx}
            />
          ))}
        </Route>
      </Routes>
    </React.Fragment>
  );
};

export default Index;

const RouteLoading = () => (
  <div className="d-flex min-vh-100 align-items-center justify-content-center text-muted">
    Memuat halaman...
  </div>
);
