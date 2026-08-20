import React from "react";
import Login from "../../features/auth/pages/Login";
import MuzakisPage from "../../features/muzakis";
import OrganizationsPage from "../../features/organizations";
import OrganizationDetail from "../../features/organizations/Detail";
import AmilsPage from "../../features/amils";
import UsersPage from "../../features/users";
import RolesPage from "../../features/roles";
import ZakatPage from "../../features/zakat";
import CalculationsPage from "../../features/calculations";
import CollectionsPage from "../../features/collections";
import FundsPage from "../../features/funds";

export const publicRoutes = [{ path: "/login", component: <Login /> }];
export const authProtectedRoutes = [
  { path: "/dashboard", component: <MuzakisPage />, permission: "muzaki.view" },
  { path: "/muzakis", component: <MuzakisPage />, permission: "muzaki.view" },
  { path: "/organizations", component: <OrganizationsPage />, permission: "organizations.view" },
  { path: "/organizations/:organizationId", component: <OrganizationDetail />, permission: "organizations.view" },
  { path: "/amils", component: <AmilsPage />, permission: "amils.view" },
  { path: "/users", component: <UsersPage />, permission: "users.view" },
  { path: "/roles", component: <RolesPage />, permission: "roles.view" },
  { path: "/zakat", component: <ZakatPage />, permission: "zakat.view" },
  { path: "/zakat/calculator", component: <CalculationsPage />, permission: "zakat.calculation.create" },
  { path: "/collections", component: <CollectionsPage />, permission: "collection.view" },
  { path: "/funds", component: <FundsPage />, permission: "fund.view" },
  { path: "/", component: <MuzakisPage />, permission: "muzaki.view" },
];
