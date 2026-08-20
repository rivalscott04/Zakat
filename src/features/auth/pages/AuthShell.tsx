import React from "react";
import { Card, CardBody, Col, Container, Row } from "reactstrap";
import { Link } from "react-router-dom";
import ParticlesAuth from "./ParticlesAuth";
import logoLight from "../../../assets/images/zetra-logo-light.svg";
import { APP_NAME, APP_TAGLINE } from "../../../shared/config/branding";

/**
 * Kerangka halaman autentikasi. Memakai ParticlesAuth dan komponen aplikasi yang
 * sudah ada, bukan layout baru (CLAUDE.md §14 dan §15).
 */
const AuthShell = ({
  title,
  subtitle,
  children,
  footer,
}: {
  title: string;
  subtitle: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
}) => (
  <ParticlesAuth>
    <div className="auth-page-content">
      <Container>
        <Row>
          <Col lg={12}>
            <div className="text-center mt-sm-5 mb-4 text-white-50">
              <Link to="/login" className="d-inline-block auth-logo">
                <img src={logoLight} alt={APP_NAME} height="20" />
              </Link>
              <p className="mt-3 fs-15 fw-medium">{APP_TAGLINE}</p>
            </div>
          </Col>
        </Row>

        <Row className="justify-content-center">
          <Col md={8} lg={6} xl={5}>
            <Card className="mt-4">
              <CardBody className="p-4">
                <div className="text-center mt-2">
                  <h5 className="text-primary">{title}</h5>
                  <p className="text-muted">{subtitle}</p>
                </div>
                <div className="p-2 mt-4">{children}</div>
              </CardBody>
            </Card>
            {footer ? <div className="mt-4 text-center">{footer}</div> : null}
          </Col>
        </Row>
      </Container>
    </div>
  </ParticlesAuth>
);

export default AuthShell;
