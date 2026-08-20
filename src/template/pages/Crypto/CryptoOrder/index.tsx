import React, { useEffect } from "react";
import { Container, Row } from "reactstrap";
import BreadCrumb from "../../../../shared/components/Common/BreadCrumb";
import AllOrders from "./AllOrders";
// import { CryptoOrders } from "../../../data/index";

import { useSelector, useDispatch } from "react-redux";
import { getOrderList } from "../../../slices/thunks";
import { createSelector } from "reselect";


const CryproOrder = () => {
    document.title = "Orders | ZETRA";
    const dispatch :any = useDispatch();

    const cryptoorderData = createSelector(
        (state:any) => state.Crypto.orderList,
        (orderList) => orderList
      );
    // Inside your component
    const orderList = useSelector(cryptoorderData);

    useEffect(() => {
        dispatch(getOrderList());
    }, [dispatch]);

    return (
        <React.Fragment>
            <div className="page-content">
                <Container fluid>
                    <BreadCrumb title="Orders" pageTitle="Crypto" />
                    <Row>
                        <AllOrders orderList={orderList} />
                    </Row>
                </Container>
            </div>
        </React.Fragment>
    );
};

export default CryproOrder;