import React from "react";
import { Col, Container, Row } from "reactstrap";
import BreadCrumb from "../../../../shared/components/Common/BreadCrumb";

const Starter = () => {
  document.title = "Starter | ZETRA";
  return (
    <React.Fragment>
      <div className="page-content">
        <Container fluid>
          <BreadCrumb title="Starter" pageTitle="Pages" />
          <Row>
            <Col xs={12}>
            </Col>
          </Row>
        </Container>
      </div>
    </React.Fragment>
  );
};

export default Starter;