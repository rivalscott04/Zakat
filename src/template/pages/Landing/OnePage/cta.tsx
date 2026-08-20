import React from "react";
import { Link } from "react-router-dom";
import { Col, Container, Row } from "reactstrap";

const Cta = () => {
    return (
        <React.Fragment>
            <section className="py-5 bg-primary position-relative">
                <div className="bg-overlay bg-overlay-pattern opacity-50"></div>
                <Container>
                    <Row className="align-items-center gy-4">
                        <Col className="col-sm">
                            <div>
                                <h4 className="text-white mb-0 fw-semibold">Build accountable zakat operations with ZETRA</h4>
                            </div>
                        </Col>
                        <Col className="col-sm-auto">
                            <div>
                                <Link to="/login" target="_blank" rel="noreferrer" className="btn bg-gradient btn-danger"><i className="ri-shopping-cart-2-line align-middle me-1"></i> Buy Now</Link>
                            </div>
                        </Col>
                    </Row>
                </Container>
            </section>
        </React.Fragment>
    );
};
export default Cta;