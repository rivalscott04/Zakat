import React, { useCallback, useEffect, useState } from "react";
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
import type { Payment, PaymentProvider, PaymentSummary } from "../api/types";

const rupiah = (value: string) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(Number(value));

const SUMMARY: { status: keyof PaymentSummary["by_status"]; label: string }[] = [
  { status: "pending", label: "Menunggu Bayar" },
  { status: "paid", label: "Lunas" },
  { status: "failed", label: "Gagal" },
  { status: "expired", label: "Kedaluwarsa" },
];

const emptyPayment = { provider_id: "", source_id: "", source_type: "zakat", amount: "", payer_name: "" };
const emptyProvider = { provider_code: "", name: "", driver: "manual", webhook_secret: "" };

const PaymentsPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<Payment[]>([]);
  const [providers, setProviders] = useState<PaymentProvider[]>([]);
  const [summary, setSummary] = useState<PaymentSummary | null>(null);
  const [detail, setDetail] = useState<Payment | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<unknown>(null);
  const [form, setForm] = useState(emptyPayment);
  const [providerForm, setProviderForm] = useState(emptyProvider);
  const [reasonFor, setReasonFor] = useState<{ endpoint: string; label: string } | null>(null);
  const [reason, setReason] = useState("");

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setRows((await getPage<Payment>("/payments")).data);
      setSummary(await getData<PaymentSummary>("/payments/summary"));
      if (can("payment.provider.view")) {
        setProviders(await getData<PaymentProvider[]>("/payment-providers"));
      }
      setError(null);
    } catch (caught) {
      setError(caught);
    } finally {
      setLoading(false);
    }
  }, [can]);

  useEffect(() => {
    void load();
  }, [load]);

  const openDetail = async (id: string) => {
    try {
      setDetail(await getData<Payment>(`/payments/${id}`));
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  const submit = async (event: React.FormEvent, action: () => Promise<unknown>) => {
    event.preventDefault();
    try {
      await action();
      setError(null);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const runAction = async (endpoint: string, payload: Record<string, unknown> = {}) => {
    if (!detail) return;
    try {
      const response = await api.post(`/payments/${detail.id}/${endpoint}`, payload);
      setDetail(response.data.data as Payment);
      setError(null);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const columns: Column<Payment>[] = [
    {
      header: "Payment",
      render: (row) => (
        <>
          <button type="button" className="btn btn-link p-0 fw-medium" onClick={() => void openDetail(row.id)}>
            {row.payment_number}
          </button>
          <div className="text-muted fs-12">{row.provider?.name ?? row.provider_id}</div>
        </>
      ),
    },
    { header: "Pembayar", render: (row) => row.payer_name ?? "-" },
    { header: "Sumber", render: (row) => row.source_type },
    { header: "Nominal", className: "text-end", render: (row) => rupiah(row.amount) },
    { header: "Status", render: (row) => <StatusBadge status={row.status} /> },
  ];

  document.title = "Payment | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Payment" pageTitle="Penerimaan" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Row className="mb-3">
          {SUMMARY.map((card) => (
            <Col md={3} key={card.status}>
              <Card>
                <CardBody>
                  <p className="text-uppercase text-muted fs-12 mb-2">{card.label}</p>
                  <h4 className="mb-1">{summary?.by_status?.[card.status]?.total ?? 0}</h4>
                  <p className="text-muted mb-0 fs-12">{rupiah(summary?.by_status?.[card.status]?.amount ?? "0")}</p>
                </CardBody>
              </Card>
            </Col>
          ))}
        </Row>

        {can("payment.provider.view") ? (
          <Card className="mb-3">
            <CardBody>
              <h5 className="mb-3">Payment Provider</h5>

              {providers.length === 0 ? (
                <p className="text-muted fs-13">
                  Belum ada provider. Driver yang tersedia saat ini hanya <code>manual</code>; driver penyedia
                  pembayaran ditambahkan setelah layanannya dipilih.
                </p>
              ) : (
                <ul className="list-group mb-3">
                  {providers.map((provider) => (
                    <li className="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-2" key={provider.id}>
                      <div>
                        <span className="fw-medium">{provider.name}</span>{" "}
                        <span className="text-muted">({provider.provider_code} · {provider.driver})</span>
                        <div className="text-muted fs-12">
                          Webhook: <code>{provider.webhook_url}</code>
                        </div>
                      </div>
                      <div className="d-flex align-items-center gap-2">
                        {provider.sandbox_mode ? <Badge color="warning">sandbox</Badge> : null}
                        <StatusBadge status={provider.status} />
                        {can("payment.provider.manage") ? (
                          <Button
                            size="sm"
                            color={provider.status === "active" ? "soft-danger" : "soft-success"}
                            onClick={() =>
                              void submit({ preventDefault: () => {} } as React.FormEvent, () =>
                                api.post(`/payment-providers/${provider.id}/${provider.status === "active" ? "deactivate" : "activate"}`),
                              )
                            }
                          >
                            {provider.status === "active" ? "Nonaktifkan" : "Aktifkan"}
                          </Button>
                        ) : null}
                      </div>
                    </li>
                  ))}
                </ul>
              )}

              {can("payment.provider.manage") ? (
                <Form
                  className="border rounded p-3"
                  onSubmit={(event) =>
                    void submit(event, async () => {
                      await api.post("/payment-providers", providerForm);
                      setProviderForm(emptyProvider);
                    })
                  }
                >
                  <Row>
                    <Col md={3}>
                      <FormGroup>
                        <Label for="provider_code">Kode</Label>
                        <Input id="provider_code" required value={providerForm.provider_code} onChange={(e) => setProviderForm({ ...providerForm, provider_code: e.target.value })} />
                      </FormGroup>
                    </Col>
                    <Col md={3}>
                      <FormGroup>
                        <Label for="provider_name">Nama</Label>
                        <Input id="provider_name" required value={providerForm.name} onChange={(e) => setProviderForm({ ...providerForm, name: e.target.value })} />
                      </FormGroup>
                    </Col>
                    <Col md={2}>
                      <FormGroup>
                        <Label for="driver">Driver</Label>
                        <Input id="driver" type="select" value={providerForm.driver} onChange={(e) => setProviderForm({ ...providerForm, driver: e.target.value })}>
                          <option value="manual">manual</option>
                        </Input>
                      </FormGroup>
                    </Col>
                    <Col md={4}>
                      <FormGroup>
                        <Label for="webhook_secret">Webhook Secret (min 32 karakter)</Label>
                        <Input id="webhook_secret" required minLength={32} value={providerForm.webhook_secret} onChange={(e) => setProviderForm({ ...providerForm, webhook_secret: e.target.value })} />
                      </FormGroup>
                    </Col>
                  </Row>
                  <Button color="success" type="submit">Tambah Provider</Button>
                </Form>
              ) : null}
            </CardBody>
          </Card>
        ) : null}

        <Card>
          <CardBody>
            {can("payment.create") ? (
              <Form
                className="border rounded p-3 mb-4"
                onSubmit={(event) =>
                  void submit(event, async () => {
                    await api.post("/payments", { ...form, amount: Number(form.amount) });
                    setForm(emptyPayment);
                  })
                }
              >
                <h5 className="mb-3">Buat Payment</h5>
                <Row>
                  <Col md={3}>
                    <FormGroup>
                      <Label for="pay_provider">Provider</Label>
                      <Input id="pay_provider" type="select" required value={form.provider_id} onChange={(e) => setForm({ ...form, provider_id: e.target.value })}>
                        <option value="">Pilih provider aktif</option>
                        {providers.filter((p) => p.status === "active").map((p) => (
                          <option key={p.id} value={p.id}>{p.name}</option>
                        ))}
                      </Input>
                    </FormGroup>
                  </Col>
                  <Col md={3}>
                    <FormGroup>
                      <Label for="pay_source">ID Collection</Label>
                      <Input id="pay_source" required value={form.source_id} onChange={(e) => setForm({ ...form, source_id: e.target.value })} />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="pay_type">Jenis</Label>
                      <Input id="pay_type" type="select" value={form.source_type} onChange={(e) => setForm({ ...form, source_type: e.target.value })}>
                        {["zakat", "infaq", "sedekah", "donation", "campaign", "other"].map((t) => (
                          <option key={t} value={t}>{t}</option>
                        ))}
                      </Input>
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="pay_amount">Nominal</Label>
                      <Input id="pay_amount" type="number" min="1" required value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="pay_payer">Pembayar</Label>
                      <Input id="pay_payer" value={form.payer_name} onChange={(e) => setForm({ ...form, payer_name: e.target.value })} />
                    </FormGroup>
                  </Col>
                </Row>
                <Button color="success" type="submit">Simpan</Button>
              </Form>
            ) : null}

            <DataTable columns={columns} rows={rows} loading={loading} rowKey={(row) => row.id} emptyMessage="Belum ada payment." />
          </CardBody>
        </Card>

        <Modal isOpen={detail !== null} toggle={() => setDetail(null)} size="lg" centered>
          <ModalHeader toggle={() => setDetail(null)}>
            {detail?.payment_number} <StatusBadge status={detail?.status ?? "created"} />
          </ModalHeader>
          <ModalBody>
            {detail ? (
              <>
                <Row className="mb-3">
                  {[
                    ["Provider", detail.provider?.name ?? detail.provider_id],
                    ["Referensi Provider", detail.provider_reference ?? "-"],
                    ["Nominal", rupiah(detail.amount)],
                    ["Dapat Direfund", rupiah(detail.refundable_amount)],
                    ["Kedaluwarsa", detail.expires_at ? new Date(detail.expires_at).toLocaleString("id-ID") : "-"],
                    ["Dibayar", detail.paid_at ? new Date(detail.paid_at).toLocaleString("id-ID") : "-"],
                  ].map(([label, value]) => (
                    <Col md={4} key={label} className="mb-2">
                      <div className="text-muted fs-12 text-uppercase">{label}</div>
                      <div className="fw-medium">{value}</div>
                    </Col>
                  ))}
                </Row>

                {detail.failure_reason ? (
                  <p className="text-danger fs-13">Gagal: {detail.failure_reason} {detail.failure_note ? `— ${detail.failure_note}` : ""}</p>
                ) : null}
                {detail.cancellation_reason ? <p className="text-danger fs-13">Dibatalkan: {detail.cancellation_reason}</p> : null}
                {detail.verification_reason ? <p className="text-success fs-13">Verifikasi manual: {detail.verification_reason}</p> : null}

                {detail.webhooks?.length ? (
                  <>
                    <h6 className="mt-3">Riwayat Webhook</h6>
                    <ul className="list-group mb-3">
                      {detail.webhooks.map((hook) => (
                        <li className="list-group-item d-flex justify-content-between fs-13" key={hook.id}>
                          <span>{hook.event_type ?? hook.event_id ?? "tanpa event"}</span>
                          <span>
                            {hook.signature_valid ? <Badge color="success">tanda tangan sah</Badge> : <Badge color="danger">tanda tangan gagal</Badge>}{" "}
                            <StatusBadge status={hook.status} />
                          </span>
                        </li>
                      ))}
                    </ul>
                  </>
                ) : null}

                <div className="d-flex flex-wrap gap-2 mt-3">
                  {detail.allowed_transitions.includes("paid") && can("payment.verify") ? (
                    <Button color="success" size="sm" onClick={() => setReasonFor({ endpoint: "verify", label: "Verifikasi Manual" })}>
                      Verifikasi Manual
                    </Button>
                  ) : null}
                  {detail.allowed_transitions.includes("cancelled") && can("payment.cancel") ? (
                    <Button color="danger" size="sm" onClick={() => setReasonFor({ endpoint: "cancel", label: "Batalkan Payment" })}>
                      Batalkan
                    </Button>
                  ) : null}
                  {can("payment.refresh") ? (
                    <Button color="secondary" size="sm" onClick={() => void runAction("refresh-status")}>
                      Perbarui Status
                    </Button>
                  ) : null}
                  {can("payment.reconciliation.manage") ? (
                    <Button color="info" size="sm" onClick={() => void runAction("reconcile")}>
                      Rekonsiliasi
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
              <Label for="pay_reason">Alasan</Label>
              <Input id="pay_reason" type="textarea" rows={3} value={reason} onChange={(e) => setReason(e.target.value)} />
            </FormGroup>
            <div className="text-end">
              <Button color="light" className="me-2" onClick={() => setReasonFor(null)}>Batal</Button>
              <Button
                color="primary"
                disabled={reason.trim().length < 5}
                onClick={async () => {
                  if (!reasonFor) return;
                  await runAction(reasonFor.endpoint, { reason });
                  setReasonFor(null);
                  setReason("");
                }}
              >
                Lanjutkan
              </Button>
            </div>
          </ModalBody>
        </Modal>
      </Container>
    </div>
  );
};

export default PaymentsPage;
