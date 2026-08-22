/** Bentuk resource yang dikembalikan API ZETRA (PRD 01 dan PRD 02). */

export type UserStatus = "pending" | "active" | "inactive" | "suspended" | "locked";
export type OrganizationStatus = "draft" | "active" | "inactive" | "suspended" | "archived";
export type OrganizationType = "platform" | "organization" | "branch" | "unit" | "upz";
export type MembershipStatus = "pending" | "active" | "inactive" | "terminated";
export type MemberType = "employee" | "amil" | "volunteer" | "auditor" | "external";
export type AmilStatus = "active" | "inactive" | "suspended" | "ended";
export type MuzakiType = "individual" | "family" | "company" | "organization" | "institution";
export type MuzakiStatus = "lead" | "active" | "inactive" | "blocked" | "archived";

export interface Muzaki {
  id: string;
  organization_id: string;
  business_number: string;
  muzaki_type: MuzakiType;
  status: MuzakiStatus;
  display_name: string;
  registration_source: string;
  registered_at: string | null;
}

export type ZakatStatus = "draft" | "active" | "inactive" | "expired" | "archived";
export type CalculationMethod = "fixed" | "percentage" | "nisab_based" | "asset_based" | "income_based" | "harvest_based" | "livestock_based" | "custom";

export interface ZakatCategory { id: string; code: string; name: string; status: string; sort_order: number; }
export interface ZakatType { id: string; zakat_category_id: string; code: string; name: string; calculation_method: CalculationMethod; status: string; category?: ZakatCategory | null; }
export interface ZakatRuleParameter { parameter_code: string; name: string; data_type: string; is_required: boolean; default_value?: unknown; }
export interface ZakatRule { id: string; zakat_type_id: string; rule_code: string; name: string; version: number; status: ZakatStatus; effective_from: string; effective_until: string | null; type?: ZakatType | null; parameters?: ZakatRuleParameter[]; }
export type CollectionStatus = "draft" | "pending" | "partially_paid" | "paid" | "completed" | "expired" | "cancelled" | "refunded";
export interface Collection { id: string; collection_number: string; muzaki_id: string; calculation_id: string | null; zakat_type_id: string; collection_date: string; due_date: string | null; status: CollectionStatus; currency: string; expected_amount: string; paid_amount: string; remaining_amount: string; payment_count: number; source: string; overpayment_status: string; muzaki?: Muzaki | null; type?: ZakatType | null; }
export interface Fund { id: string; fund_code: string; name: string; fund_type: string; category: string | null; restriction_type: string; status: string; currency: string; opening_balance: string; current_balance: string; available_balance: string; reserved_balance: string; allocated_balance: string; distributed_balance: string; }
export interface AccountingAccount { id: string; account_code: string; account_name: string; account_type: string; normal_balance: string; is_postable: boolean; status: string; parent_id: string | null; }
export interface Mustahik { id: string; mustahik_number: string; mustahik_type: string; full_name: string; display_name: string; phone: string | null; verification_status: string; eligibility_status: string; status: string; }
export interface AssessmentRequest { id: string; request_number: string; mustahik_id: string; assessment_type: string; priority: string; reason: string | null; due_date: string | null; status: string; assessor_id: string | null; mustahik?: Mustahik | null; }
export interface Assessment { id: string; assessment_number: string; assessment_request_id: string; mustahik_id: string; assessment_type: string; status: string; total_score: string | null; recommendation: string | null; mustahik?: Mustahik | null; }
export interface Program { id: string; program_code: string; name: string; program_type: string; status: string; target_beneficiary: number | null; capacity_limit: number | null; waitlist_enabled?: boolean; }
export interface ProgramDashboard { active_programs: number; completed_programs: number; total_budget: string; committed_budget: string; disbursed_amount: string; remaining_budget: string; target_beneficiaries: number; active_beneficiaries: number; }
export type DistributionStatus =
  | "draft"
  | "pending_approval"
  | "approved"
  | "reserved"
  | "scheduled"
  | "processing"
  | "completed"
  | "partially_completed"
  | "failed"
  | "cancelled"
  | "reversed";

