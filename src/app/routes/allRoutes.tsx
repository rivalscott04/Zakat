import React from "react";
import Login from "../../features/auth/pages/Login";
import MuzakisPage from "../../features/muzakis";
import ZakatPage from "../../features/zakat";

export const publicRoutes = [{ path: "/login", component: <Login /> }];
export const authProtectedRoutes = [
  { path: "/muzakis", component: <MuzakisPage />, permission: "muzaki.view" },
  { path: "/zakat", component: <ZakatPage />, permission: "zakat.view" },
  { path: "/", component: <MuzakisPage />, permission: "muzaki.view" },
];
