import React from "react";
import { Card, CardBody, Col, Container, Input, Nav, NavItem, NavLink, Row, Table } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import StatusBadge from "../components/StatusBadge";
import { getPage } from "../api/client";
import type { ZakatRule, ZakatType } from "../api/types";

const ZakatPage = () => {
  const [tab, setTab] = React.useState<"types" | "rules">("types");
  const [search, setSearch] = React.useState("");
  const [types, setTypes] = React.useState<ZakatType[]>([]);
  const [rules, setRules] = React.useState<ZakatRule[]>([]);
  const [error, setError] = React.useState<string | null>(null);
  React.useEffect(() => { void Promise.all([getPage<ZakatType>("/zakat/types", { search }), getPage<ZakatRule>("/zakat/rules")]).then(([typePage, rulePage]) => { setTypes(typePage.data); setRules(rulePage.data); }).catch((e) => setError((e as Error).message)); }, [search]);
  return <div className="page-content"><Container fluid><BreadCrumb title="Zakat" pageTitle="Master Data" /><Row><Col><Card><CardBody>
    <div className="d-flex justify-content-between align-items-center mb-3"><h4 className="card-title mb-0">Konfigurasi Zakat</h4><Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari jenis zakat..." style={{ maxWidth: 280 }} /></div>
    <Nav tabs><NavItem><NavLink className={tab === "types" ? "active" : ""} onClick={() => setTab("types")}>Jenis Zakat</NavLink></NavItem><NavItem><NavLink className={tab === "rules" ? "active" : ""} onClick={() => setTab("rules")}>Rule</NavLink></NavItem></Nav>
    {error ? <div className="alert alert-danger mt-3">{error}</div> : null}<div className="table-responsive mt-3"><Table hover><thead><tr><th>Kode</th><th>Nama</th><th>{tab === "types" ? "Metode" : "Versi"}</th><th>Status</th></tr></thead><tbody>{tab === "types" ? types.map((type) => <tr key={type.id}><td>{type.code}</td><td>{type.name}</td><td>{type.calculation_method}</td><td><StatusBadge status={type.status} /></td></tr>) : rules.map((rule) => <tr key={rule.id}><td>{rule.rule_code}</td><td>{rule.name}</td><td>V{rule.version} · {rule.effective_from}</td><td><StatusBadge status={rule.status} /></td></tr>)}</tbody></Table></div>
  </CardBody></Card></Col></Row></Container></div>;
};
export default ZakatPage;
