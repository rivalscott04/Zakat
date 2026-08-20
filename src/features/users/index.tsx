import React, { useState } from "react";
import { Alert, Button, Card, CardBody, Col, Container, Input, Row, UncontrolledDropdown, DropdownToggle, DropdownMenu, DropdownItem } from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { usePagedResource } from "../hooks/usePagedResource";
import { api, ApiError } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { User } from "../api/types";
import UserFormModal from "./UserFormModal";

/** PRD 01 §46 — halaman user management. */
const UsersPage = () => {
  const { can } = useAuth();
  const { rows, meta, loading, error, setPage, search, setSearch, reload } =
    usePagedResource<User>("/users");
  const [editing, setEditing] = useState<User | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const runAction = async (user: User, action: string) => {
    setActionError(null);
    try {
      await api.post(`/users/${user.id}/${action}`);
      reload();
    } catch (caught) {
      setActionError((caught as ApiError).message);
    }
  };

  const columns: Column<User>[] = [
    {
      header: "Nama",
      render: (user) => (
        <div>
          <div className="fw-medium">{user.name}</div>
          <div className="text-muted fs-12">{user.email}</div>
          {user.username ? <div className="text-muted fs-12">@{user.username}</div> : null}
        </div>
      ),
    },
    { header: "Organisasi", render: (user) => user.organization?.name ?? "-" },
    {
      header: "Role",
      render: (user) =>
        user.roles.length === 0 ? (
          <span className="text-muted">-</span>
        ) : (
          user.roles.map((role) => (
            <span key={role.id} className="badge bg-light text-body me-1">
              {role.code}
            </span>
          ))
        ),
    },
    { header: "Status", render: (user) => <StatusBadge status={user.status} /> },
    {
      header: "Login Terakhir",
      render: (user) => (user.last_login_at ? new Date(user.last_login_at).toLocaleString("id-ID") : "-"),
    },
    {
      header: "Aksi",
      className: "text-end",
      render: (user) => (
        <UncontrolledDropdown>
          <DropdownToggle tag="button" className="btn btn-soft-secondary btn-sm">
            <i className="ri-more-fill" />
          </DropdownToggle>
          <DropdownMenu end>
            {can("users.update") ? (
              <>
                <DropdownItem
                  onClick={() => {
                    setEditing(user);
                    setModalOpen(true);
                  }}
                >
                  <i className="ri-pencil-fill me-2 align-bottom text-muted" />
                  Ubah
                </DropdownItem>
                <DropdownItem divider />
                {user.status !== "active" ? (
                  <DropdownItem onClick={() => runAction(user, user.status === "locked" ? "unlock" : "activate")}>
                    <i className="ri-check-line me-2 align-bottom text-muted" />
                    {user.status === "locked" ? "Buka Kunci" : "Aktifkan"}
                  </DropdownItem>
                ) : null}
                {user.status === "active" ? (
                  <DropdownItem onClick={() => runAction(user, "deactivate")}>
                    <i className="ri-pause-circle-line me-2 align-bottom text-muted" />
                    Nonaktifkan
                  </DropdownItem>
                ) : null}
                {user.status !== "suspended" ? (
                  <DropdownItem onClick={() => runAction(user, "suspend")}>
                    <i className="ri-forbid-line me-2 align-bottom text-muted" />
                    Suspend
                  </DropdownItem>
                ) : null}
              </>
            ) : (
              <DropdownItem disabled>Tidak ada aksi</DropdownItem>
            )}
          </DropdownMenu>
        </UncontrolledDropdown>
      ),
    },
  ];

  document.title = "User Management | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="User" pageTitle="Administrasi" />

        {error ? <Alert color="danger">{error}</Alert> : null}
        {actionError ? <Alert color="danger">{actionError}</Alert> : null}

        <Card>
          <CardBody>
            <Row className="g-3 mb-3 align-items-center">
              <Col md={4}>
                <Input
                  type="search"
                  placeholder="Cari nama atau email..."
                  value={search}
                  onChange={(event) => {
                    setPage(1);
                    setSearch(event.target.value);
                  }}
                />
              </Col>
              <Col className="text-end">
                {can("users.create") ? (
                  <Button
                    color="success"
                    onClick={() => {
                      setEditing(null);
                      setModalOpen(true);
                    }}
                  >
                    <i className="ri-add-line align-bottom me-1" />
                    Tambah User
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
              rowKey={(user) => user.id}
              emptyMessage="Belum ada user pada organisasi ini."
            />
          </CardBody>
        </Card>

        <UserFormModal
          isOpen={modalOpen}
          user={editing}
          onClose={() => setModalOpen(false)}
          onSaved={reload}
        />
      </Container>
    </div>
  );
};

export default UsersPage;
