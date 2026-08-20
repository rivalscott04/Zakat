import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  Badge,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Form,
  FormGroup,
  Input,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Row,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import ErrorAlert from "../components/ErrorAlert";
import StatusBadge from "../components/StatusBadge";
import { api, getData, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type {
  Distribution,
  DistributionStatus,
  DistributionSummary,
} from "../api/types";

type DistributionForm = {
  mustahik_id: string;
  fund_id: string;
  program_id: string;
  distribution_type: string;
  source_type: string;
  requested_amount: string;
  description: string;
};

const emptyForm: DistributionForm = {
  mustahik_id: "",
  fund_id: "",
  program_id: "",
  distribution_type: "cash",
  source_type: "direct",
  requested_amount: "",
  description: "",
};

/** PRD 12AD §67 — kartu ringkasan yang ditampilkan di atas daftar. */
const SUMMARY_CARDS: { status: DistributionStatus; label: string }[] = [
  { status: "pending_approval", label: "Menunggu Approval" },
  { status: "reserved", label: "Dana Ditahan" },
  { status: "processing", label: "Sedang Diproses" },
  { status: "completed", label: "Selesai" },
];

/** Aksi lifecycle PRD 12J §26, dipetakan ke endpoint dan permission-nya. */
const ACTIONS: {
  target: DistributionStatus;
  label: string;
  endpoint: string;
  permission: string;
  color: string;
  needsReason?: boolean;
}[] = [
  { target: "pending_approval", label: "Ajukan", endpoint: "submit", permission: "distribution.submit", color: "primary" },
  { target: "approved", label: "Setujui", endpoint: "approve", permission: "distribution.approve", color: "success" },
  { target: "reserved", label: "Tahan Dana", endpoint: "reserve", permission: "distribution.reserve", color: "info" },
  { target: "scheduled", label: "Jadwalkan", endpoint: "schedule", permission: "distribution.schedule", color: "secondary" },
  { target: "processing", label: "Proses", endpoint: "process", permission: "distribution.process", color: "primary" },
  { target: "failed", label: "Tandai Gagal", endpoint: "fail", permission: "distribution.process", color: "warning" },
  { target: "cancelled", label: "Batalkan", endpoint: "cancel", permission: "distribution.cancel", color: "danger", needsReason: true },
  { target: "reversed", label: "Reversal", endpoint: "reverse", permission: "distribution.reverse", color: "danger", needsReason: true },
];

const rupiah = (value: string) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(Number(value));

const DistributionsPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<Distribution[]>([]);
  const [summary, setSummary] = useState<DistributionSummary | null>(null);
  const [detail, setDetail] = useState<Distribution | null>(null);
  const [statusFilter, setStatusFilter] = useState("");
  const [search, setSearch] = useState("");
  const [error, setError] = useState<unknown>(null);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState<DistributionForm>(emptyForm);
  const [reasonFor, setReasonFor] = useState<{ endpoint: string; label: string } | null>(null);
  const [reason, setReason] = useState("");
  const [completeAmount, setCompleteAmount] = useState("");

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params = { ...(statusFilter ? { status: statusFilter } : {}), ...(search ? { search } : {}) };
      setRows((await getPage<Distribution>("/distributions", params)).data);
      setSummary(await getData<DistributionSummary>("/distributions/summary"));
      setError(null);
    } catch (caught) {
      setError(caught);
    } finally {
      setLoading(false);
    }
  }, [statusFilter, search]);

  useEffect(() => {
    void load();
  }, [load]);

  const openDetail = async (id: string) => {
    try {
      setDetail(await getData<Distribution>(`/distributions/${id}`));
      setCompleteAmount("");
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  const create = async (event: React.FormEvent) => {
    event.preventDefault();
    try {
      await api.post("/distributions", {
        mustahik_id: form.mustahik_id,
        fund_id: form.fund_id,
        program_id: form.program_id || null,
        distribution_type: form.distribution_type,
        source_type: form.program_id ? "program" : form.source_type,
        requested_amount: Number(form.requested_amount),
        description: form.description || null,
      });
      setForm(emptyForm);
      setError(null);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const runAction = async (endpoint: string, payload: Record<string, unknown> = {}) => {
    if (!detail) return;
    try {
      const body =
        endpoint === "schedule"
          ? { scheduled_date: new Date().toISOString().slice(0, 10), ...payload }
          : endpoint === "fail"
            ? { failure_reason: "system_error", ...payload }
            : payload;

      const response = await api.post(`/distributions/${detail.id}/${endpoint}`, body);
      setDetail(response.data.data as Distribution);
      setError(null);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const submitReason = async () => {
    if (!reasonFor) return;
    await runAction(reasonFor.endpoint, { reason });
    setReasonFor(null);
    setReason("");
  };

  const availableActions = useMemo(() => {
    if (!detail) return [];
    return ACTIONS.filter(
      (action) => detail.allowed_transitions.includes(action.target) && can(action.permission),
    );
  }, [detail, can]);

  const columns: Column<Distribution>[] = [
    {
      header: "Distribution",
      render: (row) => (
        <>
          <button type="button" className="btn btn-link p-0 fw-medium" onClick={() => void openDetail(row.id)}>
            {row.distribution_number}
          </button>
          <div className="text-muted fs-12">{row.mustahik?.display_name ?? row.mustahik_id}</div>
        </>
      ),
    },
    { header: "Jenis", render: (row) => `${row.source_type} · ${row.distribution_type}` },
    { header: "Diminta", className: "text-end", render: (row) => rupiah(row.requested_amount) },
    { header: "Disalurkan", className: "text-end", render: (row) => rupiah(row.distributed_amount) },
    { header: "Status", render: (row) => <StatusBadge status={row.status} /> },
  ];

  document.title = "Penyaluran | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Distribution" pageTitle="Penyaluran" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Row className="mb-3">
          {SUMMARY_CARDS.map((card) => (
            <Col md={3} key={card.status}>
              <Card className="card-animate">
                <CardBody>
                  <p className="text-uppercase text-muted fs-12 mb-2">{card.label}</p>
                  <h4 className="mb-1">{summary?.by_status?.[card.status]?.total ?? 0}</h4>
                  <p className="text-muted mb-0 fs-12">
                    {rupiah(summary?.by_status?.[card.status]?.distributed_amount ?? "0")}
                  </p>
                </CardBody>
              </Card>
            </Col>
          ))}
        </Row>

        <Card>
          <CardBody>
            {can("distribution.create") ? (
              <Form onSubmit={create} className="border rounded p-3 mb-4">
                <h5 className="mb-3">Buat Distribution</h5>
                <Row>
                  <Col md={3}>
                    <FormGroup>
                      <Label for="mustahik_id">ID Mustahik</Label>
                      <Input
                        id="mustahik_id"
                        required
                        value={form.mustahik_id}
                        onChange={(event) => setForm({ ...form, mustahik_id: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={3}>
                    <FormGroup>
                      <Label for="fund_id">ID Fund</Label>
                      <Input
                        id="fund_id"
                        required
                        value={form.fund_id}
                        onChange={(event) => setForm({ ...form, fund_id: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="program_id">ID Program</Label>
                      <Input
                        id="program_id"
                        placeholder="opsional"
                        value={form.program_id}
                        onChange={(event) => setForm({ ...form, program_id: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="distribution_type">Jenis</Label>
                      <Input
                        id="distribution_type"
                        type="select"
                        value={form.distribution_type}
                        onChange={(event) => setForm({ ...form, distribution_type: event.target.value })}
                      >
                        {["cash", "bank_transfer", "goods", "service", "voucher", "scholarship", "business_capital", "emergency", "other"].map(
                          (type) => (
                            <option key={type} value={type}>
                              {type}
                            </option>
                          ),
                        )}
                      </Input>
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="requested_amount">Nominal</Label>
                      <Input
                        id="requested_amount"
                        required
                        type="number"
                        min="1"
                        value={form.requested_amount}
                        onChange={(event) => setForm({ ...form, requested_amount: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                </Row>
                <Row>
                  <Col md={10}>
                    <FormGroup>
                      <Label for="description">Keterangan</Label>
                      <Input
                        id="description"
                        value={form.description}
                        onChange={(event) => setForm({ ...form, description: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={2} className="d-flex align-items-end">
                    <FormGroup className="w-100">
                      <Button color="success" type="submit" className="w-100">
                        Simpan
                      </Button>
                    </FormGroup>
                  </Col>
                </Row>
              </Form>
            ) : null}

            <Row className="g-2 mb-3">
              <Col md={4}>
                <Input
                  type="search"
                  placeholder="Cari nomor distribution..."
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                />
              </Col>
              <Col md={3}>
                <Input type="select" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                  <option value="">Semua status</option>
                  {["draft", "pending_approval", "approved", "reserved", "scheduled", "processing", "completed", "partially_completed", "failed", "cancelled", "reversed"].map(
                    (status) => (
                      <option key={status} value={status}>
                        {status}
                      </option>
                    ),
                  )}
                </Input>
              </Col>
            </Row>

            <DataTable
              columns={columns}
              rows={rows}
              loading={loading}
              rowKey={(row) => row.id}
              emptyMessage="Belum ada distribution."
            />
          </CardBody>
        </Card>

        <Modal isOpen={detail !== null} toggle={() => setDetail(null)} size="lg" centered>
          <ModalHeader toggle={() => setDetail(null)}>
            {detail?.distribution_number} <StatusBadge status={detail?.status ?? "draft"} />
          </ModalHeader>
          <ModalBody>
            {detail ? (
              <>
                <Row className="mb-3">
                  {[
                    ["Penerima", detail.mustahik?.display_name ?? detail.mustahik_id],
                    ["Fund", detail.fund?.name ?? detail.fund_id],
                    ["Jenis", `${detail.source_type} · ${detail.distribution_type}`],
                    ["Diminta", rupiah(detail.requested_amount)],
                    ["Disetujui", rupiah(detail.approved_amount)],
                    ["Disalurkan", rupiah(detail.distributed_amount)],
                    ["Sisa", rupiah(detail.remaining_amount)],
                    ["Tanggal Salur", detail.distribution_date ?? "-"],
                  ].map(([label, value]) => (
                    <Col md={3} key={label} className="mb-2">
                      <div className="text-muted fs-12 text-uppercase">{label}</div>
                      <div className="fw-medium">{value}</div>
                    </Col>
                  ))}
                </Row>

                {detail.failure_reason ? (
                  <p className="text-danger fs-13">
                    Gagal: {detail.failure_reason} {detail.failure_note ? `— ${detail.failure_note}` : ""} (percobaan{" "}
                    {detail.retry_count}x)
                  </p>
                ) : null}
                {detail.cancellation_reason ? (
                  <p className="text-danger fs-13">Dibatalkan: {detail.cancellation_reason}</p>
                ) : null}
                {detail.reversal_reason ? <p className="text-danger fs-13">Reversal: {detail.reversal_reason}</p> : null}

                {detail.bank_transfers?.length ? (
                  <p className="fs-13">
                    Transfer ke {detail.bank_transfers[0].bank_name} a.n. {detail.bank_transfers[0].account_holder_name}{" "}
                    <Badge color="light" className="text-body">
                      {detail.bank_transfers[0].account_number_masked}
                    </Badge>
                  </p>
                ) : null}

                {detail.confirmation ? (
                  <p className="text-success fs-13">
                    Penerimaan dikonfirmasi ({detail.confirmation.confirmation_method}).
                  </p>
                ) : null}

                <div className="d-flex flex-wrap gap-2 mt-3">
                  {availableActions.map((action) => (
                    <Button
                      key={action.endpoint}
                      color={action.color}
                      size="sm"
                      onClick={() =>
                        action.needsReason
                          ? setReasonFor({ endpoint: action.endpoint, label: action.label })
                          : void runAction(action.endpoint)
                      }
                    >
                      {action.label}
                    </Button>
                  ))}

                  {detail.status === "processing" && can("distribution.complete") ? (
                    <div className="d-flex gap-2">
                      <Input
                        type="number"
                        min="1"
                        placeholder={`maks ${detail.remaining_amount}`}
                        value={completeAmount}
                        onChange={(event) => setCompleteAmount(event.target.value)}
                        style={{ maxWidth: 180 }}
                      />
                      <Button
                        color="success"
                        size="sm"
                        onClick={() =>
                          void runAction("complete", completeAmount ? { amount: Number(completeAmount) } : {})
                        }
                      >
                        Realisasikan
                      </Button>
                    </div>
                  ) : null}

                  {["completed", "partially_completed"].includes(detail.status) &&
                  !detail.confirmation &&
                  can("distribution.confirm") ? (
                    <Button
                      color="info"
                      size="sm"
                      onClick={() => void runAction("confirm", { confirmation_method: "manual" })}
                    >
                      Konfirmasi Penerimaan
                    </Button>
                  ) : null}
                </div>
              </>
            ) : null}
          </ModalBody>
        </Modal>

        <Modal isOpen={reasonFor !== null} toggle={() => setReasonFor(null)} centered>
          <ModalHeader toggle={() => setReasonFor(null)}>{reasonFor?.label}</ModalHeader>
          <ModalBody>
            <FormGroup>
              <Label for="reason">Alasan</Label>
              <Input
                id="reason"
                type="textarea"
                rows={3}
                value={reason}
                onChange={(event) => setReason(event.target.value)}
              />
            </FormGroup>
            <div className="text-end">
              <Button color="light" className="me-2" onClick={() => setReasonFor(null)}>
                Batal
              </Button>
              <Button color="danger" disabled={reason.trim() === ""} onClick={() => void submitReason()}>
                Lanjutkan
              </Button>
            </div>
          </ModalBody>
        </Modal>
      </Container>
    </div>
  );
};

export default DistributionsPage;