export interface DistributionBankTransfer {
  id: string;
  bank_name: string;
  account_holder_name: string;
  account_number_masked: string;
  transfer_reference: string | null;
  transfer_amount: string;
  transfer_date: string | null;
  status: string;
  failure_reason: string | null;
}

export interface DistributionProof {
  id: string;
  distribution_id: string;
  proof_type: string;
  file_id: string | null;
  reference_number: string | null;
  note: string | null;
  verified_by: string | null;
  verified_at: string | null;
  created_at: string | null;
}

export interface Distribution {
  id: string;
  distribution_number: string;
  distribution_type: string;
  source_type: string;
  status: DistributionStatus;
  priority: string;
  mustahik_id: string;
  program_id: string | null;
  fund_id: string;
  batch_id: string | null;
  currency: string;
  requested_amount: string;
  approved_amount: string;
  distributed_amount: string;
  remaining_amount: string;
  distribution_date: string | null;
  scheduled_date: string | null;
  description: string | null;
  rejection_reason: string | null;
  cancellation_reason: string | null;
  reversal_reason: string | null;
  failure_reason: string | null;
  failure_note: string | null;
  retry_count: number;
  allowed_transitions: DistributionStatus[];
  mustahik?: Mustahik | null;
  fund?: { id: string; name: string } | null;
  bank_transfers?: DistributionBankTransfer[];
  proofs?: DistributionProof[];
  confirmation?: { id: string; confirmation_method: string; confirmed_at: string } | null;
}

export interface DistributionSummary {
  by_status: Record<DistributionStatus, { total: number; distributed_amount: string }>;
}

export interface DistributionBeneficiary {
  id: string;
  batch_id: string;
  distribution_id: string | null;
  mustahik_id: string;
  approved_amount: string;
  distributed_amount: string;
  status: string;
  failure_reason: string | null;
  failure_note: string | null;
  mustahik?: Mustahik | null;
}

export interface DistributionBatch {
  id: string;
  batch_number: string;
  name: string;
  program_id: string | null;
  fund_id: string;
  distribution_type: string;
  total_amount: string;
  total_beneficiary: number;
  status: string;
  approved_at: string | null;
  allowed_transitions: string[];
  fund?: { id: string; name: string } | null;
  beneficiaries?: DistributionBeneficiary[];
}

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

export type PaymentStatus =
  | "created"
  | "pending"
  | "paid"
  | "failed"
  | "expired"
  | "cancelled"
  | "refunded";

export interface PaymentProvider {
  id: string;
  provider_code: string;
  name: string;
  driver: string;
  status: "active" | "inactive";
  sandbox_mode: boolean;
  configured_keys: string[];
  webhook_secret_configured: boolean;
  webhook_url: string;
  created_at: string | null;
}

export interface PaymentRefund {
  id: string;
  payment_id: string;
  refund_number: string;
  amount: string;
  reason: string;
  status: string;
  rejection_reason: string | null;
  requested_at: string | null;
  processed_at: string | null;
}

export interface PaymentWebhookRecord {
  id: string;
  event_id: string | null;
  event_type: string | null;
  signature_valid: boolean;
  status: string;
  error_message: string | null;
  received_at: string | null;
  processed_at: string | null;
}

export interface Payment {
  id: string;
  payment_number: string;
  provider_id: string;
  provider_reference: string | null;
  source_type: string;
  source_id: string;
  payer_name: string | null;
  amount: string;
  currency: string;
  payment_method: string | null;
  payment_url: string | null;
  status: PaymentStatus;
  allowed_transitions: PaymentStatus[];
  refundable_amount: string;
  expires_at: string | null;
  paid_at: string | null;
  verification_reason: string | null;
  cancellation_reason: string | null;
  failure_reason: string | null;
  failure_note: string | null;
  provider?: { id: string; provider_code: string; name: string } | null;
  webhooks?: PaymentWebhookRecord[];
  refunds?: PaymentRefund[];
}

