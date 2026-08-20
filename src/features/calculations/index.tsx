import React, { useEffect, useState } from "react";
import { Alert, Button, Card, CardBody, Col, Container, Form, FormGroup, Input, Label, Row } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import { ApiError, getPage, postData } from "../api/client";
import type { Muzaki, ZakatRule, ZakatType } from "../api/types";

const CalculationsPage = () => {
  const [muzakis, setMuzakis] = useState<Muzaki[]>([]);
  const [types, setTypes] = useState<ZakatType[]>([]);
  const [rules, setRules] = useState<ZakatRule[]>([]);
  const [muzakiId, setMuzakiId] = useState("");
  const [typeId, setTypeId] = useState("");
  const [inputs, setInputs] = useState<Record<string, string>>({});
  const [result, setResult] = useState<Record<string, unknown> | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    Promise.all([getPage<Muzaki>("/muzakis", { per_page: 100 }), getPage<ZakatType>("/zakat/types", { per_page: 100 })])
      .then(([muzakiPage, typePage]) => { setMuzakis(muzakiPage.data); setTypes(typePage.data); })
      .catch((caught) => setError((caught as ApiError).message));
  }, []);

  useEffect(() => {
    if (!typeId) { setRules([]); return; }
    getPage<ZakatRule>("/zakat/rules", { per_page: 100, zakat_type_id: typeId })
      .then((page) => setRules(page.data.filter((rule) => rule.status === "active")))
      .catch((caught) => setError((caught as ApiError).message));
  }, [typeId]);

  const selectedRule = rules[0];
  const parameters = selectedRule?.parameters ?? [];
  const setInput = (code: string, value: string) => setInputs((current) => ({ ...current, [code]: value }));

  const calculate = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);
    try {
      const normalized = Object.fromEntries(Object.entries(inputs).map(([key, value]) => {
        const parameter = parameters.find((item) => item.parameter_code === key);
        return [key, parameter && ["number", "decimal", "integer"].includes(parameter.data_type) ? Number(value) : value];
      }));
      setResult(await postData<Record<string, unknown>>("/zakat/calculations/preview", { muzaki_id: muzakiId, zakat_type_id: typeId, calculation_date: new Date().toISOString().slice(0, 10), inputs: normalized }));
    } catch (caught) { setError((caught as ApiError).message); }
  };

  const summary = (result?.summary ?? {}) as { zakat_amount?: string; currency?: string };
  const breakdown = (result?.breakdown ?? {}) as { gross_amount?: string; deduction_amount?: string; net_amount?: string; nisab_amount?: string; rate?: number };
  return <div className="page-content"><Container fluid><BreadCrumb title="Kalkulator Zakat" pageTitle="Zakat" />{error ? <Alert color="danger">{error}</Alert> : null}<Row><Col lg={7}><Card><CardBody><h5>Hitung kewajiban zakat</h5><p className="text-muted">Field kalkulasi diambil dari parameter rule aktif pada backend.</p><Form onSubmit={calculate}><FormGroup><Label>Muzaki</Label><Input type="select" value={muzakiId} onChange={(e) => setMuzakiId(e.target.value)} required><option value="">Pilih Muzaki</option>{muzakis.map((muzaki) => <option key={muzaki.id} value={muzaki.id}>{muzaki.display_name} · {muzaki.business_number}</option>)}</Input></FormGroup><FormGroup><Label>Jenis Zakat</Label><Input type="select" value={typeId} onChange={(e) => { setTypeId(e.target.value); setInputs({}); setResult(null); }} required><option value="">Pilih jenis zakat</option>{types.map((type) => <option key={type.id} value={type.id}>{type.name} ({type.code})</option>)}</Input></FormGroup>{selectedRule ? <p className="small text-muted">Rule: {selectedRule.rule_code} · versi {selectedRule.version}</p> : null}{parameters.length === 0 && typeId ? <Alert color="warning">Rule aktif belum memiliki parameter. Tambahkan parameter pada Modul 04.</Alert> : null}<Row>{parameters.map((parameter) => <Col md={6} key={parameter.parameter_code}><FormGroup><Label>{parameter.name}{parameter.is_required ? " *" : ""}</Label><Input type={parameter.data_type === "date" ? "date" : parameter.data_type === "number" || parameter.data_type === "decimal" || parameter.data_type === "integer" ? "number" : "text"} value={inputs[parameter.parameter_code] ?? ""} onChange={(e) => setInput(parameter.parameter_code, e.target.value)} required={parameter.is_required} placeholder={parameter.parameter_code} /></FormGroup></Col>)}</Row><Button color="primary" type="submit" disabled={!selectedRule}>Hitung Preview</Button></Form></CardBody></Card></Col><Col lg={5}>{result ? <Card><CardBody><h5>Hasil kalkulasi</h5><div className="display-6 text-primary mb-3">{summary.zakat_amount} {summary.currency}</div><dl className="row mb-0"><dt className="col-7">Bruto</dt><dd className="col-5 text-end">{breakdown.gross_amount}</dd><dt className="col-7">Potongan</dt><dd className="col-5 text-end">{breakdown.deduction_amount}</dd><dt className="col-7">Netto</dt><dd className="col-5 text-end">{breakdown.net_amount}</dd><dt className="col-7">Nisab</dt><dd className="col-5 text-end">{breakdown.nisab_amount ?? "-"}</dd><dt className="col-7">Kadar</dt><dd className="col-5 text-end">{breakdown.rate ?? 0}%</dd></dl><div className="alert alert-info mt-3 mb-0">Preview belum menjadi transaksi Collection.</div></CardBody></Card> : <Card><CardBody className="text-muted">Hasil kalkulasi akan tampil di sini.</CardBody></Card>}</Col></Row></Container></div>;
};

export default CalculationsPage;
