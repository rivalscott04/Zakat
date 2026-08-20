/** Bentuk resource yang dikembalikan API Zakat OS (PRD 01 dan PRD 02). */

export type UserStatus = "pending" | "active" | "inactive" | "suspended" | "locked";
export type OrganizationStatus = "draft" | "active" | "inactive" | "suspended" | "archived";
export type OrganizationType = "platform" | "organization" | "branch" | "unit" | "upz";
export type MembershipStatus = "pending" | "active" | "inactive" | "terminated";
export type MemberType = "employee" | "amil" | "volunteer" | "auditor" | "external";
export type AmilStatus = "active" | "inactive" | "suspended" | "ended";

export interface OrganizationSummary {
  id: string;
  code: string;
  name: string;
}

export interface RoleSummary {
  id: string;
  code: string;
  name: string;
}

export interface Role extends RoleSummary {
  organization_id: string | null;
  description: string | null;
  is_system: boolean;
  is_active: boolean;
  permissions?: string[];
  created_at: string | null;
}

export interface Permission {
  id: string;
  name: string;
  module: string;
  resource: string;
  action: string;
  description: string | null;
}

export interface CurrentUser {
  id: string;
  name: string;
  email: string;
  username: string | null;
  status: UserStatus;
  home_organization: OrganizationSummary | null;
  organization: OrganizationSummary | null;
  roles: Role[];
  permissions: string[];
}

export interface User {
  id: string;
  name: string;
  email: string;
  username: string | null;
  phone: string | null;
  status: UserStatus;
  organization: OrganizationSummary | null;
  roles: RoleSummary[];
  last_login_at: string | null;
  created_at: string | null;
}

export interface Organization {
  id: string;
  business_number: string;
  code: string;
  name: string;
  legal_name: string | null;
  organization_type: OrganizationType;
  status: OrganizationStatus;
  email: string | null;
  phone: string | null;
  website: string | null;
  currency: string;
  timezone: string;
  locale: string;
  parent: OrganizationSummary | null;
  children_count?: number;
  members_count?: number;
  amils_count?: number;
  created_at: string | null;
}

export interface OrganizationMember {
  id: string;
  organization_id: string;
  member_type: MemberType;
  status: MembershipStatus;
  joined_at: string | null;
  left_at: string | null;
  user: { id: string; name: string; email: string; status: UserStatus } | null;
}

export interface AmilAssignment {
  id: string;
  amil_id: string;
  assignment_type: string;
  status: "active" | "ended";
  started_at: string | null;
  ended_at: string | null;
}

export interface Amil {
  id: string;
  organization_id: string;
  business_number: string;
  name: string;
  employee_number: string | null;
  email: string | null;
  phone: string | null;
  status: AmilStatus;
  joined_at: string | null;
  ended_at: string | null;
  has_user_account: boolean;
  user: { id: string; name: string; email: string; status: UserStatus } | null;
  assignments?: AmilAssignment[];
  active_assignments?: AmilAssignment[];
}

export interface Session {
  id: string;
  ip_address: string | null;
  user_agent: string | null;
  last_activity_at: string;
  is_current: boolean;
}
