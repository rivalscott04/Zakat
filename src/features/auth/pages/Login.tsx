import React, { useEffect, useState } from "react";
import { Alert, Button, Form, FormFeedback, Input, Label, Spinner } from "reactstrap";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useFormik } from "formik";
import * as Yup from "yup";
import AuthShell from "./AuthShell";
import { useAuth } from "../AuthProvider";
import { ApiError } from "../../api/client";
import { landingPath } from "../../layout/menu";
import { APP_NAME, formatPageTitle } from "../../../shared/config/branding";

/** PRD 01 §9 / §43 — halaman login (email atau username). */
const Login = () => {
  const { login, user, initialising, can } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [error, setError] = useState<string | null>(null);
  const [passwordShow, setPasswordShow] = useState(false);

  // Halaman tujuan ditentukan dari permission user, bukan dipatok ke /dashboard
  // yang membutuhkan `muzaki.view` dan tidak dimiliki sebagian besar role.
  const target = (location.state as { from?: string } | null)?.from ?? landingPath(can) ?? "/dashboard";

  useEffect(() => {
    if (!initialising && user) {
      navigate(target, { replace: true });
    }
  }, [user, initialising, navigate, target]);

  const validation = useFormik({
    initialValues: { login: "", password: "", remember: false },
    validationSchema: Yup.object({
      login: Yup.string()
        .required("Email atau username wajib diisi")
        .test("email-or-username", "Format email atau username tidak valid", (value) => {
          if (!value) {
            return false;
          }

          const isEmail = Yup.string().email().isValidSync(value);
          const isUsername = /^[a-zA-Z0-9_-]+$/.test(value);

          return isEmail || isUsername;
        }),
      password: Yup.string().required("Password wajib diisi"),
    }),
    onSubmit: async (values, helpers) => {
      setError(null);
      try {
        await login(values.login, values.password, values.remember);
        navigate(target, { replace: true });
      } catch (caught) {
        const apiError = caught as ApiError;
        setError(apiError.message);
        helpers.setFieldError("password", apiError.fieldError("password"));
      } finally {
        helpers.setSubmitting(false);
      }
    },
  });

  document.title = formatPageTitle("Masuk");

  return (
    <AuthShell
      title="Selamat Datang"
      subtitle={`Masuk untuk melanjutkan ke ${APP_NAME}.`}
      footer={
        <p className="mb-0 text-muted">
          Belum punya akses? Hubungi administrator organisasi Anda.
        </p>
      }
    >
      {error ? <Alert color="danger">{error}</Alert> : null}

      <Form onSubmit={validation.handleSubmit}>
        <div className="mb-3">
          <Label htmlFor="login" className="form-label">
            Email atau username
          </Label>
          <Input
            id="login"
            name="login"
            type="text"
            autoComplete="username"
            placeholder="nama@organisasi.id atau username"
            value={validation.values.login}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            invalid={Boolean(validation.touched.login && validation.errors.login)}
          />
          <FormFeedback>{validation.errors.login}</FormFeedback>
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
