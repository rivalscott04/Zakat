import React, { useEffect, useState } from "react";
import {
  Alert,
  Button,
  Col,
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
import { api, ApiError, getPage } from "../api/client";
import type { Organization } from "../api/types";

interface Props {
  isOpen: boolean;
  organization: Organization | null;
  onClose: () => void;
  onSaved: () => void;
}

const TYPES = ["organization", "branch", "unit", "upz", "platform"];

/** PRD 02 §41 — form organisasi. */
const OrganizationFormModal = ({ isOpen, organization, onClose, onSaved }: Props) => {
  const [parents, setParents] = useState<Organization[]>([]);
  const [error, setError] = useState<string | null>(null);
  const isEdit = organization !== null;

  useEffect(() => {
    if (!isOpen) return;
    getPage<Organization>("/organizations", { per_page: 100 })
      .then((result) => setParents(result.data.filter((item) => item.id !== organization?.id)))
      .catch(() => setParents([]));
  }, [isOpen, organization?.id]);

  const validation = useFormik({
    enableReinitialize: true,
    initialValues: {
      code: organization?.code ?? "",
      name: organization?.name ?? "",
      legal_name: organization?.legal_name ?? "",
      organization_type: organization?.organization_type ?? "organization",
      email: organization?.email ?? "",
      phone: organization?.phone ?? "",
      website: organization?.website ?? "",
      parent_id: organization?.parent?.id ?? "",
    },
    validationSchema: Yup.object({
      code: Yup.string()
        .matches(/^[A-Za-z0-9]+$/, "Kode hanya boleh huruf dan angka")
        .max(20, "Maksimal 20 karakter")
        .required("Kode wajib diisi"),
      name: Yup.string().required("Nama wajib diisi"),
      email: Yup.string().email("Format email tidak valid").nullable(),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      const payload = {
        code: values.code.toUpperCase(),
        name: values.name,
        legal_name: values.legal_name || null,
        email: values.email || null,
        phone: values.phone || null,
        website: values.website || null,
        parent_id: values.parent_id || null,
      };

      try {
        if (isEdit) {
          await api.patch(`/organizations/${organization.id}`, payload);
        } else {
          await api.post("/organizations", { ...payload, organization_type: values.organization_type });
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

  return (
    <Modal isOpen={isOpen} toggle={onClose} centered size="lg">
      <ModalHeader toggle={onClose}>{isEdit ? "Ubah Organisasi" : "Tambah Organisasi"}</ModalHeader>
      <ModalBody>
        {error ? <Alert color="danger">{error}</Alert> : null}
        {isEdit && organization.status !== "draft" ? (
          <Alert color="info">
            Kode organisasi bersifat permanen setelah organisasi keluar dari status draft.
          </Alert>
        ) : null}

        <Form onSubmit={validation.handleSubmit}>
          <Row>
            <Col md={4} className="mb-3">
              <Label htmlFor="org-code">Kode</Label>
              <Input
                id="org-code"
                name="code"
                disabled={isEdit && organization.status !== "draft"}
                value={validation.values.code}
                onChange={validation.handleChange}
                onBlur={validation.handleBlur}
                invalid={Boolean(validation.touched.code && validation.errors.code)}
              />
              <FormFeedback>{validation.errors.code as string}</FormFeedback>
            </Col>
            <Col md={8} className="mb-3">
              <Label htmlFor="org-name">Nama</Label>
              <Input
                id="org-name"
                name="name"
                value={validation.values.name}
                onChange={validation.handleChange}
                onBlur={validation.handleBlur}
                invalid={Boolean(validation.touched.name && validation.errors.name)}
              />
              <FormFeedback>{validation.errors.name as string}</FormFeedback>
            </Col>
          </Row>

          <Row>
            <Col md={6} className="mb-3">
              <Label htmlFor="org-legal-name">Nama Legal</Label>
              <Input
                id="org-legal-name"
                name="legal_name"
                value={validation.values.legal_name ?? ""}
                onChange={validation.handleChange}
              />
            </Col>
            <Col md={6} className="mb-3">
              <Label htmlFor="org-type">Tipe</Label>
              <Input
                id="org-type"
                name="organization_type"
                type="select"
                disabled={isEdit}
                value={validation.values.organization_type}
                onChange={validation.handleChange}
              >
                {TYPES.map((type) => (
                  <option key={type} value={type}>
                    {type}
                  </option>
                ))}
              </Input>
            </Col>
          </Row>

          <Row>
            <Col md={4} className="mb-3">
              <Label htmlFor="org-email">Email</Label>
              <Input
                id="org-email"
                name="email"
                type="email"
                value={validation.values.email ?? ""}
                onChange={validation.handleChange}
                invalid={Boolean(validation.errors.email)}
              />
              <FormFeedback>{validation.errors.email as string}</FormFeedback>
            </Col>
            <Col md={4} className="mb-3">
              <Label htmlFor="org-phone">Telepon</Label>
              <Input
                id="org-phone"
                name="phone"
                value={validation.values.phone ?? ""}
                onChange={validation.handleChange}
              />
            </Col>
            <Col md={4} className="mb-3">
              <Label htmlFor="org-website">Website</Label>
              <Input
                id="org-website"
                name="website"
                placeholder="https://"
                value={validation.values.website ?? ""}
                onChange={validation.handleChange}
              />
            </Col>
          </Row>

          <div className="mb-3">
            <Label htmlFor="org-parent">Parent Organisasi</Label>
            <Input
              id="org-parent"
              name="parent_id"
              type="select"
              value={validation.values.parent_id ?? ""}
              onChange={validation.handleChange}
            >
              <option value="">Tanpa parent (root)</option>
              {parents.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.name} ({item.code})
                </option>
              ))}
            </Input>
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

export default OrganizationFormModal;
