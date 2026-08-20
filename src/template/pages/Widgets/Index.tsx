import React from "react";
import { Container } from "reactstrap";

// import Components
import BreadCrumb from "../../../shared/components/Common/BreadCrumb";

import TileBoxs from "./TileBoxs";
import OtherWidgets from "./OtherWidgets";
import UpcomingActivity from "./UpcomingActivities";
import ChartMapWidgets from "./Chart&MapWidgets";
import EcommerceWidgets from "./EcommerenceWidget";
import CreditCard from "./Creaditcard";

const Widgets = () => {
    document.title = "Widgets | ZETRA";
    return (
        <React.Fragment>
            <div className="page-content">
                <Container fluid>
                    <BreadCrumb title="Widgets" pageTitle="ZETRA" />
                    {/* Tile Boxs Widgets */}
                    <TileBoxs />

                    {/* Other Widgets */}
                    <OtherWidgets 
                    // dataColors='["--vz-success", "--vz-danger"]'
                    />

                    {/* Upcoming Activity */}
                    <UpcomingActivity />

                    {/* Chart & Map Widgets */}
                    <ChartMapWidgets />

                    {/* Chart & EcommerceWidgets  */}
                    <EcommerceWidgets />

                    {/* CreditCard */}
                    <CreditCard />

                </Container>
            </div>
        </React.Fragment>
    );
};

export default Widgets;
