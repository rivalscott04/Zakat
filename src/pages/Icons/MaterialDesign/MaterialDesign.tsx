import React from "react";
import { Card, CardBody, Col, Container, Row } from "reactstrap";
import BreadCrumb from "../../../Components/Common/BreadCrumb";
import {
  materialAllIcons,
  materialNewIcons,
  materialRotateIcons,
  materialSizeIcons,
  materialSpinIcons,
} from "./materialDesignData";

const renderIconGrid = (
  items: readonly { className: string; label: string }[],
  id?: string,
) => (
  <Row className="icon-demo-content" id={id}>
    {items.map((item) => (
      <div className="col-xl-3 col-lg-4 col-sm-6" key={`${id ?? "icons"}-${item.className}-${item.label}`}>
        <i className={item.className}></i>
        {item.label.startsWith("mdi-") ? <span>{item.label}</span> : <> {item.label}</>}
      </div>
    ))}
  </Row>
);

const Materialdesign = () => {
  document.title = "Material Design Icons | Velzon - React Admin & Dashboard Template";

  return (
    <React.Fragment>
      <div className="page-content">
        <Container fluid>
          <BreadCrumb title="Material Design" pageTitle="Icons" />
          <Row className="icon-demo-content">
            <Col className="col-12">
              <Card>
                <CardBody>
                  <h4 className="card-title">New Icons</h4>
                  <p className="card-title-desc mb-2">
                    Use <code>{'<i className="mdi mdi-*-*"></i>'}</code> class.
                    <span className="badge bg-success">v 6.5.95</span>.
                  </p>
                  {renderIconGrid(materialNewIcons, "newIcons")}
                </CardBody>
              </Card>
              <Card>
                <CardBody>
                  <h4 className="card-title mb-4">All Icons</h4>
                  {renderIconGrid(materialAllIcons, "icons")}
                </CardBody>
              </Card>
            </Col>
          </Row>
          <Row>
            <Col className="col-12">
              <Card>
                <CardBody>
                  <h4 className="card-title">Size</h4>
                  {renderIconGrid(materialSizeIcons)}
                </CardBody>
              </Card>
            </Col>
          </Row>
          <Row>
            <Col className="col-12">
              <Card>
                <CardBody>
                  <h4 className="card-title">Rotate</h4>
                  {renderIconGrid(materialRotateIcons)}
                </CardBody>
              </Card>
            </Col>
          </Row>
          <Row>
            <Col className="col-12">
              <Card>
                <CardBody>
                  <h4 className="card-title">Spin</h4>
                  {renderIconGrid(materialSpinIcons)}
                </CardBody>
              </Card>
            </Col>
          </Row>
        </Container>
      </div>
    </React.Fragment>
  );
};

export default Materialdesign;
