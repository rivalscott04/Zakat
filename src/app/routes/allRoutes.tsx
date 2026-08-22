import { lazy } from "react";

const Login = lazy(() => import("../../features/auth/pages/Login"));
const ForgotPassword = lazy(() => import("../../features/auth/pages/ForgotPassword"));
const SetPassword = lazy(() => import("../../features/auth/pages/SetPassword"));
const MuzakisPage = lazy(() => import("../../features/muzakis"));
const OrganizationsPage = lazy(() => import("../../features/organizations"));
const OrganizationDetail = lazy(
  () => import("../../features/organizations/Detail"),
);
const AmilsPage = lazy(() => import("../../features/amils"));
const UsersPage = lazy(() => import("../../features/users"));
const RolesPage = lazy(() => import("../../features/roles"));
const ZakatPage = lazy(() => import("../../features/zakat"));
const CalculationsPage = lazy(() => import("../../features/calculations"));
const CollectionsPage = lazy(() => import("../../features/collections"));
const FundsPage = lazy(() => import("../../features/funds"));
const AccountingPage = lazy(() => import("../../features/accounting"));
const MustahiksPage = lazy(() => import("../../features/mustahiks"));
const AssessmentsPage = lazy(() => import("../../features/assessments"));
const ProgramsPage = lazy(() => import("../../features/programs"));
const DistributionsPage = lazy(() => import("../../features/distributions"));
const DistributionBatchesPage = lazy(
  () => import("../../features/distribution-batches"),
);
const PaymentsPage = lazy(() => import("../../features/payments"));
const ReportsPage = lazy(() => import("../../features/reports"));
const NotificationsPage = lazy(() => import("../../features/notifications"));
const AuditLogsPage = lazy(() => import("../../features/audit-logs"));
const SettingsPage = lazy(() => import("../../features/settings"));
const LandingPage = lazy(() => import("../../features/landing"));
const BankReconciliationPage = lazy(() => import("../../features/bank-reconciliation"));
const DocumentsPage = lazy(() => import("../../features/documents"));

export const publicRoutes = [
  { path: "/", component: <LandingPage /> },
  { path: "/login", component: <Login /> },
  // Ketiganya wajib publik: penerima undangan dan pemulihan password belum bisa
  // login. Path-nya harus sama persis dengan tautan pada email yang dikirim
  // backend (UserInvitationNotification dan ResetPassword::createUrlUsing).
  { path: "/forgot-password", component: <ForgotPassword /> },
  { path: "/reset-password", component: <SetPassword mode="reset" /> },
  { path: "/accept-invitation", component: <SetPassword mode="invitation" /> },
];
export const authProtectedRoutes = [
  { path: "/dashboard", component: <MuzakisPage />, permission: "muzaki.view" },
  { path: "/muzakis", component: <MuzakisPage />, permission: "muzaki.view" },
  {
    path: "/organizations",
    component: <OrganizationsPage />,
    permission: "organizations.view",
  },
  {
    path: "/organizations/:organizationId",
    component: <OrganizationDetail />,
    permission: "organizations.view",
  },
  { path: "/amils", component: <AmilsPage />, permission: "amils.view" },
  { path: "/users", component: <UsersPage />, permission: "users.view" },
  { path: "/roles", component: <RolesPage />, permission: "roles.view" },
  { path: "/zakat", component: <ZakatPage />, permission: "zakat.view" },
  {
    path: "/zakat/calculator",
    component: <CalculationsPage />,
    permission: "zakat.calculation.create",
  },
  {
    path: "/collections",
    component: <CollectionsPage />,
    permission: "collection.view",
  },
  {
    path: "/payments",
    component: <PaymentsPage />,
    permission: "payment.view",
  },
  { path: "/funds", component: <FundsPage />, permission: "fund.view" },
  {
    path: "/accounting",
    component: <AccountingPage />,
    permission: "accounting.account.view",
  },
  {
    path: "/mustahiks",
    component: <MustahiksPage />,
    permission: "mustahik.view",
  },
  {
    path: "/assessments",
    component: <AssessmentsPage />,
    permission: "assessment.view",
  },
  {
    path: "/programs",
    component: <ProgramsPage />,
    permission: "program.view",
  },
  {
    path: "/distributions",
    component: <DistributionsPage />,
    permission: "distribution.view",
  },
  {
    path: "/distribution-batches",
    component: <DistributionBatchesPage />,
    permission: "distribution.batch.view",
  },
  { path: "/bank-reconciliation", component: <BankReconciliationPage />, permission: "bank_reconciliation.view" },
  { path: "/documents", component: <DocumentsPage />, permission: "document.view" },
  { path: "/reports", component: <ReportsPage />, permission: "report.view" },
  { path: "/notifications", component: <NotificationsPage />, permission: "notification.view" },
  { path: "/audit-logs", component: <AuditLogsPage />, permission: "audit.view" },
  { path: "/settings", component: <SettingsPage />, permission: "setting.view" },
];
