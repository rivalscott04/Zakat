import React from "react";
import { Container, Row } from "reactstrap";
import BreadCrumb from "../../../../shared/components/Common/BreadCrumb";
import AllTasks from "./AllTasks";
import Widgets from "./Widgets";


const TaskList = () => {
    document.title = "Tasks List | ZETRA";
    return (
        <React.Fragment>
            <div className="page-content">
                <Container fluid>
                    <BreadCrumb title="Tasks List" pageTitle="Tasks" />
                    <Row>
                        <Widgets />
                    </Row>
                    <AllTasks />
                </Container>
            </div>
        </React.Fragment>
    );
};

export default TaskList;