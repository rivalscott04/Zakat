import React, { useEffect, useState } from "react";
import { Alert, Button, Card, CardBody, Col, Container, Form, FormGroup, Input, Label, Row } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { ApiError, api, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { Fund } from "../api/types";

const FundsPage = () => {
  const { can } = useAuth(); const [rows, setRows] = useState<Fund[]>([]); const [meta, setMeta] = useState<import("../api/client").PaginationMeta>(); const [error, setError] = useState<string | null>(null); const [page, setPage] = useState(1); const [form, setForm] = useState({ fund_code: "", name: "", fund_type: "zakat", category: "", restriction_type: "unrestricted" });
  const load = async () => { try { const result = await getPage<Fund>("/funds", { page }); setRows(result.data); setMeta(result.meta); } catch (e) { setError((e as ApiError).message); } };
  useEffect(() => { void load(); }, [page]);
  const create = async (event: React.FormEvent) => { event.preventDefault(); try { await api.post("/funds", form); setForm({ fund_code: "", name: "", fund_type: "zakat", category: "", restriction_type: "unrestricted" }); await load(); } catch (e) { setError((e as ApiError).message); } };
  const columns: Column<Fund>[] = [{ header: "Fund", render: (row) => <><div className="fw-medium">{row.fund_code}</div><small className="text-muted">{row.name}</small></> }, { header: "Type", render: (row) => `${row.fund_type} · ${row.category ?? "-"}` }, { header: "Restriction", render: (row) => row.restriction_type }, { header: "Current", render: (row) => `${row.current_balance} ${row.currency}` }, { header: "Available", render: (row) => `${row.available_balance} ${row.currency}` }, { header: "Status", render: (row) => <StatusBadge status={row.status} /> }];
  return <div className="page-content"><Container fluid><BreadCrumb title="Fund Management" pageTitle="Keuangan" />{error ? <Alert color="danger">{error}</Alert> : null}<Card><CardBody>{can("fund.create") ? <Form onSubmit={create} className="border rounded p-3 mb-4"><h5>Buat Fund</h5><Row><Col md={3}><FormGroup><Label>Fund Code</Label><Input required value={form.fund_code} onChange={(e) => setForm({ ...form, fund_code: e.target.value.toUpperCase() })} /></FormGroup></Col><Col md={3}><FormGroup><Label>Nama</Label><Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></FormGroup></Col><Col md={2}><FormGroup><Label>Type</Label><Input type="select" value={form.fund_type} onChange={(e) => setForm({ ...form, fund_type: e.target.value })}><option value="zakat">Zakat</option><option value="infaq">Infaq</option><option value="sedekah">Sedekah</option><option value="amil">Amil</option></Input></FormGroup></Col><Col md={2}><FormGroup><Label>Category</Label><Input value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} /></FormGroup></Col><Col md={2} className="d-flex align-items-end"><Button color="success" type="submit">Simpan</Button></Col></Row></Form> : null}<DataTable columns={columns} rows={rows} meta={meta} loading={false} onPageChange={setPage} rowKey={(row) => row.id} emptyMessage="Belum ada fund." /></CardBody></Card></Container></div>;
};
export default FundsPage;
