import React, { useCallback, useEffect, useState } from "react";
import {
  Badge,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Input,
  Row,
  Table,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import ErrorAlert from "../components/ErrorAlert";
import { api, getData, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type {
  TransparencyAggregate,
  TransparencyReportItem,
  TransparencySnapshotItem,
} from "../api/types";

const STATUS_COLOR: Record<string, string> = {
  DRAFT: "light",
  GENERATED: "secondary",
  PENDING_APPROVAL: "warning",
  APPROVED: "info",
  PUBLISHED: "success",
  ARCHIVED: "light",
  REVOKED: "danger",
};

const VERIFICATION_COLOR: Record<string, string> = {
  VALID: "success",
  WARNING: "warning",
  INVALID: "danger",
};

/** Tombol alur publikasi sesuai PRD 18G section 11. */
const WORKFLOW: { status: string; action: string; label: string; permission: string }[] = [
  { status: "DRAFT", action: "generate", label: "Hasilkan agregat", permission: "transparency.snapshot.generate" },
  { status: "GENERATED", action: "submit", label: "Ajukan persetujuan", permission: "transparency.snapshot.submit" },
  { status: "PENDING_APPROVAL", action: "approve", label: "Setujui", permission: "transparency.snapshot.approve" },
  { status: "APPROVED", action: "publish", label: "Publikasikan", permission: "transparency.snapshot.publish" },
];

const money = (value: string | number | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(
    Number(value ?? 0),
  );

const TransparencyPage = () => {
  const { can } = useAuth();
  const [dashboard, setDashboard] = useState<TransparencyAggregate | null>(null);
  const [snapshots, setSnapshots] = useState<TransparencySnapshotItem[]>([]);
  const [reports, setReports] = useState<TransparencyReportItem[]>([]);
  const [selected, setSelected] = useState<TransparencySnapshotItem | null>(null);
  const [form, setForm] = useState({
    snapshot_type: "MONTHLY",
    period_start: new Date().toISOString().slice(0, 8) + "01",
    period_end: new Date().toISOString().slice(0, 10),
  });
  const [error, setError] = useState<unknown>(null);

  const load = useCallback(async () => {
    try {
      setSnapshots((await getPage<TransparencySnapshotItem>("/transparency/snapshots", { per_page: 20 })).data);
      if (can("transparency.report.view")) {
        setReports((await getPage<TransparencyReportItem>("/transparency/reports", { per_page: 20 })).data);
      }
      if (can("transparency.dashboard.view")) {
        setDashboard(await getData<TransparencyAggregate>("/transparency/dashboard"));
      }
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  }, [can]);

  useEffect(() => {
    void load();
  }, [load]);

  const act = async (run: () => Promise<unknown>) => {
    try {
      await run();
      await load();
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  const openSnapshot = async (snapshot: TransparencySnapshotItem) => {
    try {
      setSelected(await getData<TransparencySnapshotItem>(`/transparency/snapshots/${snapshot.id}`));
    } catch (caught) {
      setError(caught);
    }
  };

  const revoke = async (snapshot: TransparencySnapshotItem) => {
    const reason = window.prompt("Alasan pencabutan publikasi (minimal 10 karakter):");
    if (reason === null) return;
    await act(() => api.post(`/transparency/snapshots/${snapshot.id}/revoke`, { reason }));
  };

  document.title = "Transparansi | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Transparansi" pageTitle="Publikasi" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        {dashboard ? (
          <Row className="mb-3">
            {[
              ["Penerimaan periode berjalan", money(dashboard.collection.total_collection)],
              ["Penyaluran", money(dashboard.distribution.total_distributed)],
              ["Dana tersedia", money(dashboard.fund.available_balance)],
              ["Rasio penyaluran", `${dashboard.metrics.distribution_rate}%`],
            ].map(([label, value]) => (
              <Col md={3} key={label}>
                <Card>
                  <CardBody>
                    <p className="text-muted fs-12 text-uppercase mb-2">{label}</p>
                    <h5 className="mb-0">{value}</h5>
                  </CardBody>
                </Card>
              </Col>
            ))}
          </Row>
        ) : null}

        <Row>
          <Col lg={7}>
            <Card>
              <CardBody>
                <h6 className="mb-3">Snapshot transparansi</h6>
                <div className="table-responsive">
                  <Table hover className="align-middle mb-0">
                    <thead className="table-light">
                      <tr>
                        <th>Nomor</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Verifikasi</th>
                        <th />
                      </tr>
                    </thead>
                    <tbody>
                      {snapshots.map((snapshot) => {
                        const step = WORKFLOW.find((item) => item.status === snapshot.status);

                        return (
                          <tr key={snapshot.id}>
                            <td role="button" onClick={() => void openSnapshot(snapshot)}>
                              <div className="fw-medium">{snapshot.snapshot_number}</div>
                              <small className="text-muted">{snapshot.snapshot_type}</small>
                            </td>
                            <td className="fs-12">
                              {snapshot.period_start} sampai {snapshot.period_end}
                            </td>
                            <td>
                              <Badge color={STATUS_COLOR[snapshot.status]} className={STATUS_COLOR[snapshot.status] === "light" ? "text-body" : ""}>
                                {snapshot.status}
                              </Badge>
                            </td>
                            <td>
                              {snapshot.verification_status ? (
                                <Badge color={VERIFICATION_COLOR[snapshot.verification_status]}>
                                  {snapshot.verification_status}
                                </Badge>
                              ) : (
                                <span className="text-muted fs-12">belum</span>
                              )}
                            </td>
                            <td className="text-end">
                              {step && can(step.permission) ? (
                                <Button
                                  size="sm"
                                  color="link"
                                  className="p-0"
                                  onClick={() => void act(() => api.post(`/transparency/snapshots/${snapshot.id}/${step.action}`))}
                                >
                                  {step.label}
                                </Button>
                              ) : null}
                              {snapshot.status === "PUBLISHED" && can("transparency.snapshot.revoke") ? (
                                <Button size="sm" color="link" className="p-0 text-danger" onClick={() => void revoke(snapshot)}>
                                  Cabut
                                </Button>
                              ) : null}
                            </td>
                          </tr>
                        );
                      })}
                      {snapshots.length === 0 ? (
                        <tr>
                          <td colSpan={5} className="text-center text-muted py-4">
                            Belum ada snapshot.
                          </td>
                        </tr>
                      ) : null}
                    </tbody>
                  </Table>
                </div>
              </CardBody>
            </Card>

            {selected?.snapshot_data ? (
              <Card>
                <CardBody>
                  <h6 className="mb-3">{selected.snapshot_number}</h6>

                  {selected.verification_notes ? (
                    <>
                      {selected.verification_notes.problems.map((note) => (
                        <div className="alert alert-danger py-2 fs-13" key={note}>
                          {note}
                        </div>
                      ))}
                      {selected.verification_notes.warnings.map((note) => (
                        <div className="alert alert-warning py-2 fs-13" key={note}>
                          {note}
                        </div>
                      ))}
                    </>
                  ) : null}

                  <div className="table-responsive">
                    <Table className="align-middle mb-0 fs-13">
                      <thead className="table-light">
                        <tr>
                          <th>Asnaf</th>
                          <th className="text-end">Penerima</th>
                          <th className="text-end">Nilai</th>
                          <th className="text-end">Porsi</th>
                        </tr>
                      </thead>
                      <tbody>
                        {selected.snapshot_data.asnaf.map((row) => (
                          <tr key={row.asnaf_code}>
                            <td>{row.asnaf_code}</td>
                            <td className="text-end">{row.beneficiary_count}</td>
                            <td className="text-end">{money(row.amount)}</td>
                            <td className="text-end">{row.percentage}%</td>
                          </tr>
                        ))}
                        {selected.snapshot_data.asnaf.length === 0 ? (
                          <tr>
                            <td colSpan={4} className="text-center text-muted py-3">
                              Belum ada penyaluran pada periode ini.
                            </td>
                          </tr>
                        ) : null}
                      </tbody>
                    </Table>
                  </div>
                </CardBody>
              </Card>
            ) : null}
          </Col>

          <Col lg={5}>
            {can("transparency.snapshot.create") ? (
              <Card>
                <CardBody>
                  <h6 className="mb-3">Snapshot baru</h6>
                  <Input
                    type="select"
                    className="mb-2"
                    value={form.snapshot_type}
                    onChange={(e) => setForm({ ...form, snapshot_type: e.target.value })}
                  >
                    {["DAILY", "MONTHLY", "QUARTERLY", "YEARLY", "CUSTOM"].map((type) => (
                      <option key={type} value={type}>
                        {type}
                      </option>
                    ))}
                  </Input>
                  <Input
                    type="date"
                    className="mb-2"
                    value={form.period_start}
                    onChange={(e) => setForm({ ...form, period_start: e.target.value })}
                  />
                  <Input
                    type="date"
                    className="mb-3"
                    value={form.period_end}
                    onChange={(e) => setForm({ ...form, period_end: e.target.value })}
                  />
                  <p className="text-muted fs-12">
                    Selain CUSTOM, periode dirapikan otomatis mengikuti jenis snapshot.
                  </p>
                  <Button color="primary" onClick={() => void act(() => api.post("/transparency/snapshots", form))}>
                    Buat snapshot
                  </Button>
                </CardBody>
              </Card>
            ) : null}

            {can("transparency.report.view") ? (
              <Card>
                <CardBody>
                  <h6 className="mb-3">Laporan publik</h6>
                  <div className="table-responsive">
                    <Table className="align-middle mb-0 fs-13">
                      <tbody>
                        {reports.map((report) => (
                          <tr key={report.id}>
                            <td>
                              <div className="fw-medium">{report.title}</div>
                              <small className="text-muted">{report.report_number}</small>
                            </td>
                            <td>
                              <Badge color={report.status === "PUBLISHED" ? "success" : "light"} className={report.status === "PUBLISHED" ? "" : "text-body"}>
                                {report.status}
                              </Badge>
                            </td>
                            <td className="text-end">
                              {report.status === "DRAFT" && can("transparency.report.publish") ? (
                                <Button
                                  size="sm"
                                  color="link"
                                  className="p-0"
                                  onClick={() => void act(() => api.post(`/transparency/reports/${report.id}/publish`))}
                                >
                                  Terbitkan
                                </Button>
                              ) : null}
                            </td>
                          </tr>
                        ))}
                        {reports.length === 0 ? (
                          <tr>
                            <td className="text-muted">Belum ada laporan.</td>
                          </tr>
                        ) : null}
                      </tbody>
                    </Table>
                  </div>
                </CardBody>
              </Card>
            ) : null}
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default TransparencyPage;
