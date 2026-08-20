import React, { useEffect, useState } from "react";
import { Alert, Button, Card, CardBody, Col, Container, Form, FormGroup, Input, Label, Row } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { ApiError, api, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { Assessment, AssessmentRequest } from "../api/types";

const AssessmentsPage = () => {
  const { can } = useAuth();
  const [requests, setRequests] = useState<AssessmentRequest[]>([]);
  const [assessments, setAssessments] = useState<Assessment[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({ mustahik_id: "", assessment_type: "initial", priority: "normal", reason: "" });
  const load = async () => { try { const [requestPage, assessmentPage] = await Promise.all([getPage<AssessmentRequest>("/assessment-requests"), getPage<Assessment>("/assessments")]); setRequests(requestPage.data); setAssessments(assessmentPage.data); } catch (e) { setError((e as ApiError).message); } };
  useEffect(() => { void load(); }, []);
  const createRequest = async (event: React.FormEvent) => { event.preventDefault(); try { await api.post("/assessment-requests", form); setForm({ mustahik_id: "", assessment_type: "initial", priority: "normal", reason: "" }); await load(); } catch (e) { setError((e as ApiError).message); } };
  const requestColumns: Column<AssessmentRequest>[] = [{ header: "Request", render: (row) => <><div className="fw-medium">{row.request_number}</div><small className="text-muted">{row.mustahik?.display_name ?? row.mustahik_id}</small></> }, { header: "Type", render: (row) => row.assessment_type }, { header: "Priority", render: (row) => row.priority }, { header: "Status", render: (row) => <StatusBadge status={row.status} /> }];
  const assessmentColumns: Column<Assessment>[] = [{ header: "Assessment", render: (row) => <><div className="fw-medium">{row.assessment_number}</div><small className="text-muted">{row.mustahik?.display_name ?? row.mustahik_id}</small></> }, { header: "Type", render: (row) => row.assessment_type }, { header: "Score", render: (row) => row.total_score ?? "-" }, { header: "Status", render: (row) => <StatusBadge status={row.status} /> }];
  return <div className="page-content"><Container fluid><BreadCrumb title="Assessment" pageTitle="Mustahik" />{error ? <Alert color="danger">{error}</Alert> : null}<Card><CardBody>{can("assessment.request.create") ? <Form onSubmit={createRequest} className="border rounded p-3 mb-4"><h5>Buat Assessment Request</h5><Row><Col md={3}><FormGroup><Label>ID Mustahik</Label><Input required value={form.mustahik_id} onChange={(e) => setForm({ ...form, mustahik_id: e.target.value })} /></FormGroup></Col><Col md={2}><FormGroup><Label>Type</Label><Input type="select" value={form.assessment_type} onChange={(e) => setForm({ ...form, assessment_type: e.target.value })}><option value="initial">Initial</option><option value="reassessment">Reassessment</option><option value="emergency">Emergency</option><option value="verification">Verification</option></Input></FormGroup></Col><Col md={2}><FormGroup><Label>Priority</Label><Input type="select" value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })}><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></Input></FormGroup></Col><Col md={3}><FormGroup><Label>Alasan</Label><Input value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} /></FormGroup></Col><Col md={2} className="d-flex align-items-end"><Button color="success" type="submit">Buat Request</Button></Col></Row></Form> : null}<h5>Assessment Request</h5><DataTable columns={requestColumns} rows={requests} loading={false} rowKey={(row) => row.id} emptyMessage="Belum ada assessment request." /><h5 className="mt-4">Assessment</h5><DataTable columns={assessmentColumns} rows={assessments} loading={false} rowKey={(row) => row.id} emptyMessage="Belum ada assessment." /></CardBody></Card></Container></div>;
};

export default AssessmentsPage;
