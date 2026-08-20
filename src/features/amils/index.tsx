import React, { useEffect, useState } from "react";
import { Alert, Button, Card, CardBody, Col, Container, Form, FormGroup, Input, Label, Row } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { ApiError, api, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { Amil } from "../api/types";

const AmilsPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<Amil[]>([]);
  const [meta, setMeta] = useState<import("../api/client").PaginationMeta>();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({ name: "", employee_number: "", email: "", phone: "" });
  const load = async () => {
    try { const result = await getPage<Amil>("/amils", { page, search }); setRows(result.data); setMeta(result.meta); } catch (e) { setError((e as ApiError).message); }
  };
  useEffect(() => { void load(); }, [page, search]);
  const create = async (event: React.FormEvent) => {
    event.preventDefault(); setError(null);
    try { await api.post("/amils", form); setForm({ name: "", employee_number: "", email: "", phone: "" }); await load(); } catch (e) { setError((e as ApiError).message); }
  };
  const action = async (amil: Amil, name: string) => { try { await api.post(`/amils/${amil.id}/${name}`); await load(); } catch (e) { setError((e as ApiError).message); } };
  const columns: Column<Amil>[] = [
    { header: "Amil", render: (amil) => <><div className="fw-medium">{amil.name}</div><small className="text-muted">{amil.business_number}</small></> },
    { header: "Kontak", render: (amil) => <>{amil.email ?? "-"}<br /><small>{amil.phone ?? "-"}</small></> },
    { header: "Status", render: (amil) => <StatusBadge status={amil.status} /> },
    { header: "Aksi", className: "text-end", render: (amil) => can("amils.update") && amil.status !== "ended" ? <Button size="sm" color="light" onClick={() => void action(amil, amil.status === "active" ? "deactivate" : "activate")}>{amil.status === "active" ? "Nonaktifkan" : "Aktifkan"}</Button> : null },
  ];
  return <div className="page-content"><Container fluid><BreadCrumb title="Amil" pageTitle="Organisasi" />{error ? <Alert color="danger">{error}</Alert> : null}<Card><CardBody><Row className="g-3 mb-3"><Col md={4}><Input type="search" placeholder="Cari amil..." value={search} onChange={(e) => { setPage(1); setSearch(e.target.value); }} /></Col></Row>{can("amils.create") ? <Form onSubmit={create} className="border rounded p-3 mb-4"><h5>Tambah Amil</h5><Row><Col md={3}><FormGroup><Label>Nama</Label><Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></FormGroup></Col><Col md={3}><FormGroup><Label>No. Pegawai</Label><Input value={form.employee_number} onChange={(e) => setForm({ ...form, employee_number: e.target.value })} /></FormGroup></Col><Col md={3}><FormGroup><Label>Email</Label><Input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></FormGroup></Col><Col md={3} className="d-flex align-items-end"><Button color="success" type="submit">Tambah</Button></Col></Row></Form> : null}<DataTable columns={columns} rows={rows} meta={meta} loading={false} onPageChange={setPage} rowKey={(amil) => amil.id} emptyMessage="Belum ada amil." /></CardBody></Card></Container></div>;
};

export default AmilsPage;
