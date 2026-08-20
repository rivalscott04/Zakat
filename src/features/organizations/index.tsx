import React, { useState } from "react";
import {
  Alert,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Input,
  Row,
  UncontrolledDropdown,
} from "reactstrap";
import { Link } from "react-router-dom";
import BreadCrumb from "../../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { usePagedResource } from "../hooks/usePagedResource";
import { api, ApiError } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { Organization } from "../api/types";
import OrganizationFormModal from "./OrganizationFormModal";

/** PRD 02 §41 — halaman organization list. */
const OrganizationsPage = () => {
  const { can } = useAuth();
  const { rows, meta, loading, error, setPage, search, setSearch, reload } =
    usePagedResource<Organization>("/organizations");
  const [editing, setEditing] = useState<Organization | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const runAction = async (organization: Organization, action: string) => {
    setActionError(null);
    try {
      await api.post(`/organizations/${organization.id}/${action}`);
      reload();
    } catch (caught) {
      setActionError((caught as ApiError).message);
    }
  };

  const columns: Column<Organization>[] = [
    {
      header: "Organisasi",
      render: (organization) => (
        <div>
          <Link to={`/organizations/${organization.id}`} className="fw-medium">
            {organization.name}
          </Link>
          <div className="text-muted fs-12">
            {organization.code} · {organization.business_number}
          </div>
        </div>
      ),
    },
    { header: "Tipe", render: (organization) => organization.organization_type },
    { header: "Parent", render: (organization) => organization.parent?.name ?? "-" },
    { header: "Sub Unit", render: (organization) => organization.children_count ?? 0 },
    { header: "Status", render: (organization) => <StatusBadge status={organization.status} /> },
    {
      header: "Aksi",
      className: "text-end",
      render: (organization) => (
        <UncontrolledDropdown>
          <DropdownToggle tag="button" className="btn btn-soft-secondary btn-sm">
            <i className="ri-more-fill" />
          </DropdownToggle>
          <DropdownMenu end>
            <DropdownItem tag={Link} to={`/organizations/${organization.id}`}>
              <i className="ri-eye-fill me-2 align-bottom text-muted" />
              Detail
            </DropdownItem>
            {can("organizations.update") ? (
              <>
                <DropdownItem
                  onClick={() => {
                    setEditing(organization);
                    setModalOpen(true);
                  }}
                >
                  <i className="ri-pencil-fill me-2 align-bottom text-muted" />
                  Ubah
                </DropdownItem>
                <DropdownItem divider />
                {organization.status !== "active" ? (
                  <DropdownItem onClick={() => runAction(organization, "activate")}>
                    <i className="ri-check-line me-2 align-bottom text-muted" />
                    Aktifkan
                  </DropdownItem>
                ) : null}
                {organization.status === "active" ? (
                  <DropdownItem onClick={() => runAction(organization, "deactivate")}>
                    <i className="ri-pause-circle-line me-2 align-bottom text-muted" />
                    Nonaktifkan
                  </DropdownItem>
                ) : null}
                {organization.status !== "suspended" ? (
                  <DropdownItem onClick={() => runAction(organization, "suspend")}>
                    <i className="ri-forbid-line me-2 align-bottom text-muted" />
                    Suspend
                  </DropdownItem>
                ) : null}
              </>
            ) : null}
          </DropdownMenu>
        </UncontrolledDropdown>
      ),
    },
  ];

  document.title = "Organisasi | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Organisasi" pageTitle="Administrasi" />

        {error ? <Alert color="danger">{error}</Alert> : null}
        {actionError ? <Alert color="danger">{actionError}</Alert> : null}

        <Card>
          <CardBody>
            <Row className="g-3 mb-3 align-items-center">
              <Col md={4}>
                <Input
                  type="search"
                  placeholder="Cari nama atau kode..."
                  value={search}
                  onChange={(event) => {
                    setPage(1);
                    setSearch(event.target.value);
                  }}
                />
              </Col>
              <Col className="text-end">
                {can("organizations.create") ? (
                  <Button
                    color="success"
                    onClick={() => {
                      setEditing(null);
                      setModalOpen(true);
                    }}
                  >
                    <i className="ri-add-line align-bottom me-1" />
                    Tambah Organisasi
                  </Button>
                ) : null}
              </Col>
            </Row>

            <DataTable
              columns={columns}
              rows={rows}
              meta={meta}
              loading={loading}
              onPageChange={setPage}
              rowKey={(organization) => organization.id}
              emptyMessage="Belum ada organisasi yang dapat Anda akses."
            />
          </CardBody>
        </Card>

        <OrganizationFormModal
          isOpen={modalOpen}
          organization={editing}
          onClose={() => setModalOpen(false)}
          onSaved={reload}
        />
      </Container>
    </div>
  );
};

export default OrganizationsPage;
