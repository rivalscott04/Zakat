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
import type { ReportItem, ReportRunItem } from "../api/types";

const STATUS_COLOR: Record<string, string> = {
  QUEUED: "secondary",
  PROCESSING: "info",
  COMPLETED: "success",
  FAILED: "danger",
  CANCELLED: "light",
};

const FORMATS = ["CSV", "XLSX", "PDF"] as const;

const ReportsPage = () => {
  const { can } = useAuth();
  const [reports, setReports] = useState<ReportItem[]>([]);
  const [favorites, setFavorites] = useState<string[]>([]);
  const [selected, setSelected] = useState<ReportItem | null>(null);
  const [params, setParams] = useState<Record<string, string>>({});
  const [run, setRun] = useState<ReportRunItem | null>(null);
  const [history, setHistory] = useState<ReportRunItem[]>([]);
  const [error, setError] = useState<unknown>(null);
  const [running, setRunning] = useState(false);

  const loadCatalog = useCallback(async () => {
    try {
      setReports((await getPage<ReportItem>("/reports", { per_page: 100 })).data);
      setFavorites((await getData<ReportItem[]>("/reports/favorites")).map((item) => item.id));
    } catch (caught) {
      setError(caught);
    }
  }, []);

  const loadHistory = useCallback(async () => {
    try {
      setHistory((await getPage<ReportRunItem>("/report-runs", { per_page: 10 })).data);
    } catch (caught) {
      setError(caught);
    }
  }, []);

  useEffect(() => {
    void loadCatalog();
    void loadHistory();
  }, [loadCatalog, loadHistory]);

  const openReport = async (report: ReportItem) => {
    try {
      const detail = await getData<ReportItem>(`/reports/${report.id}`);
      setSelected(detail);
      setRun(null);
      setParams(
        Object.fromEntries(
          (detail.parameters ?? []).map((parameter) => [parameter.parameter_code, parameter.default_value ?? ""]),
        ),
      );
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  const execute = async () => {
    if (!selected) return;
    setRunning(true);
    try {
      const response = await api.post(`/reports/${selected.id}/run`, { parameters: params });
      setRun(response.data.data as ReportRunItem);
      await loadHistory();
      setError(null);
    } catch (caught) {
      setError(caught);
    } finally {
      setRunning(false);
    }
  };

  const openRun = async (item: ReportRunItem) => {
    try {
      setRun(await getData<ReportRunItem>(`/report-runs/${item.id}`));
    } catch (caught) {
      setError(caught);
    }
  };

  const exportRun = async (format: string) => {
    if (!run) return;
    try {
      const response = await api.post(`/report-runs/${run.id}/export`, { format });
      // Unduhan lewat navigasi biasa supaya cookie sesi ikut terkirim.
      window.location.href = `/api/v1/report-exports/${response.data.data.id}/download`;
    } catch (caught) {
      setError(caught);
    }
  };

  const toggleFavorite = async (report: ReportItem) => {
    const isFavorite = favorites.includes(report.id);
    try {
      await (isFavorite ? api.delete(`/reports/${report.id}/favorite`) : api.post(`/reports/${report.id}/favorite`));
      setFavorites((current) =>
        isFavorite ? current.filter((id) => id !== report.id) : [...current, report.id],
      );
    } catch (caught) {
      setError(caught);
    }
  };

  document.title = "Laporan | ZETRA";

  const categories = [...new Set(reports.map((report) => report.category))];

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Laporan" pageTitle="Analisis" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Row>
          <Col lg={4}>
            <Card>
              <CardBody>
                <h6 className="mb-3">Katalog laporan</h6>
                {categories.map((category) => (
                  <div key={category} className="mb-3">
                    <div className="text-uppercase text-muted fs-12 mb-1">{category}</div>
                    <div className="list-group list-group-flush">
                      {reports
                        .filter((report) => report.category === category)
                        .map((report) => (
                          <div
                            key={report.id}
                            className={`list-group-item d-flex justify-content-between align-items-center px-0${
                              selected?.id === report.id ? " fw-semibold" : ""
                            }`}
                          >
                            <button type="button" className="btn btn-link p-0 text-start" onClick={() => void openReport(report)}>
                              {report.name}
                              <div className="text-muted fs-12 fw-normal">{report.report_code}</div>
                            </button>
                            <button
                              type="button"
                              className="btn btn-link p-0 text-warning"
                              title={favorites.includes(report.id) ? "Hapus dari favorit" : "Tandai favorit"}
                              onClick={() => void toggleFavorite(report)}
                            >
                              {favorites.includes(report.id) ? "★" : "☆"}
                            </button>
                          </div>
                        ))}
                    </div>
                  </div>
                ))}
                {reports.length === 0 ? <p className="text-muted mb-0">Belum ada laporan.</p> : null}
              </CardBody>
            </Card>

            <Card>
              <CardBody>
                <h6 className="mb-3">Riwayat terakhir</h6>
                <div className="table-responsive">
                  <Table className="align-middle mb-0 fs-12">
                    <tbody>
                      {history.map((item) => (
                        <tr key={item.id} role="button" onClick={() => void openRun(item)}>
                          <td>
                            <div>{item.report_name ?? item.report_code}</div>
                            <span className="text-muted">{item.run_number}</span>
                          </td>
                          <td className="text-end">
                            <Badge color={STATUS_COLOR[item.status] ?? "light"} className={item.status === "CANCELLED" ? "text-body" : ""}>
                              {item.status}
                            </Badge>
                          </td>
                        </tr>
                      ))}
                      {history.length === 0 ? (
                        <tr>
                          <td className="text-muted">Belum ada laporan dijalankan.</td>
                        </tr>
                      ) : null}
                    </tbody>
                  </Table>
                </div>
              </CardBody>
            </Card>
          </Col>

          <Col lg={8}>
            {selected === null ? (
              <Card>
                <CardBody className="text-center text-muted py-5">Pilih laporan dari katalog di sebelah kiri.</CardBody>
              </Card>
            ) : (
              <>
                <Card>
                  <CardBody>
                    <div className="d-flex justify-content-between align-items-start mb-3">
                      <div>
                        <h5 className="mb-1">{selected.name}</h5>
                        <p className="text-muted mb-0 fs-13">{selected.description}</p>
                      </div>
                      <Badge color="light" className="text-body">
                        {selected.visibility}
                      </Badge>
                    </div>

                    <Row className="g-2 align-items-end">
                      {(selected.parameters ?? []).map((parameter) => (
                        <Col md={4} key={parameter.parameter_code}>
                          <label className="form-label fs-12 text-muted">
                            {parameter.label}
                            {parameter.required ? " *" : ""}
                          </label>
                          <Input
                            type={
                              parameter.type === "DATE"
                                ? "date"
                                : parameter.type === "NUMBER"
                                  ? "number"
                                  : "text"
                            }
                            value={params[parameter.parameter_code] ?? ""}
                            onChange={(e) => setParams({ ...params, [parameter.parameter_code]: e.target.value })}
                          />
                        </Col>
                      ))}
                      <Col md={4}>
                        {can("report.run") ? (
                          <Button color="primary" disabled={running} onClick={() => void execute()}>
                            {running ? "Menjalankan..." : "Jalankan laporan"}
                          </Button>
                        ) : null}
                      </Col>
                    </Row>
                  </CardBody>
                </Card>

                {run ? (
                  <Card>
                    <CardBody>
                      <div className="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <span className="fw-semibold">{run.run_number}</span>
                          <Badge color={STATUS_COLOR[run.status] ?? "light"} className="ms-2">
                            {run.status}
                          </Badge>
                          <span className="text-muted ms-2 fs-12">{run.row_count} baris</span>
                        </div>
                        {run.status === "COMPLETED" && can("report.export") ? (
                          <div className="d-flex gap-1">
                            {FORMATS.map((format) => (
                              <Button key={format} size="sm" color="light" onClick={() => void exportRun(format)}>
                                {format}
                              </Button>
                            ))}
                          </div>
                        ) : null}
                      </div>

                      {run.error_message ? <div className="alert alert-danger">{run.error_message}</div> : null}

                      {run.snapshot ? (
                        <>
                          <div className="table-responsive">
                            <Table hover className="align-middle mb-0 fs-13">
                              <thead className="table-light">
                                <tr>
                                  {run.snapshot.columns.map((column) => (
                                    <th key={column.key} className={column.type === "money" || column.type === "number" ? "text-end" : ""}>
                                      {column.label}
                                    </th>
                                  ))}
                                </tr>
                              </thead>
                              <tbody>
                                {run.snapshot.rows.map((row, index) => (
                                  <tr key={index}>
                                    {run.snapshot!.columns.map((column) => (
                                      <td
                                        key={column.key}
                                        className={column.type === "money" || column.type === "number" ? "text-end" : ""}
                                      >
                                        {String(row[column.key] ?? "")}
                                      </td>
                                    ))}
                                  </tr>
                                ))}
                                {run.snapshot.rows.length === 0 ? (
                                  <tr>
                                    <td colSpan={run.snapshot.columns.length} className="text-center text-muted py-4">
                                      Tidak ada data pada periode ini.
                                    </td>
                                  </tr>
                                ) : null}
                              </tbody>
                            </Table>
                          </div>

                          {Object.keys(run.snapshot.summary ?? {}).length > 0 ? (
                            <div className="d-flex flex-wrap gap-4 mt-3">
                              {Object.entries(run.snapshot.summary).map(([key, value]) => (
                                <div key={key}>
                                  <div className="text-uppercase text-muted fs-12">{key.replace(/_/g, " ")}</div>
                                  <div className="fw-semibold">{String(value)}</div>
                                </div>
                              ))}
                            </div>
                          ) : null}
                        </>
                      ) : null}
                    </CardBody>
                  </Card>
                ) : null}
              </>
            )}
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default ReportsPage;
