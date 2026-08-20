import React from "react";
import { Container, Row } from "reactstrap";
import BreadCrumb from "../../../../shared/components/Common/BreadCrumb";
import Widgets from "./Widgets";
import ICO from "./ICO";

const ICOList = () => {
    document.title = "ICO LIST | ZETRA";
    return (
        <React.Fragment>
            <div className="page-content">
                <Container fluid>
                    <BreadCrumb title="ICO LIST" pageTitle="Crypto" />
                    <Row>
                        <Widgets />
                    </Row>
                    <ICO />
                </Container>
            </div>
        </React.Fragment>
    );
};

export default ICOList;