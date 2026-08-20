import React from "react";
import { Col, Container, Row } from "reactstrap";
import { APP_NAME, APP_TAGLINE } from "../config/branding";

const Footer = () => {
    return (
        <React.Fragment>
            <footer className="footer">
                <Container fluid>
                    <Row>
                        <Col sm={6}>
                            © {new Date().getFullYear()} {APP_NAME}.
                        </Col>
                        <Col sm={6}>
                            <div className="text-sm-end d-none d-sm-block">
                                {APP_TAGLINE}
                            </div>
                        </Col>
                    </Row>
                </Container>
            </footer>
        </React.Fragment>
    );
};

export default Footer;
