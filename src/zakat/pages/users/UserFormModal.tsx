import React, { useEffect, useState } from "react";
import {
  Alert,
  Button,
  Form,
  FormFeedback,
  Input,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Spinner,
} from "reactstrap";
import { useFormik } from "formik";
import * as Yup from "yup";
import { api, ApiError, getData } from "../../api/client";
import type { Role, User } from "../../api/types";

interface Props {
  isOpen: boolean;
  user: User | null;
  onClose: () => void;
  onSaved: () => void;
}

/** PRD 01 §46 — form create dan edit user. Status diubah lewat aksi tersendiri. */
const UserFormModal = ({ isOpen, user, onClose, onSaved }: Props) => {
  const [roles, setRoles] = useState<Role[]>([]);
  const [error, setError] = useState<string | null>(null);
  const isEdit = user !== null;

  useEffect(() => {
    if (!isOpen) return;
    getData<Role[]>("/roles")
      .then((data) => setRoles(data.filter((role) => role.is_active)))
      .catch(() => setRoles([]));
  }, [isOpen]);

  const validation = useFormik({
    enableReinitialize: true,
    initialValues: {
      name: user?.name ?? "",
      email: user?.email ?? "",
      username: user?.username ?? "",
      phone: user?.phone ?? "",
      member_type: "employee",
      role_ids: user?.roles.map((role) => role.id) ?? [],
    },
    validationSchema: Yup.object({
      name: Yup.string().required("Nama wajib diisi"),
      email: Yup.string().email("Format email tidak valid").required("Email wajib diisi"),
      role_ids: isEdit
        ? Yup.array()
        : Yup.array().min(1, "Pilih minimal satu role"),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      try {
        if (isEdit) {
          await api.patch(`/users/${user.id}`, {
            name: values.name,
            email: values.email,
            username: values.username || null,
            phone: values.phone || null,
          });
          await api.put(`/users/${user.id}/roles`, { role_ids: values.role_ids });
        } else {
          await api.post("/users", {
            name: values.name,
            email: values.email,
            username: values.username || null,
            phone: values.phone || null,
            member_type: values.member_type,
            role_ids: values.role_ids,
          });
        }
        onSaved();
        onClose();
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

  const toggleRole = (roleId: string) => {
    const current = validation.values.role_ids;
    validation.setFieldValue(
      "role_ids",
      current.includes(roleId) ? current.filter((id) => id !== roleId) : [...current, roleId],
    );
  };

  return (
    <Modal isOpen={isOpen} toggle={onClose} centered>
      <ModalHeader toggle={onClose}>{isEdit ? "Ubah User" : "Tambah User"}</ModalHeader>
      <ModalBody>
        {error ? <Alert color="danger">{error}</Alert> : null}
        {!isEdit ? (
          <Alert color="info">
            User baru dibuat berstatus <strong>pending</strong> dan menerima undangan untuk mengatur
            password sendiri.
          </Alert>
        ) : null}

        <Form onSubmit={validation.handleSubmit}>
          <div className="mb-3">
            <Label htmlFor="name">Nama</Label>
            <Input
              id="name"
              name="name"
              value={validation.values.name}
              onChange={validation.handleChange}
              onBlur={validation.handleBlur}
              invalid={Boolean(validation.touched.name && validation.errors.name)}
            />
            <FormFeedback>{validation.errors.name as string}</FormFeedback>
          </div>

          <div className="mb-3">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              name="email"
              type="email"
              value={validation.values.email}
              onChange={validation.handleChange}
              onBlur={validation.handleBlur}
              invalid={Boolean(validation.touched.email && validation.errors.email)}
            />
            <FormFeedback>{validation.errors.email as string}</FormFeedback>
          </div>

          <div className="row">
            <div className="col-md-6 mb-3">
              <Label htmlFor="username">Username</Label>
              <Input
                id="username"
                name="username"
                value={validation.values.username ?? ""}
                onChange={validation.handleChange}
                invalid={Boolean(validation.errors.username)}
              />
              <FormFeedback>{validation.errors.username as string}</FormFeedback>
            </div>
            <div className="col-md-6 mb-3">
              <Label htmlFor="phone">Telepon</Label>
              <Input
                id="phone"
                name="phone"
                value={validation.values.phone ?? ""}
                onChange={validation.handleChange}
              />
            </div>
          </div>

          {!isEdit ? (
            <div className="mb-3">
              <Label htmlFor="member_type">Jenis Keanggotaan</Label>
              <Input
                id="member_type"
                name="member_type"
                type="select"
                value={validation.values.member_type}
                onChange={validation.handleChange}
              >
                <option value="employee">Employee</option>
                <option value="amil">Amil</option>
                <option value="volunteer">Volunteer</option>
                <option value="auditor">Auditor</option>
                <option value="external">External</option>
              </Input>
            </div>
          ) : null}

          <div className="mb-3">
            <Label>Role</Label>
            <div className="border rounded p-2" style={{ maxHeight: 180, overflowY: "auto" }}>
              {roles.map((role) => (
                <div className="form-check" key={role.id}>
                  <Input
                    className="form-check-input"
                    type="checkbox"
                    id={`role-${role.id}`}
                    checked={validation.values.role_ids.includes(role.id)}
                    onChange={() => toggleRole(role.id)}
                  />
                  <Label className="form-check-label" htmlFor={`role-${role.id}`}>
                    {role.name} <span className="text-muted">({role.code})</span>
                  </Label>
                </div>
              ))}
            </div>
            {validation.errors.role_ids ? (
              <div className="text-danger fs-12 mt-1">{validation.errors.role_ids as string}</div>
            ) : null}
          </div>

          <div className="text-end">
            <Button color="light" type="button" className="me-2" onClick={onClose}>
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
  );
};

export default UserFormModal;
