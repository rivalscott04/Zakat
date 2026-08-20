import React, { useEffect, useState } from "react";
import { Alert, Button, Form, FormFeedback, Input, Label, Spinner } from "reactstrap";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useFormik } from "formik";
import * as Yup from "yup";
import AuthShell from "./AuthShell";
import { useAuth } from "../../auth/AuthProvider";
import { ApiError } from "../../api/client";

/** PRD 01 §43 — halaman login. */
const Login = () => {
  const { login, user, initialising } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [error, setError] = useState<string | null>(null);
  const [passwordShow, setPasswordShow] = useState(false);

  const from = (location.state as { from?: string } | null)?.from ?? "/dashboard";

  useEffect(() => {
    if (!initialising && user) {
      navigate(from, { replace: true });
    }
  }, [user, initialising, navigate, from]);

  const validation = useFormik({
    initialValues: { email: "", password: "", remember: false },
    validationSchema: Yup.object({
      email: Yup.string().email("Format email tidak valid").required("Email wajib diisi"),
      password: Yup.string().required("Password wajib diisi"),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      try {
        await login(values.email, values.password, values.remember);
        navigate(from, { replace: true });
      } catch (caught) {
        const apiError = caught as ApiError;
        setError(apiError.message);
        helpers.setFieldError("password", apiError.fieldError("password"));
      } finally {
        helpers.setSubmitting(false);
      }
    },
  });

  document.title = "Masuk | Zakat OS";

  return (
    <AuthShell
      title="Selamat Datang"
      subtitle="Masuk untuk melanjutkan ke Zakat OS."
      footer={
        <p className="mb-0 text-muted">
          Belum punya akses? Hubungi administrator organisasi Anda.
        </p>
      }
    >
      {error ? <Alert color="danger">{error}</Alert> : null}

      <Form onSubmit={validation.handleSubmit}>
        <div className="mb-3">
          <Label htmlFor="email" className="form-label">
            Email
          </Label>
          <Input
            id="email"
            name="email"
            type="email"
            autoComplete="username"
            placeholder="nama@organisasi.id"
            value={validation.values.email}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            invalid={Boolean(validation.touched.email && validation.errors.email)}
          />
          <FormFeedback>{validation.errors.email}</FormFeedback>
        </div>

        <div className="mb-3">
          <div className="float-end">
            <Link to="/forgot-password" className="text-muted">
              Lupa password?
            </Link>
          </div>
          <Label className="form-label" htmlFor="password">
            Password
          </Label>
          <div className="position-relative auth-pass-inputgroup mb-3">
            <Input
              id="password"
              name="password"
              type={passwordShow ? "text" : "password"}
              autoComplete="current-password"
              className="pe-5"
              placeholder="Masukkan password"
              value={validation.values.password}
              onChange={validation.handleChange}
              onBlur={validation.handleBlur}
              invalid={Boolean(validation.touched.password && validation.errors.password)}
            />
            <FormFeedback>{validation.errors.password}</FormFeedback>
            <button
              className="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted"
              type="button"
              aria-label="Tampilkan password"
              onClick={() => setPasswordShow(!passwordShow)}
            >
              <i className="ri-eye-fill align-middle" />
            </button>
          </div>
        </div>

        <div className="form-check">
          <Input
            className="form-check-input"
            type="checkbox"
            id="remember"
            name="remember"
            checked={validation.values.remember}
            onChange={validation.handleChange}
          />
          <Label className="form-check-label" htmlFor="remember">
            Ingat saya
          </Label>
        </div>

        <div className="mt-4">
          <Button color="success" className="w-100" type="submit" disabled={validation.isSubmitting}>
            {validation.isSubmitting ? <Spinner size="sm" className="me-2" /> : null}
            Masuk
          </Button>
        </div>
      </Form>
    </AuthShell>
  );
};

export default Login;
