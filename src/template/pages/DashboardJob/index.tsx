import React from "react";
import { Container, Row } from "reactstrap";
import BreadCrumb from "../../../shared/components/Common/BreadCrumb";
import SalesByLocations from "../DashboardEcommerce/SalesByLocations";
import ApplicationsStatistic from "./ApplicationsStatistic";
import Candidates from "./Candidates";
import FeaturedCompanies from "./FeaturedCompanies";
import RecentApplicants from "./RecentApplicants";
import RecomendedJobs from "./RecomendedJobs";
import Widgets from "./Widgets";

const DashboardJobs = () => {
  document.title = "Jobs Dashboard | ZETRA";

  return (
    <React.Fragment>
      <div className="page-content">
        <Container fluid>
          <BreadCrumb title="Job Dashboard" pageTitle="Dashboards" />

          <Row>
            <Widgets />
            <FeaturedCompanies />
          </Row>

          <Row>
            <ApplicationsStatistic dataColors='["--vz-secondary", "--vz-info", "--vz-primary"]' />
            <Candidates />
          </Row>

          <Row>
            <RecomendedJobs />
          </Row>

          <Row>
            <RecentApplicants />
            <SalesByLocations />
          </Row>
        </Container>
      </div>
    </React.Fragment>
  );
};

export default DashboardJobs;
