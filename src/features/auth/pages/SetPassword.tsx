import React, { useState } from "react";
import { Alert, Button, Form, FormFeedback, Input, Label, Spinner } from "reactstrap";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useFormik } from "formik";
import * as Yup from "yup";
import AuthShell from "./AuthShell";
import { api, ApiError } from "../../api/client";
import { formatPageTitle } from "../../../shared/config/branding";

/**
 * PRD 01 §45 (reset password) dan PRD 01 §8 (accept invitation).
 * Kedua alur memakai form yang sama: token + email + password baru.
 */
const SetPassword = ({ mode }: { mode: "reset" | "invitation" }) => {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const [error, setError] = useState<string | null>(null);

  const endpoint = mode === "reset" ? "/auth/reset-password" : "/auth/accept-invitation";
  const copy =
    mode === "reset"
      ? { title: "Atur Password Baru", subtitle: "Masukkan password baru untuk akun Anda." }
      : { title: "Aktivasi Akun", subtitle: "Atur password untuk mengaktifkan akun Anda." };

  const validation = useFormik({
    initialValues: {
      email: params.get("email") ?? "",
      password: "",
      password_confirmation: "",
    },
    validationSchema: Yup.object({
      email: Yup.string().email("Format email tidak valid").required("Email wajib diisi"),
      password: Yup.string().min(8, "Password minimal 8 karakter").required("Password wajib diisi"),
      password_confirmation: Yup.string()
        .oneOf([Yup.ref("password")], "Konfirmasi password tidak sama")
        .required("Konfirmasi password wajib diisi"),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      try {
        await api.post(endpoint, { ...values, token: params.get("token") ?? "" });
        navigate("/login", { replace: true });
      } catch (caught) {
        const apiError = caught as ApiError;
        setError(apiError.message);
        helpers.setFieldError("password", apiError.fieldError("password"));
      } finally {
        helpers.setSubmitting(false);
      }
    },
  });

  document.title = formatPageTitle(copy.title);

  if (!params.get("token")) {
    return (
      <AuthShell title={copy.title} subtitle={copy.subtitle}>
        <Alert color="danger" className="mb-0">
          Tautan tidak lengkap. Silakan buka kembali tautan dari email Anda.
        </Alert>
      </AuthShell>
    );
  }

  return (
    <AuthShell
      title={copy.title}
      subtitle={copy.subtitle}
      footer={
        <p className="mb-0 text-muted">
          <Link to="/login" className="fw-semibold text-primary text-decoration-underline">
            Kembali ke halaman masuk
          </Link>
        </p>
      }
    >
      {error ? <Alert color="danger">{error}</Alert> : null}

      <Form onSubmit={validation.handleSubmit}>
        <div className="mb-3">
          <Label className="form-label" htmlFor="email">
            Email
          </Label>
          <Input
            id="email"
            name="email"
            type="email"
            value={validation.values.email}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            invalid={Boolean(validation.touched.email && validation.errors.email)}
          />
          <FormFeedback>{validation.errors.email}</FormFeedback>
        </div>

        <div className="mb-3">
          <Label className="form-label" htmlFor="password">
            Password Baru
          </Label>
          <Input
            id="password"
            name="password"
            type="password"
            autoComplete="new-password"
            value={validation.values.password}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            invalid={Boolean(validation.touched.password && validation.errors.password)}
          />
          <FormFeedback>{validation.errors.password}</FormFeedback>
        </div>

        <div className="mb-4">
          <Label className="form-label" htmlFor="password_confirmation">
            Konfirmasi Password
          </Label>
          <Input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            autoComplete="new-password"
            value={validation.values.password_confirmation}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            invalid={Boolean(
              validation.touched.password_confirmation && validation.errors.password_confirmation,
            )}
          />
          <FormFeedback>{validation.errors.password_confirmation}</FormFeedback>
        </div>

        <Button color="success" className="w-100" type="submit" disabled={validation.isSubmitting}>
          {validation.isSubmitting ? <Spinner size="sm" className="me-2" /> : null}
          Simpan Password
        </Button>
      </Form>
    </AuthShell>
  );
};

export default SetPassword;