export interface PaymentSummary {
  by_status: Record<PaymentStatus, { total: number; amount: string }>;
}
export interface BankAccount { id: string; account_code: string; bank_name: string; account_name: string; account_number_masked: string; currency: string; current_balance: string; status: string; }
export interface BankTransaction { id: string; transaction_reference: string; transaction_date: string; description: string | null; debit_amount: string; credit_amount: string; match_status: string; duplicate_status: string; }

export interface BankAccountItem {
  id: string;
  account_code: string;
  bank_name: string;
  account_name: string;
  account_number_masked: string;
  currency: string;
  status: string;
}

export interface BankTransactionItem {
  id: string;
  transaction_reference: string;
  transaction_date: string | null;
  description: string | null;
  debit_amount: string;
  credit_amount: string;
  match_status: string;
  duplicate_status: string;
}

export interface ReconciliationSessionItem {
  id: string;
  session_number: string;
  bank_account_id: string;
  period_start: string | null;
  period_end: string | null;
  opening_balance: string;
  closing_balance: string;
  matched_amount: string;
  unmatched_amount: string;
  difference_amount: string;
  status: string;
}

export interface ReconciliationSummary {
  opening_balance: string;
  total_credit: string;
  total_debit: string;
  closing_balance: string;
  expected_closing_balance: string;
  difference_amount: string;
  balance_valid: boolean;
  total_transactions: number;
  matched: number;
  partially_matched: number;
  unmatched: number;
  excluded: number;
  possible_duplicates: number;
}

export interface DocumentItem {
  id: string;
  document_number: string | null;
  mime_type: string;
  checksum: string;
  document_name: string;
  original_filename: string;
  document_type: string;
  category: string | null;
  extension: string;
  file_size: number;
  version: number;
  visibility: string;
  status: string;
  previewable: boolean;
  created_at: string | null;
}

export type AuditSeverity = "INFO" | "NOTICE" | "WARNING" | "CRITICAL";

export interface AuditLogItem {
  id: string;
  audit_number: string | null;
  event_name: string | null;
  event_category: string | null;
  module_code: string | null;
  severity: AuditSeverity;
  action: string;
  description: string | null;
  entity_type: string | null;
  entity_id: string | null;
  entity_reference: string | null;
  actor_id: string | null;
  actor_name: string | null;
  actor_type: string;
  ip_address: string | null;
  request_id: string | null;
  occurred_at: string | null;
  has_changes: boolean;
  old_values?: Record<string, unknown> | null;
  new_values?: Record<string, unknown> | null;
  metadata?: Record<string, unknown> | null;
}

export interface AuditSummary {
  total: number;
  by_severity: Record<string, number>;
  by_category: Record<string, number>;
  by_module: Record<string, number>;
}

// ---------------------------------------------------------------- modul 20

export interface SettingItem {
  key: string;
  label: string;
  group: string;
  scope: "GLOBAL" | "ORGANIZATION";
  type: "integer" | "boolean" | "string";
  value: string | number | boolean;
  default_value: string | number | boolean;
  source: "DEFAULT" | "GLOBAL" | "ORGANIZATION";
}

// ---------------------------------------------------------------- modul 16

export type NotificationPriority = "low" | "normal" | "high" | "urgent";

export interface NotificationDeliveryItem {
  channel: string;
  status: string;
  attempt_count: number;
  max_attempts: number;
  error_message: string | null;
  sent_at: string | null;
}

export interface NotificationItem {
  id: string;
  notification_number: string;
  event_name: string | null;
  title: string;
  message: string;
  data: Record<string, unknown> | null;
  priority: NotificationPriority;
  status: string;
  read_at: string | null;
  scheduled_at: string | null;
  sent_at: string | null;
  created_at: string | null;
  deliveries?: NotificationDeliveryItem[];
}

export interface NotificationTemplateItem {
  id: string;
  template_code: string;
  name: string;
  channel: string;
  subject: string | null;
  content: string;
  locale: string;
  status: string;
  variables: string[];
  updated_at: string | null;
}

export interface NotificationRuleItem {
  id: string;
  event_name: string;
  template_id: string | null;
  template_code?: string | null;
  channels: string[];
  recipient_strategy: string;
  recipient_config: Record<string, unknown> | null;
  priority: NotificationPriority;
  enabled: boolean;
}
