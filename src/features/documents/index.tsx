import React, { useCallback, useEffect, useState } from "react";
import {
  Badge,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Form,
  Input,
  Label,
  Row,
  Table,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import ErrorAlert from "../components/ErrorAlert";
import StatusBadge from "../components/StatusBadge";
import { api, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { DocumentItem } from "../api/types";

const TYPES = ["IDENTITY", "FAMILY", "ASSESSMENT", "PROGRAM", "PAYMENT", "BANK", "DISTRIBUTION", "RECEIPT", "REPORT", "CONTRACT", "LETTER", "IMAGE", "OTHER"];
const emptyForm = { document_name: "", document_type: "OTHER", visibility: "PRIVATE" };

const size = (bytes: number) => (bytes > 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);

const DocumentsPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<DocumentItem[]>([]);
  const [error, setError] = useState<unknown>(null);
  const [form, setForm] = useState(emptyForm);
  const [file, setFile] = useState<File | null>(null);
  const [search, setSearch] = useState("");

  const load = useCallback(async () => {
    try {
      setRows((await getPage<DocumentItem>("/documents", { per_page: 50, ...(search ? { search } : {}) })).data);
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  }, [search]);

  useEffect(() => {
    void load();
  }, [load]);

  const run = async (action: () => Promise<unknown>) => {
    try {
      await action();
      setError(null);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  const upload = (event: React.FormEvent) => {
    event.preventDefault();
    if (!file) return;

    const body = new FormData();
    Object.entries(form).forEach(([key, value]) => body.append(key, value));
    body.append("file", file);

    void run(async () => {
      await api.post("/documents", body, { headers: { "Content-Type": "multipart/form-data" } });
      setForm(emptyForm);
      setFile(null);
    });
  };

  /**
   * Berkas diambil sebagai blob lewat klien ber-sesi, bukan dibuka langsung
   * sebagai URL. Endpoint dokumen memerlukan autentikasi, dan window.open tidak
   * membawa header yang sama.
   */
  const openFile = async (document: DocumentItem, mode: "download" | "preview") => {
    await run(async () => {
      const response = await api.get(`/documents/${document.id}/${mode}`, { responseType: "blob" });
      const url = URL.createObjectURL(response.data as Blob);

      if (mode === "preview") {
        window.open(url, "_blank", "noopener");
      } else {
        const link = window.document.createElement("a");
        link.href = url;
        link.download = document.original_filename;
        link.click();
      }

      setTimeout(() => URL.revokeObjectURL(url), 30000);
    });
  };

  document.title = "Dokumen | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Dokumen" pageTitle="Administrasi" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Row>
          <Col lg={4}>
            {can("document.create") ? (
              <Card>
                <CardBody>
                  <h5 className="mb-3">Unggah Dokumen</h5>
                  <Form onSubmit={upload}>
                    <Input className="mb-2" placeholder="Nama dokumen" required value={form.document_name} onChange={(e) => setForm({ ...form, document_name: e.target.value })} />

                    <Label for="doc-type" className="fs-12 text-muted">Jenis</Label>
                    <Input id="doc-type" className="mb-2" type="select" value={form.document_type} onChange={(e) => setForm({ ...form, document_type: e.target.value })}>
                      {TYPES.map((type) => <option key={type}>{type}</option>)}
                    </Input>

                    <Label for="doc-visibility" className="fs-12 text-muted">Sifat</Label>
                    <Input id="doc-visibility" className="mb-2" type="select" value={form.visibility} onChange={(e) => setForm({ ...form, visibility: e.target.value })}>
                      <option value="PRIVATE">PRIVATE, hanya pengunggah dan pemegang izin khusus</option>
                      <option value="INTERNAL">INTERNAL, seluruh organisasi sesuai izin</option>
                      <option value="PUBLIC">PUBLIC, dapat dibagikan</option>
                    </Input>

                    <Input className="mb-2" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp" required onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
                    <small className="text-muted d-block mb-2">
                      PDF, Word, Excel, CSV, atau gambar. Maksimal 10 MB. Isi berkas diperiksa, tidak hanya ekstensinya.
                    </small>
                    <Button color="primary">Unggah</Button>
                  </Form>
                </CardBody>
              </Card>
            ) : null}
          </Col>

          <Col lg={8}>
            <Card>
              <CardBody>
                <Row className="mb-3">
                  <Col md={6}>
                    <Input type="search" placeholder="Cari nama atau nomor dokumen..." value={search} onChange={(e) => setSearch(e.target.value)} />
                  </Col>
                </Row>

                {rows.length === 0 ? (
                  <p className="text-muted mb-0">Belum ada dokumen.</p>
                ) : (
                  <Table responsive hover className="align-middle">
                    <thead>
                      <tr><th>Dokumen</th><th>Jenis</th><th>Ukuran</th><th>Versi</th><th>Status</th><th className="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                      {rows.map((row) => (
                        <tr key={row.id}>
                          <td>
                            <div className="fw-medium">{row.document_name}</div>
                            <small className="text-muted">{row.original_filename}</small>
                            {row.visibility === "PRIVATE" ? <Badge color="dark" className="ms-1">privat</Badge> : null}
                          </td>
                          <td className="fs-12">{row.document_type}</td>
                          <td className="fs-12">{size(row.file_size)}</td>
                          <td>{row.version}</td>
                          <td><StatusBadge status={row.status.toLowerCase()} /></td>
                          <td className="text-end">
                            {row.previewable && can("document.preview") ? (
                              <Button size="sm" outline color="secondary" className="me-1" onClick={() => void openFile(row, "preview")}>Pratinjau</Button>
                            ) : null}
                            {can("document.download") ? (
                              <Button size="sm" outline color="secondary" className="me-1" onClick={() => void openFile(row, "download")}>Unduh</Button>
                            ) : null}
                            {can("document.verify") && row.status !== "VERIFIED" ? (
                              <Button size="sm" color="soft-success" className="me-1" onClick={() => void run(() => api.post(`/documents/${row.id}/verify`, {}))}>Verifikasi</Button>
                            ) : null}
                            {can("document.archive") && row.status !== "ARCHIVED" ? (
                              <Button size="sm" color="soft-dark" onClick={() => void run(() => api.post(`/documents/${row.id}/archive`))}>Arsip</Button>
                            ) : null}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                )}
              </CardBody>
            </Card>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default DocumentsPage;
