import React from "react";
import { Card, CardBody, Col, Container, Input, Row } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { usePagedResource } from "../hooks/usePagedResource";
import type { Muzaki } from "../api/types";

const MuzakisPage = () => {
  const { rows, meta, loading, error, setPage, search, setSearch } = usePagedResource<Muzaki>("/muzakis");
  const columns: Column<Muzaki>[] = [
    { header: "Nomor", render: (row) => <span className="fw-medium">{row.business_number}</span> },
    { header: "Nama", render: (row) => row.display_name },
    { header: "Tipe", render: (row) => row.muzaki_type },
    { header: "Status", render: (row) => <StatusBadge status={row.status} /> },
    { header: "Sumber", render: (row) => row.registration_source },
  ];
  return <div className="page-content"><Container fluid><BreadCrumb title="Muzaki" pageTitle="Master Data" /><Row><Col><Card><CardBody>
    <div className="d-flex justify-content-between mb-3"><h4 className="card-title mb-0">Daftar Muzaki</h4><Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari nama atau nomor..." style={{ maxWidth: 320 }} /></div>
    {error ? <div className="alert alert-danger">{error}</div> : null}<DataTable columns={columns} rows={rows} meta={meta} loading={loading} rowKey={(row) => row.id} onPageChange={setPage} />
  </CardBody></Card></Col></Row></Container></div>;
};

export default MuzakisPage;
