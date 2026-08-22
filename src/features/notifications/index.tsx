import React, { useCallback, useEffect, useState } from "react";
import {
  Badge,
  Button,
  Card,
  CardBody,
  Col,
  Container,
  Input,
  Nav,
  NavItem,
  NavLink,
  Row,
  Table,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import ErrorAlert from "../components/ErrorAlert";
import { api, getData, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { NotificationItem, NotificationRuleItem, NotificationTemplateItem } from "../api/types";

const PRIORITY_COLOR: Record<string, string> = {
  low: "light",
  normal: "info",
  high: "warning",
  urgent: "danger",
};

const CHANNELS = ["in_app", "email", "webhook"];

const STRATEGIES = ["user", "role", "permission", "organization_admin", "source_owner", "custom"];

type Tab = "inbox" | "templates" | "rules";

const emptyTemplate = {
  template_code: "",
  name: "",
  channel: "in_app",
  subject: "",
  content: "",
  variables: "",
};

const emptyRule = {
  event_name: "",
  template_id: "",
  channels: ["in_app"],
  recipient_strategy: "organization_admin",
  priority: "normal",
};

const NotificationsPage = () => {
  const { can } = useAuth();
  const [tab, setTab] = useState<Tab>("inbox");
  const [error, setError] = useState<unknown>(null);

  const [inbox, setInbox] = useState<NotificationItem[]>([]);
  const [unreadOnly, setUnreadOnly] = useState(false);
  const [templates, setTemplates] = useState<NotificationTemplateItem[]>([]);
  const [rules, setRules] = useState<NotificationRuleItem[]>([]);
  const [templateForm, setTemplateForm] = useState(emptyTemplate);
  const [ruleForm, setRuleForm] = useState(emptyRule);

  const loadInbox = useCallback(async () => {
    try {
      setInbox((await getPage<NotificationItem>("/notifications", unreadOnly ? { unread: 1 } : {})).data);
    } catch (caught) {
      setError(caught);
    }
  }, [unreadOnly]);

  const loadTemplates = useCallback(async () => {
    if (!can("notification.template.view")) return;
    try {
      setTemplates((await getPage<NotificationTemplateItem>("/notification-templates")).data);
    } catch (caught) {
      setError(caught);
    }
  }, [can]);

  const loadRules = useCallback(async () => {
    if (!can("notification.rule.view")) return;
    try {
      setRules((await getPage<NotificationRuleItem>("/notification-rules")).data);
    } catch (caught) {
      setError(caught);
    }
  }, [can]);

  useEffect(() => {
    void loadInbox();
  }, [loadInbox]);

  useEffect(() => {
    void loadTemplates();
    void loadRules();
  }, [loadTemplates, loadRules]);

  const act = async (run: () => Promise<unknown>, reload: () => Promise<void>) => {
    try {
      await run();
      await reload();
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  document.title = "Notifikasi | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Notifikasi" pageTitle="Administrasi" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        <Nav tabs className="mb-3">
          <NavItem>
            <NavLink role="button" active={tab === "inbox"} onClick={() => setTab("inbox")}>
              Kotak masuk
            </NavLink>
          </NavItem>
          {can("notification.template.view") ? (
            <NavItem>
              <NavLink role="button" active={tab === "templates"} onClick={() => setTab("templates")}>
                Template
              </NavLink>
            </NavItem>
          ) : null}
          {can("notification.rule.view") ? (
            <NavItem>
              <NavLink role="button" active={tab === "rules"} onClick={() => setTab("rules")}>
                Aturan event
              </NavLink>
            </NavItem>
          ) : null}
        </Nav>

        {tab === "inbox" ? (
          <Card>
            <CardBody>
              <div className="d-flex justify-content-between align-items-center mb-3">
                <div className="form-check form-switch">
                  <Input
                    type="checkbox"
                    className="form-check-input"
                    id="unread-only"
                    checked={unreadOnly}
                    onChange={(e) => setUnreadOnly(e.target.checked)}
                  />
                  <label className="form-check-label" htmlFor="unread-only">
                    Hanya yang belum dibaca
                  </label>
                </div>
                <Button size="sm" color="light" onClick={() => void act(() => api.post("/notifications/read-all"), loadInbox)}>
                  Tandai semua dibaca
                </Button>
              </div>

              <div className="table-responsive">
                <Table hover className="align-middle mb-0">
                  <thead className="table-light">
                    <tr>
                      <th>Notifikasi</th>
                      <th>Event</th>
                      <th>Prioritas</th>
                      <th>Status</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {inbox.length === 0 ? (
                      <tr>
                        <td colSpan={5} className="text-center text-muted py-4">
                          Belum ada notifikasi.
                        </td>
                      </tr>
                    ) : (
                      inbox.map((item) => (
                        <tr key={item.id} className={item.read_at ? "" : "fw-medium"}>
                          <td>
                            <div>{item.title}</div>
                            <small className="text-muted">{item.message}</small>
                          </td>
                          <td className="fs-12">{item.event_name ?? "-"}</td>
                          <td>
                            <Badge color={PRIORITY_COLOR[item.priority]} className={item.priority === "low" ? "text-body" : ""}>
                              {item.priority}
                            </Badge>
                          </td>
                          <td className="fs-12">{item.status}</td>
                          <td className="text-end">
                            <Button
                              size="sm"
                              color="link"
                              className="p-0"
                              onClick={() =>
                                void act(
                                  () => api.post(`/notifications/${item.id}/${item.read_at ? "unread" : "read"}`),
                                  loadInbox,
                                )
                              }
                            >
                              {item.read_at ? "Tandai belum dibaca" : "Tandai dibaca"}
                            </Button>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </Table>
              </div>
            </CardBody>
          </Card>
        ) : null}

        {tab === "templates" ? (
          <Row>
            <Col lg={7}>
              <Card>
                <CardBody>
                  <div className="table-responsive">
                    <Table className="align-middle mb-0">
                      <thead className="table-light">
                        <tr>
                          <th>Kode</th>
                          <th>Channel</th>
                          <th>Status</th>
                          <th />
                        </tr>
                      </thead>
                      <tbody>
                        {templates.map((template) => (
                          <tr key={template.id}>
                            <td>
                              <div className="fw-medium">{template.template_code}</div>
                              <small className="text-muted">{template.name}</small>
                            </td>
                            <td className="fs-12">{template.channel}</td>
                            <td>
                              <Badge color={template.status === "active" ? "success" : "light"} className={template.status === "active" ? "" : "text-body"}>
                                {template.status}
                              </Badge>
                            </td>
                            <td className="text-end">
                              {can("notification.template.manage") ? (
                                <Button
                                  size="sm"
                                  color="link"
                                  className="p-0"
                                  onClick={() =>
                                    void act(
                                      () =>
                                        api.post(
                                          `/notification-templates/${template.id}/${template.status === "active" ? "deactivate" : "activate"}`,
                                        ),
                                      loadTemplates,
                                    )
                                  }
                                >
                                  {template.status === "active" ? "Nonaktifkan" : "Aktifkan"}
                                </Button>
                              ) : null}
                            </td>
                          </tr>
                        ))}
                        {templates.length === 0 ? (
                          <tr>
                            <td colSpan={4} className="text-center text-muted py-4">
                              Belum ada template.
                            </td>
                          </tr>
                        ) : null}
                      </tbody>
                    </Table>
                  </div>
                </CardBody>
              </Card>
            </Col>

            {can("notification.template.create") ? (
              <Col lg={5}>
                <Card>
                  <CardBody>
                    <h6 className="mb-3">Template baru</h6>
                    <Input
                      className="mb-2"
                      placeholder="Kode, huruf dan angka saja"
                      value={templateForm.template_code}
                      onChange={(e) => setTemplateForm({ ...templateForm, template_code: e.target.value })}
                    />
                    <Input
                      className="mb-2"
                      placeholder="Nama"
                      value={templateForm.name}
                      onChange={(e) => setTemplateForm({ ...templateForm, name: e.target.value })}
                    />
                    <Input
                      type="select"
                      className="mb-2"
                      value={templateForm.channel}
                      onChange={(e) => setTemplateForm({ ...templateForm, channel: e.target.value })}
                    >
                      {CHANNELS.map((channel) => (
                        <option key={channel} value={channel}>
                          {channel}
                        </option>
                      ))}
                    </Input>
                    <Input
                      className="mb-2"
                      placeholder="Subjek"
                      value={templateForm.subject}
                      onChange={(e) => setTemplateForm({ ...templateForm, subject: e.target.value })}
                    />
                    <Input
                      type="textarea"
                      rows={4}
                      className="mb-2"
                      placeholder="Isi, gunakan {{variabel}}"
                      value={templateForm.content}
                      onChange={(e) => setTemplateForm({ ...templateForm, content: e.target.value })}
                    />
                    <Input
                      className="mb-3"
                      placeholder="Variabel tambahan, pisahkan dengan koma"
                      value={templateForm.variables}
                      onChange={(e) => setTemplateForm({ ...templateForm, variables: e.target.value })}
                    />
                    <Button
                      color="primary"
                      onClick={() =>
                        void act(async () => {
                          await api.post("/notification-templates", {
                            ...templateForm,
                            subject: templateForm.subject || null,
                            variables: templateForm.variables
                              .split(",")
                              .map((item) => item.trim())
                              .filter(Boolean),
                          });
                          setTemplateForm(emptyTemplate);
                        }, loadTemplates)
                      }
                    >
                      Simpan
                    </Button>
                  </CardBody>
                </Card>
              </Col>
            ) : null}
          </Row>
        ) : null}

        {tab === "rules" ? (
          <Row>
            <Col lg={7}>
              <Card>
                <CardBody>
                  <div className="table-responsive">
                    <Table className="align-middle mb-0">
                      <thead className="table-light">
                        <tr>
                          <th>Event</th>
                          <th>Channel</th>
                          <th>Penerima</th>
                          <th>Status</th>
                          <th />
                        </tr>
                      </thead>
                      <tbody>
                        {rules.map((rule) => (
                          <tr key={rule.id}>
                            <td>
                              <div className="fw-medium">{rule.event_name}</div>
                              <small className="text-muted">{rule.template_code ?? "tanpa template"}</small>
                            </td>
                            <td className="fs-12">{rule.channels.join(", ")}</td>
                            <td className="fs-12">{rule.recipient_strategy}</td>
                            <td>
                              <Badge color={rule.enabled ? "success" : "light"} className={rule.enabled ? "" : "text-body"}>
                                {rule.enabled ? "aktif" : "nonaktif"}
                              </Badge>
                            </td>
                            <td className="text-end">
                              {can("notification.rule.manage") ? (
                                <Button
                                  size="sm"
                                  color="link"
                                  className="p-0"
                                  onClick={() =>
                                    void act(
                                      () => api.post(`/notification-rules/${rule.id}/${rule.enabled ? "disable" : "enable"}`),
                                      loadRules,
                                    )
                                  }
                                >
                                  {rule.enabled ? "Matikan" : "Nyalakan"}
                                </Button>
                              ) : null}
                            </td>
                          </tr>
                        ))}
                        {rules.length === 0 ? (
                          <tr>
                            <td colSpan={5} className="text-center text-muted py-4">
                              Belum ada aturan.
                            </td>
                          </tr>
                        ) : null}
                      </tbody>
                    </Table>
                  </div>
                </CardBody>
              </Card>
            </Col>

            {can("notification.rule.create") ? (
              <Col lg={5}>
                <Card>
                  <CardBody>
                    <h6 className="mb-3">Aturan baru</h6>
                    <Input
                      className="mb-2"
                      placeholder="Nama event, misal payment_paid"
                      value={ruleForm.event_name}
                      onChange={(e) => setRuleForm({ ...ruleForm, event_name: e.target.value })}
                    />
                    <Input
                      type="select"
                      className="mb-2"
                      value={ruleForm.template_id}
                      onChange={(e) => setRuleForm({ ...ruleForm, template_id: e.target.value })}
                    >
                      <option value="">Tanpa template</option>
                      {templates
                        .filter((template) => template.status === "active")
                        .map((template) => (
                          <option key={template.id} value={template.id}>
                            {template.template_code}
                          </option>
                        ))}
                    </Input>
                    <Input
                      type="select"
                      multiple
                      className="mb-2"
                      value={ruleForm.channels}
                      onChange={(e) =>
                        setRuleForm({
                          ...ruleForm,
                          channels: [...(e.target as unknown as HTMLSelectElement).selectedOptions].map(
                            (option) => option.value,
                          ),
                        })
                      }
                    >
                      {CHANNELS.map((channel) => (
                        <option key={channel} value={channel}>
                          {channel}
                        </option>
                      ))}
                    </Input>
                    <Input
                      type="select"
                      className="mb-3"
                      value={ruleForm.recipient_strategy}
                      onChange={(e) => setRuleForm({ ...ruleForm, recipient_strategy: e.target.value })}
                    >
                      {STRATEGIES.map((strategy) => (
                        <option key={strategy} value={strategy}>
                          {strategy}
                        </option>
                      ))}
                    </Input>
                    <Button
                      color="primary"
                      onClick={() =>
                        void act(async () => {
                          await api.post("/notification-rules", {
                            ...ruleForm,
                            template_id: ruleForm.template_id || null,
                          });
                          setRuleForm(emptyRule);
                        }, loadRules)
                      }
                    >
                      Simpan
                    </Button>
                  </CardBody>
                </Card>
              </Col>
            ) : null}
          </Row>
        ) : null}
      </Container>
    </div>
  );
};

export default NotificationsPage;
