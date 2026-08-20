import React, { useCallback, useEffect, useState } from "react";
import {
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
import type { DistributionBatch, DistributionBeneficiary } from "../api/types";

/** PRD 12P §41 — aksi berurutan pada alur batch. */
const ACTIONS: { target: string; label: string; endpoint: string; permission: string; color: string }[] = [
  { target: "validated", label: "Validasi", endpoint: "validate", permission: "distribution.batch.update", color: "info" },
  { target: "pending_approval", label: "Ajukan", endpoint: "submit", permission: "distribution.batch.update", color: "primary" },
  { target: "approved", label: "Setujui", endpoint: "approve", permission: "distribution.batch.approve", color: "success" },
  { target: "processing", label: "Proses Penyaluran", endpoint: "process", permission: "distribution.batch.process", color: "success" },
];

const rupiah = (value: string) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(Number(value));

const DistributionBatchesPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<DistributionBatch[]>([]);
  const [detail, setDetail] = useState<DistributionBatch | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<unknown>(null);
  const [form, setForm] = useState({ name: "", fund_id: "", program_id: "", distribution_type: "cash" });
  const [beneficiary, setBeneficiary] = useState({ mustahik_id: "", approved_amount: "" });
  const [cancelOpen, setCancelOpen] = useState(false);
  const [reason, setReason] = useState("");

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setRows((await getPage<DistributionBatch>("/distribution-batches")).data);
      setError(null);
    } catch (caught) {
      setError(caught);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const openDetail = async (id: string) => {
    try {
      setDetail(await getData<DistributionBatch>(`/distribution-batches/${id}`));
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  const create = async (event: React.FormEvent) => {
    event.preventDefault();
    try {
      const response = await api.post("/distribution-batches", {
        name: form.name,
        fund_id: form.fund_id,
        program_id: form.program_id || null,
        distribution_type: form.distribution_type,
      });
      setForm({ name: "", fund_id: "", program_id: "", distribution_type: "cash" });
      setError(null);
      await load();
      setDetail(response.data.data as DistributionBatch);
    } catch (caught) {
      setError(caught);
    }
  };

  const addBeneficiary = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!detail) return;
    try {
      await api.post(`/distribution-batches/${detail.id}/beneficiaries`, {
        mustahik_id: beneficiary.mustahik_id,
        approved_amount: Number(beneficiary.approved_amount),
      });
      setBeneficiary({ mustahik_id: "", approved_amount: "" });
      setError(null);
      await openDetail(detail.id);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const runAction = async (endpoint: string, payload: Record<string, unknown> = {}) => {
    if (!detail) return;
    try {
      const response = await api.post(`/distribution-batches/${detail.id}/${endpoint}`, payload);
      setDetail(response.data.data as DistributionBatch);
      setError(null);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const columns: Column<DistributionBatch>[] = [
    {
      header: "Batch",
      render: (row) => (
        <>
          <button type="button" className="btn btn-link p-0 fw-medium" onClick={() => void openDetail(row.id)}>
            {row.batch_number}
          </button>
          <div className="text-muted fs-12">{row.name}</div>
        </>
      ),
    },
    { header: "Jenis", render: (row) => row.distribution_type },
    { header: "Penerima", className: "text-end", render: (row) => row.total_beneficiary },
    { header: "Total", className: "text-end", render: (row) => rupiah(row.total_amount) },
    { header: "Status", render: (row) => <StatusBadge status={row.status} /> },
  ];

  const beneficiaryColumns: Column<DistributionBeneficiary>[] = [
    { header: "Mustahik", render: (row) => row.mustahik?.display_name ?? row.mustahik_id },
    { header: "Disetujui", className: "text-end", render: (row) => rupiah(row.approved_amount) },
    { header: "Disalurkan", className: "text-end", render: (row) => rupiah(row.distributed_amount) },
    {
      header: "Status",
      render: (row) => (
        <>
          <StatusBadge status={row.status} />
          {row.failure_note ? <div className="text-danger fs-12">{row.failure_note}</div> : null}
        </>
      ),
    },
    {
      header: "",
      className: "text-end",
      render: (row) =>
        detail && ["draft", "validated"].includes(detail.status) && can("distribution.batch.update") ? (
          <Button
            size="sm"
            color="soft-danger"
            onClick={async () => {
              try {
                await api.delete(`/distribution-batches/${detail.id}/beneficiaries/${row.id}`);
                await openDetail(detail.id);
                await load();
              } catch (caught) {
                setError(caught);
              }
            }}
          >
            <i className="ri-delete-bin-line" />
          </Button>
        ) : null,
    },
  ];

  document.title = "Batch Penyaluran | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Batch Distribution" pageTitle="Penyaluran" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Card>
          <CardBody>
            {can("distribution.batch.create") ? (
              <Form onSubmit={create} className="border rounded p-3 mb-4">
                <h5 className="mb-3">Buat Batch</h5>
                <Row>
                  <Col md={4}>
                    <FormGroup>
                      <Label for="batch-name">Nama Batch</Label>
                      <Input
                        id="batch-name"
                        required
                        value={form.name}
                        onChange={(event) => setForm({ ...form, name: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={3}>
                    <FormGroup>
                      <Label for="batch-fund">ID Fund</Label>
                      <Input
                        id="batch-fund"
                        required
                        value={form.fund_id}
                        onChange={(event) => setForm({ ...form, fund_id: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={3}>
                    <FormGroup>
                      <Label for="batch-program">ID Program</Label>
                      <Input
                        id="batch-program"
                        placeholder="opsional"
                        value={form.program_id}
                        onChange={(event) => setForm({ ...form, program_id: event.target.value })}
                      />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label for="batch-type">Jenis</Label>
                      <Input
                        id="batch-type"
                        type="select"
                        value={form.distribution_type}
                        onChange={(event) => setForm({ ...form, distribution_type: event.target.value })}
                      >
                        {["cash", "bank_transfer", "goods", "scholarship", "voucher"].map((type) => (
                          <option key={type} value={type}>
                            {type}
                          </option>
                        ))}
                      </Input>
                    </FormGroup>
                  </Col>
                </Row>
                <Button color="success" type="submit">
                  Simpan
                </Button>
              </Form>
            ) : null}

            <DataTable
              columns={columns}
              rows={rows}
              loading={loading}
              rowKey={(row) => row.id}
              emptyMessage="Belum ada batch distribution."
            />
          </CardBody>
        </Card>

        <Modal isOpen={detail !== null} toggle={() => setDetail(null)} size="lg" centered>
          <ModalHeader toggle={() => setDetail(null)}>
            {detail?.batch_number} <StatusBadge status={detail?.status ?? "draft"} />
          </ModalHeader>
          <ModalBody>
            {detail ? (
              <>
                <Row className="mb-3">
                  {[
                    ["Nama", detail.name],
                    ["Fund", detail.fund?.name ?? detail.fund_id],
                    ["Penerima", String(detail.total_beneficiary)],
                    ["Total", rupiah(detail.total_amount)],
                  ].map(([label, value]) => (
                    <Col md={3} key={label} className="mb-2">
                      <div className="text-muted fs-12 text-uppercase">{label}</div>
                      <div className="fw-medium">{value}</div>
                    </Col>
                  ))}
                </Row>

                {["draft", "validated"].includes(detail.status) && can("distribution.batch.update") ? (
                  <Form onSubmit={addBeneficiary} className="border rounded p-3 mb-3">
                    <Row className="align-items-end">
                      <Col md={5}>
                        <FormGroup className="mb-0">
                          <Label for="beneficiary-mustahik">ID Mustahik</Label>
                          <Input
                            id="beneficiary-mustahik"
                            required
                            value={beneficiary.mustahik_id}
                            onChange={(event) => setBeneficiary({ ...beneficiary, mustahik_id: event.target.value })}
                          />
                        </FormGroup>
                      </Col>
                      <Col md={4}>
                        <FormGroup className="mb-0">
                          <Label for="beneficiary-amount">Nominal</Label>
                          <Input
                            id="beneficiary-amount"
                            required
                            type="number"
                            min="1"
                            value={beneficiary.approved_amount}
                            onChange={(event) => setBeneficiary({ ...beneficiary, approved_amount: event.target.value })}
                          />
                        </FormGroup>
                      </Col>
                      <Col md={3}>
                        <Button color="primary" type="submit" className="w-100">
                          Tambah Penerima
                        </Button>
                      </Col>
                    </Row>
                  </Form>
                ) : null}

                <DataTable
                  columns={beneficiaryColumns}
                  rows={detail.beneficiaries ?? []}
                  rowKey={(row) => row.id}
                  emptyMessage="Belum ada penerima pada batch ini."
                />

                <div className="d-flex flex-wrap gap-2 mt-3">
                  {ACTIONS.filter(
                    (action) => detail.allowed_transitions.includes(action.target) && can(action.permission),
                  ).map((action) => (
                    <Button key={action.endpoint} color={action.color} size="sm" onClick={() => void runAction(action.endpoint)}>
                      {action.label}
                    </Button>
                  ))}

                  {detail.allowed_transitions.includes("cancelled") && can("distribution.batch.update") ? (
                    <Button color="danger" size="sm" onClick={() => setCancelOpen(true)}>
                      Batalkan
                    </Button>
                  ) : null}
                </div>
              </>
            ) : null}
          </ModalBody>
        </Modal>

        <Modal isOpen={cancelOpen} toggle={() => setCancelOpen(false)} centered>
          <ModalHeader toggle={() => setCancelOpen(false)}>Batalkan Batch</ModalHeader>
          <ModalBody>
            <FormGroup>
              <Label for="batch-reason">Alasan</Label>
              <Input
                id="batch-reason"
                type="textarea"
                rows={3}
                value={reason}
                onChange={(event) => setReason(event.target.value)}
              />
            </FormGroup>
            <div className="text-end">
              <Button color="light" className="me-2" onClick={() => setCancelOpen(false)}>
                Tutup
              </Button>
              <Button
                color="danger"
                disabled={reason.trim() === ""}
                onClick={async () => {
                  await runAction("cancel", { reason });
                  setCancelOpen(false);
                  setReason("");
                }}
              >
                Batalkan
              </Button>
            </div>
          </ModalBody>
        </Modal>
      </Container>
    </div>
  );
};

export default DistributionBatchesPage;
