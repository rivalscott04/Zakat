import React, { useCallback, useEffect, useState } from "react";
import {
  Badge,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Input,
  Modal,
  ModalBody,
  ModalHeader,
  Row,
  Table,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import ErrorAlert from "../components/ErrorAlert";
import { api, getData, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { AuditLogItem, AuditSummary } from "../api/types";

const SEVERITY_COLOR: Record<string, string> = {
  INFO: "light",
  NOTICE: "info",
  WARNING: "warning",
  CRITICAL: "danger",
};

const CATEGORIES = [
  "AUTHENTICATION", "AUTHORIZATION", "CREATE", "UPDATE", "DELETE", "RESTORE",
  "APPROVAL", "REJECTION", "PAYMENT", "COLLECTION", "DISTRIBUTION", "ASSESSMENT",
  "PROGRAM", "DOCUMENT", "BANKING", "ACCOUNTING", "NOTIFICATION", "CONFIGURATION",
  "SECURITY", "SYSTEM", "OTHER",
];

const emptyFilters = { search: "", event_category: "", severity: "", date_from: "", date_to: "" };

const AuditLogsPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<AuditLogItem[]>([]);
  const [summary, setSummary] = useState<AuditSummary | null>(null);
  const [detail, setDetail] = useState<AuditLogItem | null>(null);
  const [filters, setFilters] = useState(emptyFilters);
  const [error, setError] = useState<unknown>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ""));
      setRows((await getPage<AuditLogItem>("/audit-logs", { ...params, per_page: 50 })).data);
      setSummary(await getData<AuditSummary>("/audit-logs/summary", params));
      setError(null);
    } catch (caught) {
      setError(caught);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    void load();
  }, [load]);

  const openDetail = async (row: AuditLogItem) => {
    if (!can("audit.view_detail")) {
      setDetail(row);
      return;
    }

    try {
      setDetail(await getData<AuditLogItem>(`/audit-logs/${row.id}`));
    } catch (caught) {
      setError(caught);
    }
  };

  const exportRows = async () => {
    try {
      const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ""));
      const response = await api.post("/audit-logs/export", params);
      const data = response.data.data as Record<string, unknown>[];

      if (data.length === 0) return;

      const header = Object.keys(data[0]);
      const csv = [
        header.join(","),
        ...data.map((row) => header.map((key) => `"${String(row[key] ?? "").replace(/"/g, '""')}"`).join(",")),
      ].join("\n");

      const url = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
      const link = window.document.createElement("a");
      link.href = url;
      link.download = `audit-${new Date().toISOString().slice(0, 10)}.csv`;
      link.click();
      setTimeout(() => URL.revokeObjectURL(url), 30000);
    } catch (caught) {
      setError(caught);
    }
  };

  document.title = "Audit Trail | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Audit Trail" pageTitle="Administrasi" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Row className="mb-3">
          {(["INFO", "NOTICE", "WARNING", "CRITICAL"] as const).map((level) => (
            <Col md={3} key={level}>
              <Card>
                <CardBody>
                  <p className="text-uppercase text-muted fs-12 mb-2">{level}</p>
                  <h4 className="mb-0">{summary?.by_severity?.[level] ?? 0}</h4>
                </CardBody>
              </Card>
            </Col>
          ))}
        </Row>

        <Card>
          <CardBody>
            <Row className="g-2 mb-3">
              <Col md={3}>
                <Input type="search" placeholder="Cari nomor, peristiwa, atau pelaku..." value={filters.search} onChange={(e) => setFilters({ ...filters, search: e.target.value })} />
              </Col>
              <Col md={2}>
                <Input type="select" value={filters.event_category} onChange={(e) => setFilters({ ...filters, event_category: e.target.value })}>
                  <option value="">Semua kategori</option>
                  {CATEGORIES.map((item) => <option key={item} value={item}>{item}</option>)}
                </Input>
              </Col>
              <Col md={2}>
                <Input type="select" value={filters.severity} onChange={(e) => setFilters({ ...filters, severity: e.target.value })}>
                  <option value="">Semua tingkat</option>
                  {Object.keys(SEVERITY_COLOR).map((item) => <option key={item} value={item}>{item}</option>)}
                </Input>
              </Col>
              <Col md={2}><Input type="date" value={filters.date_from} onChange={(e) => setFilters({ ...filters, date_from: e.target.value })} /></Col>
              <Col md={2}><Input type="date" value={filters.date_to} onChange={(e) => setFilters({ ...filters, date_to: e.target.value })} /></Col>
              <Col md={1} className="d-flex gap-1">
                <Button color="light" onClick={() => setFilters(emptyFilters)} title="Bersihkan">×</Button>
                {can("audit.export") ? <Button color="secondary" onClick={() => void exportRows()} title="Ekspor CSV">CSV</Button> : null}
              </Col>
            </Row>

            <div className="table-responsive">
              <Table hover className="align-middle table-nowrap mb-0">
                <thead className="table-light">
                  <tr><th>Waktu</th><th>Peristiwa</th><th>Entitas</th><th>Pelaku</th><th>Tingkat</th></tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr><td colSpan={5} className="text-center text-muted py-4">Memuat data...</td></tr>
                  ) : rows.length === 0 ? (
                    <tr><td colSpan={5} className="text-center text-muted py-4">Belum ada catatan audit.</td></tr>
                  ) : rows.map((row) => (
                    <tr key={row.id} role="button" onClick={() => void openDetail(row)}>
                      <td className="fs-12">{row.occurred_at ? new Date(row.occurred_at).toLocaleString("id-ID") : "-"}</td>
                      <td>
                        <div className="fw-medium">{row.event_name ?? row.action}</div>
                        <small className="text-muted">{row.audit_number}</small>
                      </td>
                      <td className="fs-12">
                        {row.entity_type ?? "-"}
                        {row.entity_reference ? <span className="text-muted"> · {row.entity_reference}</span> : null}
                      </td>
                      <td className="fs-12">
                        {row.actor_name ?? "-"}
                        {row.actor_type === "SYSTEM" ? <Badge color="light" className="ms-1 text-body">sistem</Badge> : null}
                      </td>
                      <td><Badge color={SEVERITY_COLOR[row.severity] ?? "light"}>{row.severity}</Badge></td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </div>
          </CardBody>
        </Card>

        <Modal isOpen={detail !== null} toggle={() => setDetail(null)} size="lg" centered>
          <ModalHeader toggle={() => setDetail(null)}>{detail?.audit_number}</ModalHeader>
          <ModalBody>
            {detail ? (
              <>
                <Row className="mb-3">
                  {[
                    ["Peristiwa", detail.event_name ?? detail.action],
                    ["Kategori", detail.event_category],
                    ["Modul", detail.module_code],
                    ["Tingkat", detail.severity],
                    ["Pelaku", `${detail.actor_name ?? "-"} (${detail.actor_type})`],
                    ["Entitas", `${detail.entity_type ?? "-"} ${detail.entity_reference ?? ""}`],
                    ["Alamat IP", detail.ip_address ?? "-"],
                    ["Request ID", detail.request_id ?? "-"],
                  ].map(([label, value]) => (
                    <Col md={6} key={label as string} className="mb-2">
                      <div className="text-muted fs-12 text-uppercase">{label}</div>
                      <div className="fw-medium">{value || "-"}</div>
                    </Col>
                  ))}
                </Row>

                {detail.old_values || detail.new_values ? (
                  <Row>
                    <Col md={6}>
                      <div className="text-muted fs-12 text-uppercase mb-1">Nilai lama</div>
                      <pre className="bg-light p-2 rounded fs-12 mb-0" style={{ maxHeight: 220, overflow: "auto" }}>{JSON.stringify(detail.old_values ?? {}, null, 2)}</pre>
                    </Col>
                    <Col md={6}>
                      <div className="text-muted fs-12 text-uppercase mb-1">Nilai baru</div>
                      <pre className="bg-light p-2 rounded fs-12 mb-0" style={{ maxHeight: 220, overflow: "auto" }}>{JSON.stringify(detail.new_values ?? {}, null, 2)}</pre>
                    </Col>
                  </Row>
                ) : detail.has_changes ? (
                  <p className="text-muted fs-13 mb-0">
                    Catatan ini memuat perubahan nilai, tetapi hanya dapat dilihat pemegang izin <code>audit.view_sensitive</code>.
                  </p>
                ) : null}

                {detail.request_id ? (
                  <div className="mt-3">
                    <Button
                      size="sm"
                      color="light"
                      onClick={() => {
                        setFilters({ ...emptyFilters, search: "" });
                        setDetail(null);
                        void (async () => {
                          try {
                            setRows(await getData<AuditLogItem[]>(`/audit-logs/request/${detail.request_id}`));
                          } catch (caught) {
                            setError(caught);
                          }
                        })();
                      }}
                    >
                      Lihat semua peristiwa dari request ini
                    </Button>
                  </div>
                ) : null}
              </>
            ) : null}
          </ModalBody>
        </Modal>
      </Container>
    </div>
  );
};

export default AuditLogsPage;
