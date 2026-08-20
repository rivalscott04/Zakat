import React, { useEffect, useState } from "react";
import {
  Alert,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Form,
  FormGroup,
  Input,
  Label,
  Row,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import ErrorAlert from "../components/ErrorAlert";
import StatusBadge from "../components/StatusBadge";
import { api, getData, getPage } from "../api/client";
import { getUserErrorMessage } from "../api/userMessage";
import { useAuth } from "../auth/AuthProvider";
import type { Program, ProgramDashboard } from "../api/types";

type ProgramForm = {
  name: string;
  program_type: string;
  capacity_limit: string;
  target_beneficiary: string;
  waitlist_enabled: boolean;
};
const emptyProgram: ProgramForm = {
  name: "",
  program_type: "assistance",
  capacity_limit: "",
  target_beneficiary: "",
  waitlist_enabled: true,
};

const ProgramsPage = () => {
  const { can } = useAuth();
  const [rows, setRows] = useState<Program[]>([]);
  const [dashboard, setDashboard] = useState<ProgramDashboard | null>(null);
  const [selected, setSelected] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [program, setProgram] = useState<ProgramForm>(emptyProgram);
  const [period, setPeriod] = useState({
    period_code: "",
    name: "",
    start_date: "",
    end_date: "",
  });
  const [fund, setFund] = useState({ fund_id: "", priority: "0" });
  const [budget, setBudget] = useState({ fund_id: "", budget_amount: "" });
  const [rule, setRule] = useState({
    rule_code: "",
    field: "status",
    operator: "equals",
    value: "active",
    required: true,
  });
  const [mustahikId, setMustahikId] = useState("");
  const [enrollment, setEnrollment] = useState({
    mustahik_id: "",
    assessment_id: "",
    eligibility_result: "eligible",
  });
  const [activity, setActivity] = useState({
    activity_code: "",
    name: "",
    activity_type: "training",
  });
  const [target, setTarget] = useState({
    target_type: "beneficiary",
    name: "",
    target_value: "",
    unit: "orang",
  });
  const [output, setOutput] = useState({
    output_code: "",
    name: "",
    target_value: "",
    unit: "orang",
  });
  const [outcome, setOutcome] = useState({
    outcome_code: "",
    name: "",
    target_value: "",
    unit: "indicator",
  });
  const load = async () => {
    try {
      const [list, summary] = await Promise.all([
        getPage<Program>("/programs"),
        getData<ProgramDashboard>("/programs/dashboard"),
      ]);
      setRows(list.data);
      setDashboard(summary);
      if (!selected && list.data[0]) setSelected(list.data[0].id);
    } catch (e) {
      setError(getUserErrorMessage(e));
    }
  };
  useEffect(() => {
    void load();
  }, []);
  const send = async (
    url: string,
    payload: Record<string, unknown>,
    reset?: () => void,
  ) => {
    try {
      await api.post(url, payload);
      reset?.();
      await load();
    } catch (e) {
      setError(getUserErrorMessage(e));
    }
  };
  const requireSelected = () => {
    if (!selected) setError("Pilih program terlebih dahulu.");
    return Boolean(selected);
  };
  const createProgram = async (event: React.FormEvent) => {
    event.preventDefault();
    await send(
      "/programs",
      {
        ...program,
        capacity_limit: program.capacity_limit
          ? Number(program.capacity_limit)
          : undefined,
        target_beneficiary: program.target_beneficiary
          ? Number(program.target_beneficiary)
          : undefined,
      },
      () => setProgram(emptyProgram),
    );
  };
  const transition = async (id: string, action: string) => {
    await send(`/programs/${id}/${action}`, {});
  };
  const columns: Column<Program>[] = [
    {
      header: "Program",
      render: (row) => (
        <>
          <div className="fw-medium">{row.program_code}</div>
          <small className="text-muted">{row.name}</small>
        </>
      ),
    },
    { header: "Type", render: (row) => row.program_type },
    { header: "Capacity", render: (row) => row.capacity_limit ?? "-" },
    { header: "Status", render: (row) => <StatusBadge status={row.status} /> },
    {
      header: "Actions",
      render: (row) => (
        <div className="d-flex gap-1 flex-wrap">
          <Button
            size="sm"
            color={selected === row.id ? "primary" : "light"}
            onClick={() => setSelected(row.id)}
          >
            Detail
          </Button>
          {row.status === "draft" ? (
            <Button
              size="sm"
              color="warning"
              onClick={() => void transition(row.id, "submit")}
            >
              Submit
            </Button>
          ) : null}
          {row.status === "pending_approval" ? (
            <Button
              size="sm"
              color="success"
              onClick={() => void transition(row.id, "approve")}
            >
              Approve
            </Button>
          ) : null}
          {row.status === "active" ? (
            <Button
              size="sm"
              color="info"
              onClick={() => void transition(row.id, "suspend")}
            >
              Suspend
            </Button>
          ) : null}
          {row.status === "suspended" ? (
            <Button
              size="sm"
              color="success"
              onClick={() => void transition(row.id, "activate")}
            >
              Activate
            </Button>
          ) : null}
          {row.status === "active" ? (
            <Button
              size="sm"
              color="primary"
              onClick={() => void transition(row.id, "complete")}
            >
              Complete
            </Button>
          ) : null}
          {row.status === "completed" ? (
            <Button
              size="sm"
              color="dark"
              onClick={() => void transition(row.id, "close")}
            >
              Close
            </Button>
          ) : null}
        </div>
      ),
    },
  ];
  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Program Management" pageTitle="Mustahik" />
        {error ? (
          <ErrorAlert error={error} onClose={() => setError(null)} />
        ) : null}
        {dashboard ? (
          <Row className="mb-3">
            {[
              ["Active", dashboard.active_programs],
              ["Budget", dashboard.total_budget],
              ["Committed", dashboard.committed_budget],
              ["Disbursed", dashboard.disbursed_amount],
              ["Beneficiary", dashboard.active_beneficiaries],
            ].map(([label, value]) => (
              <Col md={2} key={String(label)}>
                <Card>
                  <CardBody>
                    <small className="text-muted">{label}</small>
                    <h5 className="mb-0">{value}</h5>
                  </CardBody>
                </Card>
              </Col>
            ))}
          </Row>
        ) : null}
        <Card>
          <CardBody>
            {can("program.create") ? (
              <Form
                onSubmit={createProgram}
                className="border rounded p-3 mb-4"
              >
                <h5>Buat Program</h5>
                <Row>
                  <Col md={3}>
                    <FormGroup>
                      <Label>Nama</Label>
                      <Input
                        required
                        value={program.name}
                        onChange={(e) =>
                          setProgram({ ...program, name: e.target.value })
                        }
                      />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label>Type</Label>
                      <Input
                        type="select"
                        value={program.program_type}
                        onChange={(e) =>
                          setProgram({
                            ...program,
                            program_type: e.target.value,
                          })
                        }
                      >
                        <option value="assistance">Assistance</option>
                        <option value="empowerment">Empowerment</option>
                        <option value="scholarship">Scholarship</option>
                        <option value="emergency">Emergency</option>
                        <option value="development">Development</option>
                        <option value="service">Service</option>
                        <option value="campaign">Campaign</option>
                        <option value="custom">Custom</option>
                      </Input>
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label>Capacity</Label>
                      <Input
                        type="number"
                        min="1"
                        value={program.capacity_limit}
                        onChange={(e) =>
                          setProgram({
                            ...program,
                            capacity_limit: e.target.value,
                          })
                        }
                      />
                    </FormGroup>
                  </Col>
                  <Col md={2}>
                    <FormGroup>
                      <Label>Target</Label>
                      <Input
                        type="number"
                        min="0"
                        value={program.target_beneficiary}
                        onChange={(e) =>
                          setProgram({
                            ...program,
                            target_beneficiary: e.target.value,
                          })
                        }
                      />
                    </FormGroup>
                  </Col>
                  <Col md={1} className="d-flex align-items-center">
                    <FormGroup check>
                      <Input
                        type="checkbox"
                        checked={program.waitlist_enabled}
                        onChange={(e) =>
                          setProgram({
                            ...program,
                            waitlist_enabled: e.target.checked,
                          })
                        }
                      />
                      <Label check>Waitlist</Label>
                    </FormGroup>
                  </Col>
                  <Col md={2} className="d-flex align-items-end">
                    <Button color="success" type="submit">
                      Simpan Draft
                    </Button>
                  </Col>
                </Row>
              </Form>
            ) : null}
            <DataTable
              columns={columns}
              rows={rows}
              loading={false}
              rowKey={(row) => row.id}
              emptyMessage="Belum ada program."
            />
          </CardBody>
        </Card>
        {selected ? (
          <Card className="mt-3">
            <CardBody>
              <h5>Program Detail & Konfigurasi</h5>
              <p className="text-muted small">Program ID: {selected}</p>
              <Row>
                <Col md={6}>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(`/programs/${selected}/periods`, period, () =>
                          setPeriod({
                            period_code: "",
                            name: "",
                            start_date: "",
                            end_date: "",
                          }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Period</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Kode"
                          value={period.period_code}
                          onChange={(e) =>
                            setPeriod({
                              ...period,
                              period_code: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          placeholder="Nama"
                          value={period.name}
                          onChange={(e) =>
                            setPeriod({ ...period, name: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          type="date"
                          value={period.start_date}
                          onChange={(e) =>
                            setPeriod({ ...period, start_date: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          type="date"
                          value={period.end_date}
                          onChange={(e) =>
                            setPeriod({ ...period, end_date: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Tambah</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/funds`,
                          { ...fund, priority: Number(fund.priority) },
                          () => setFund({ fund_id: "", priority: "0" }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Program Fund</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Fund ID"
                          value={fund.fund_id}
                          onChange={(e) =>
                            setFund({ ...fund, fund_id: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          type="number"
                          placeholder="Priority"
                          value={fund.priority}
                          onChange={(e) =>
                            setFund({ ...fund, priority: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Hubungkan</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/budgets`,
                          {
                            fund_id: budget.fund_id,
                            budget_amount: Number(budget.budget_amount),
                          },
                          () => setBudget({ fund_id: "", budget_amount: "" }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Budget</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Fund ID"
                          value={budget.fund_id}
                          onChange={(e) =>
                            setBudget({ ...budget, fund_id: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          type="number"
                          placeholder="Nominal"
                          value={budget.budget_amount}
                          onChange={(e) =>
                            setBudget({
                              ...budget,
                              budget_amount: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Tambah Budget</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/eligibility-rules`,
                          { ...rule, rule_code: rule.rule_code.toUpperCase() },
                          () => setRule({ ...rule, rule_code: "" }),
                        );
                    }}
                    className="border rounded p-3"
                  >
                    <h6>Eligibility Rule</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Rule code"
                          value={rule.rule_code}
                          onChange={(e) =>
                            setRule({ ...rule, rule_code: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          placeholder="Field"
                          value={rule.field}
                          onChange={(e) =>
                            setRule({ ...rule, field: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          type="select"
                          value={rule.operator}
                          onChange={(e) =>
                            setRule({ ...rule, operator: e.target.value })
                          }
                        >
                          <option value="equals">Equals</option>
                          <option value="in">In</option>
                          <option value="greater_than">Greater than</option>
                          <option value="exists">Exists</option>
                        </Input>
                      </Col>
                      <Col>
                        <Input
                          placeholder="Value"
                          value={rule.value}
                          onChange={(e) =>
                            setRule({ ...rule, value: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Tambah</Button>
                      </Col>
                    </Row>
                  </Form>
                </Col>
                <Col md={6}>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/evaluate-eligibility`,
                          { mustahik_id: mustahikId },
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Eligibility Evaluation</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Mustahik ID"
                          value={mustahikId}
                          onChange={(e) => setMustahikId(e.target.value)}
                        />
                      </Col>
                      <Col>
                        <Button color="warning">Evaluate</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/enrollments`,
                          enrollment,
                          () =>
                            setEnrollment({
                              mustahik_id: "",
                              assessment_id: "",
                              eligibility_result: "eligible",
                            }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Beneficiary Enrollment</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Mustahik ID"
                          value={enrollment.mustahik_id}
                          onChange={(e) =>
                            setEnrollment({
                              ...enrollment,
                              mustahik_id: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          placeholder="Assessment ID"
                          value={enrollment.assessment_id}
                          onChange={(e) =>
                            setEnrollment({
                              ...enrollment,
                              assessment_id: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Enroll</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/activities`,
                          activity,
                          () =>
                            setActivity({
                              activity_code: "",
                              name: "",
                              activity_type: "training",
                            }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Activity</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Code"
                          value={activity.activity_code}
                          onChange={(e) =>
                            setActivity({
                              ...activity,
                              activity_code: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          placeholder="Nama"
                          value={activity.name}
                          onChange={(e) =>
                            setActivity({ ...activity, name: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          type="select"
                          value={activity.activity_type}
                          onChange={(e) =>
                            setActivity({
                              ...activity,
                              activity_type: e.target.value,
                            })
                          }
                        >
                          <option value="training">Training</option>
                          <option value="mentoring">Mentoring</option>
                          <option value="monitoring">Monitoring</option>
                          <option value="event">Event</option>
                        </Input>
                      </Col>
                      <Col>
                        <Button color="primary">Tambah</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/targets`,
                          {
                            ...target,
                            target_value: Number(target.target_value),
                          },
                          () =>
                            setTarget({
                              ...target,
                              name: "",
                              target_value: "",
                            }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Target</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Nama"
                          value={target.name}
                          onChange={(e) =>
                            setTarget({ ...target, name: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          type="number"
                          placeholder="Nilai"
                          value={target.target_value}
                          onChange={(e) =>
                            setTarget({
                              ...target,
                              target_value: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          placeholder="Unit"
                          value={target.unit}
                          onChange={(e) =>
                            setTarget({ ...target, unit: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Tambah</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/outputs`,
                          {
                            ...output,
                            output_code: output.output_code.toUpperCase(),
                            target_value: Number(output.target_value),
                          },
                          () =>
                            setOutput({
                              ...output,
                              output_code: "",
                              name: "",
                              target_value: "",
                            }),
                        );
                    }}
                    className="border rounded p-3 mb-3"
                  >
                    <h6>Output</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Code"
                          value={output.output_code}
                          onChange={(e) =>
                            setOutput({
                              ...output,
                              output_code: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          placeholder="Nama"
                          value={output.name}
                          onChange={(e) =>
                            setOutput({ ...output, name: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          type="number"
                          placeholder="Target"
                          value={output.target_value}
                          onChange={(e) =>
                            setOutput({
                              ...output,
                              target_value: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Tambah</Button>
                      </Col>
                    </Row>
                  </Form>
                  <Form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (requireSelected())
                        void send(
                          `/programs/${selected}/outcomes`,
                          {
                            ...outcome,
                            outcome_code: outcome.outcome_code.toUpperCase(),
                            target_value: Number(outcome.target_value),
                          },
                          () =>
                            setOutcome({
                              ...outcome,
                              outcome_code: "",
                              name: "",
                              target_value: "",
                            }),
                        );
                    }}
                    className="border rounded p-3"
                  >
                    <h6>Outcome</h6>
                    <Row>
                      <Col>
                        <Input
                          required
                          placeholder="Code"
                          value={outcome.outcome_code}
                          onChange={(e) =>
                            setOutcome({
                              ...outcome,
                              outcome_code: e.target.value,
                              name: "",
                              target_value: "",
                              unit: "indicator",
                            } as typeof outcome)
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          placeholder="Nama"
                          value={outcome.name}
                          onChange={(e) =>
                            setOutcome({ ...outcome, name: e.target.value })
                          }
                        />
                      </Col>
                      <Col>
                        <Input
                          required
                          type="number"
                          placeholder="Target"
                          value={outcome.target_value}
                          onChange={(e) =>
                            setOutcome({
                              ...outcome,
                              target_value: e.target.value,
                            })
                          }
                        />
                      </Col>
                      <Col>
                        <Button color="primary">Tambah</Button>
                      </Col>
                    </Row>
                  </Form>
                </Col>
              </Row>
            </CardBody>
          </Card>
        ) : null}
      </Container>
    </div>
  );
};
export default ProgramsPage;
