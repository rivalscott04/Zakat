import React, { useState } from "react";
import { Alert, Button, Form, FormFeedback, Input, Label, Spinner } from "reactstrap";
import { Link } from "react-router-dom";
import { useFormik } from "formik";
import * as Yup from "yup";
import AuthShell from "./AuthShell";
import { api, ApiError } from "../../api/client";
import { formatPageTitle } from "../../../shared/config/branding";

/** PRD 01 §44 — response selalu generik, tidak menunjukkan apakah email terdaftar. */
const ForgotPassword = () => {
  const [notice, setNotice] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const validation = useFormik({
    initialValues: { email: "" },
    validationSchema: Yup.object({
      email: Yup.string().email("Format email tidak valid").required("Email wajib diisi"),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      try {
        const response = await api.post("/auth/forgot-password", values);
        setNotice(response.data.data.message);
        helpers.resetForm();
      } catch (caught) {
        setError((caught as ApiError).message);
      } finally {
        helpers.setSubmitting(false);
      }
    },
  });

  document.title = formatPageTitle("Lupa Password");

  return (
    <AuthShell
      title="Lupa Password"
      subtitle="Masukkan email akun Anda untuk menerima tautan reset."
      footer={
        <p className="mb-0 text-muted">
          Ingat password Anda?{" "}
          <Link to="/login" className="fw-semibold text-primary text-decoration-underline">
            Masuk di sini
          </Link>
        </p>
      }
    >
      {notice ? <Alert color="success">{notice}</Alert> : null}
      {error ? <Alert color="danger">{error}</Alert> : null}

      <Form onSubmit={validation.handleSubmit}>
        <div className="mb-4">
          <Label className="form-label" htmlFor="email">
            Email
          </Label>
          <Input
            id="email"
            name="email"
            type="email"
            placeholder="nama@organisasi.id"
            value={validation.values.email}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            invalid={Boolean(validation.touched.email && validation.errors.email)}
          />
          <FormFeedback>{validation.errors.email}</FormFeedback>
        </div>

        <Button color="success" className="w-100" type="submit" disabled={validation.isSubmitting}>
          {validation.isSubmitting ? <Spinner size="sm" className="me-2" /> : null}
          Kirim Tautan Reset
        </Button>
      </Form>
    </AuthShell>
  );
};

export default ForgotPassword;
