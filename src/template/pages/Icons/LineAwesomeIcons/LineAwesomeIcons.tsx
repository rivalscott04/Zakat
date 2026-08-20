import React from "react";
import { Card, CardBody, CardHeader, Col, Container, Row } from "reactstrap";
import BreadCrumb from "../../../../shared/components/Common/BreadCrumb";
import { lineAwesomeSections } from "./lineAwesomeData";

const LineAwesomeIcons = () => {
  document.title = "Line Awesome Icons | ZETRA";

  return (
    <React.Fragment>
      <div className="page-content">
        <Container fluid>
          <BreadCrumb title="Line Awesome" pageTitle="Icons" />
          <Row className="icon-demo-content">
            <Col xs={12}>
              <Card>
                <CardHeader>
                  <h4 className="card-title">Examples</h4>
                  <p className="text-muted mb-0">
                    Use <code>{'<i className="lab la-*-*"></i>'}</code> class.
                  </p>
                </CardHeader>
                <CardBody>
                  {lineAwesomeSections.map((section, sectionIndex) => (
                    <React.Fragment key={section.title}>
                      <h6
                        className={`text-uppercase text-muted fw-semibold${sectionIndex === 0 ? "" : " mt-4"}`}
                      >
                        {section.title}
                      </h6>
                      <Row className="icon-demo-content">
                        {section.icons.map((icon) => (
                          <Col xl={3} lg={4} sm={6} key={icon}>
                            <i className={icon}></i> {icon}
                          </Col>
                        ))}
                      </Row>
                    </React.Fragment>
                  ))}
                </CardBody>
              </Card>
            </Col>
          </Row>
        </Container>
      </div>
    </React.Fragment>
  );
};

export default LineAwesomeIcons;
