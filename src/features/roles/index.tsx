import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  Alert,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Form,
  FormFeedback,
  Input,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Row,
  Spinner,
} from "reactstrap";
import { useFormik } from "formik";
import * as Yup from "yup";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import { api, ApiError, getData } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { Permission, Role } from "../api/types";

/** PRD 01 §47 — halaman role management dan permission assignment. */
const RolesPage = () => {
  const { can } = useAuth();
  const [roles, setRoles] = useState<Role[]>([]);
  const [permissions, setPermissions] = useState<Permission[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState<Role | null>(null);
  const [modalOpen, setModalOpen] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setRoles(await getData<Role[]>("/roles"));
      if (can("permissions.view")) {
        setPermissions(await getData<Permission[]>("/permissions"));
      }
    } catch (caught) {
      setError((caught as ApiError).message);
    } finally {
      setLoading(false);
    }
  }, [can]);

  useEffect(() => {
    load();
  }, [load]);

  const grouped = useMemo(() => {
    return permissions.reduce<Record<string, Permission[]>>((accumulator, permission) => {
      (accumulator[permission.module] ??= []).push(permission);
      return accumulator;
    }, {});
  }, [permissions]);

  const validation = useFormik({
    enableReinitialize: true,
    initialValues: {
      name: editing?.name ?? "",
      code: editing?.code ?? "",
      description: editing?.description ?? "",
      is_active: editing?.is_active ?? true,
      permission_ids: permissions
        .filter((permission) => editing?.permissions?.includes(permission.name))
        .map((permission) => permission.id),
    },
    validationSchema: Yup.object({
      name: Yup.string().required("Nama role wajib diisi"),
      code: Yup.string()
        .matches(/^[A-Za-z][A-Za-z0-9_]*$/, "Kode hanya boleh huruf, angka, dan underscore")
        .required("Kode role wajib diisi"),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      try {
        if (editing) {
          await api.patch(`/roles/${editing.id}`, {
            name: values.name,
            description: values.description || null,
            is_active: values.is_active,
          });
          await api.put(`/roles/${editing.id}/permissions`, { permission_ids: values.permission_ids });
        } else {
          await api.post("/roles", {
            name: values.name,
            code: values.code.toUpperCase(),
            description: values.description || null,
            is_active: values.is_active,
            permission_ids: values.permission_ids,
          });
        }
        setModalOpen(false);
        load();
      } catch (caught) {
        const apiError = caught as ApiError;
        setError(apiError.message);
        Object.entries(apiError.errors).forEach(([field, messages]) =>
          helpers.setFieldError(field, messages[0]),
        );
      } finally {
        helpers.setSubmitting(false);
      }
    },
  });

  const togglePermission = (permissionId: string) => {
    const current = validation.values.permission_ids;
    validation.setFieldValue(
      "permission_ids",
      current.includes(permissionId)
        ? current.filter((id) => id !== permissionId)
        : [...current, permissionId],
    );
  };

  const columns: Column<Role>[] = [
    {
      header: "Role",
      render: (role) => (
        <div>
          <div className="fw-medium">{role.name}</div>
          <div className="text-muted fs-12">{role.code}</div>
        </div>
      ),
    },
    {
      header: "Lingkup",
      render: (role) =>
        role.is_system ? (
          <span className="badge bg-info-subtle text-info">System</span>
        ) : (
          <span className="badge bg-light text-body">Organisasi</span>
        ),
    },
    { header: "Permission", render: (role) => `${role.permissions?.length ?? 0} permission` },
    {
      header: "Status",
      render: (role) =>
        role.is_active ? (
          <span className="badge bg-success-subtle text-success">Aktif</span>
        ) : (
          <span className="badge bg-secondary-subtle text-secondary">Nonaktif</span>
        ),
    },
    {
      header: "Aksi",
      className: "text-end",
      render: (role) =>
        // PRD 01 §49.6 — role system tidak boleh diubah lewat UI.
        role.is_system || !can("roles.update") ? (
          <span className="text-muted fs-12">Terkunci</span>
        ) : (
          <Button
            size="sm"
            color="soft-secondary"
            onClick={() => {
              setEditing(role);
              setModalOpen(true);
            }}
          >
            <i className="ri-pencil-fill" />
          </Button>
        ),
    },
  ];

  document.title = "Role Management | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Role" pageTitle="Administrasi" />

        {error ? <Alert color="danger">{error}</Alert> : null}

        <Card>
          <CardBody>
            <Row className="mb-3">
              <Col className="text-end">
                {can("roles.create") ? (
                  <Button
                    color="success"
                    onClick={() => {
                      setEditing(null);
                      setModalOpen(true);
                    }}
                  >
                    <i className="ri-add-line align-bottom me-1" />
                    Tambah Role
                  </Button>
                ) : null}
              </Col>
            </Row>

            <DataTable
              columns={columns}
              rows={roles}
              loading={loading}
              rowKey={(role) => role.id}
              emptyMessage="Belum ada role."
            />
          </CardBody>
        </Card>

        <Modal isOpen={modalOpen} toggle={() => setModalOpen(false)} centered size="lg">
          <ModalHeader toggle={() => setModalOpen(false)}>
            {editing ? "Ubah Role" : "Tambah Role"}
          </ModalHeader>
          <ModalBody>
            <Form onSubmit={validation.handleSubmit}>
              <Row>
                <Col md={6} className="mb-3">
                  <Label htmlFor="role-name">Nama</Label>
                  <Input
                    id="role-name"
                    name="name"
                    value={validation.values.name}
                    onChange={validation.handleChange}
                    onBlur={validation.handleBlur}
                    invalid={Boolean(validation.touched.name && validation.errors.name)}
                  />
                  <FormFeedback>{validation.errors.name as string}</FormFeedback>
                </Col>
                <Col md={6} className="mb-3">
                  <Label htmlFor="role-code">Kode</Label>
                  <Input
                    id="role-code"
                    name="code"
                    disabled={Boolean(editing)}
                    value={validation.values.code}
                    onChange={validation.handleChange}
                    onBlur={validation.handleBlur}
                    invalid={Boolean(validation.touched.code && validation.errors.code)}
                  />
                  <FormFeedback>{validation.errors.code as string}</FormFeedback>
                  {editing ? (
                    <div className="text-muted fs-12 mt-1">Kode role tidak dapat diubah.</div>
                  ) : null}
                </Col>
              </Row>

              <div className="mb-3">
                <Label htmlFor="role-description">Deskripsi</Label>
                <Input
                  id="role-description"
                  name="description"
                  type="textarea"
                  rows={2}
                  value={validation.values.description ?? ""}
                  onChange={validation.handleChange}
                />
              </div>

              <div className="form-check mb-3">
                <Input
                  className="form-check-input"
                  type="checkbox"
                  id="role-active"
                  name="is_active"
                  checked={validation.values.is_active}
                  onChange={validation.handleChange}
                />
                <Label className="form-check-label" htmlFor="role-active">
                  Role aktif
                </Label>
              </div>

              <Label>Permission</Label>
              <div className="border rounded p-3 mb-3" style={{ maxHeight: 300, overflowY: "auto" }}>
                {Object.entries(grouped).map(([module, items]) => (
                  <div key={module} className="mb-3">
                    <div className="fw-semibold text-uppercase text-muted fs-11 mb-2">{module}</div>
                    <Row>
                      {items.map((permission) => (
                        <Col md={4} key={permission.id}>
                          <div className="form-check">
                            <Input
                              className="form-check-input"
                              type="checkbox"
                              id={`permission-${permission.id}`}
                              checked={validation.values.permission_ids.includes(permission.id)}
                              onChange={() => togglePermission(permission.id)}
                            />
                            <Label className="form-check-label" htmlFor={`permission-${permission.id}`}>
                              {permission.name}
                            </Label>
                          </div>
                        </Col>
                      ))}
                    </Row>
                  </div>
                ))}
              </div>

              <div className="text-end">
                <Button color="light" type="button" className="me-2" onClick={() => setModalOpen(false)}>
                  Batal
                </Button>
                <Button color="success" type="submit" disabled={validation.isSubmitting}>
                  {validation.isSubmitting ? <Spinner size="sm" className="me-2" /> : null}
                  Simpan
                </Button>
              </div>
            </Form>
          </ModalBody>
        </Modal>
      </Container>
    </div>
  );
};

export default RolesPage;
