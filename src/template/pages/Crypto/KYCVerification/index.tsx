import React from "react";
import { Container, Row } from "reactstrap";
import BreadCrumb from "../../../../shared/components/Common/BreadCrumb";
import KYC from "./KYCVerification";

const KYCVerification = () => {
    document.title = "KYC Application | ZETRA";
    return (
        <React.Fragment>
            <div className="page-content">
                <Container fluid>
                    <BreadCrumb title="KYC Application" pageTitle="Crypto" />
                    <Row>
                        <KYC />
                    </Row>
                </Container>
            </div>
        </React.Fragment>
    );
};

export default KYCVerification;