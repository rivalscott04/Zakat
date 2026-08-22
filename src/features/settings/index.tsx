import React, { useEffect, useState } from "react";
import {
  Badge,
  Button,
  Card,
  CardBody,
  CardHeader,
  Col,
  Container,
  Input,
  Row,
  Table,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import ErrorAlert from "../components/ErrorAlert";
import { api, getData } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { SettingItem } from "../api/types";

const GROUP_LABEL: Record<string, string> = {
  security: "Keamanan dan Akses",
  locale: "Tampilan dan Lokal",
};

const SOURCE_COLOR: Record<string, string> = {
  DEFAULT: "light",
  GLOBAL: "info",
  ORGANIZATION: "success",
};

const SettingsPage = () => {
  const { can } = useAuth();
  const [items, setItems] = useState<SettingItem[]>([]);
  const [draft, setDraft] = useState<Record<string, string | number | boolean>>({});
  const [error, setError] = useState<unknown>(null);
  const [saving, setSaving] = useState<string | null>(null);
  const editable = can("setting.update");

  const load = async () => {
    try {
      const data = await getData<SettingItem[]>("/settings");
      setItems(data);
      setDraft(Object.fromEntries(data.map((item) => [item.key, item.value])));
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const save = async (scope: string) => {
    const changed = items.filter((item) => item.scope === scope && draft[item.key] !== item.value);

    if (changed.length === 0) return;

    setSaving(scope);
    try {
      await api.put("/settings", {
        scope,
        values: Object.fromEntries(changed.map((item) => [item.key, draft[item.key]])),
      });
      await load();
      setError(null);
    } catch (caught) {
      setError(caught);
    } finally {
      setSaving(null);
    }
  };

  const reset = async (key: string) => {
    try {
      await api.delete(`/settings/${key}`);
      await load();
    } catch (caught) {
      setError(caught);
    }
  };

  document.title = "Pengaturan Sistem | ZETRA";

  const groups = [...new Set(items.map((item) => item.group))];

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Pengaturan Sistem" pageTitle="Administrasi" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}

        {groups.map((group) => {
          const rows = items.filter((item) => item.group === group);
          const scope = rows[0].scope;

          return (
            <Card key={group}>
              <CardHeader className="d-flex justify-content-between align-items-center">
                <div>
                  <h5 className="mb-0">{GROUP_LABEL[group] ?? group}</h5>
                  <small className="text-muted">
                    {scope === "GLOBAL"
                      ? "Berlaku untuk seluruh platform, hanya dapat diubah platform admin."
                      : "Berlaku untuk organisasi aktif."}
                  </small>
                </div>
                {editable ? (
                  <Button color="primary" size="sm" disabled={saving !== null} onClick={() => void save(scope)}>
                    {saving === scope ? "Menyimpan..." : "Simpan"}
                  </Button>
                ) : null}
              </CardHeader>
              <CardBody className="pt-0">
                <div className="table-responsive">
                  <Table className="align-middle mb-0">
                    <thead className="table-light">
                      <tr>
                        <th style={{ width: "40%" }}>Pengaturan</th>
                        <th style={{ width: "25%" }}>Nilai</th>
                        <th>Bawaan</th>
                        <th>Sumber</th>
                        <th />
                      </tr>
                    </thead>
                    <tbody>
                      {rows.map((item) => (
                        <tr key={item.key}>
                          <td>
                            <div className="fw-medium">{item.label}</div>
                            <code className="fs-12 text-muted">{item.key}</code>
                          </td>
                          <td>
                            {item.type === "boolean" ? (
                              <div className="form-check form-switch">
                                <Input
                                  type="checkbox"
                                  className="form-check-input"
                                  disabled={!editable}
                                  checked={Boolean(draft[item.key])}
                                  onChange={(e) => setDraft({ ...draft, [item.key]: e.target.checked })}
                                />
                              </div>
                            ) : (
                              <Input
                                type={item.type === "integer" ? "number" : "text"}
                                bsSize="sm"
                                disabled={!editable}
                                value={String(draft[item.key] ?? "")}
                                onChange={(e) =>
                                  setDraft({
                                    ...draft,
                                    [item.key]: item.type === "integer" ? Number(e.target.value) : e.target.value,
                                  })
                                }
                              />
                            )}
                          </td>
                          <td className="text-muted fs-13">{String(item.default_value)}</td>
                          <td>
                            <Badge color={SOURCE_COLOR[item.source]} className={item.source === "DEFAULT" ? "text-body" : ""}>
                              {item.source}
                            </Badge>
                          </td>
                          <td className="text-end">
                            {editable && item.source !== "DEFAULT" ? (
                              <Button color="link" size="sm" className="text-danger p-0" onClick={() => void reset(item.key)}>
                                Kembalikan bawaan
                              </Button>
                            ) : null}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              </CardBody>
            </Card>
          );
        })}

        {items.length === 0 ? (
          <Row>
            <Col>
              <Card>
                <CardBody className="text-center text-muted py-4">Memuat pengaturan...</CardBody>
              </Card>
            </Col>
          </Row>
        ) : null}
      </Container>
    </div>
  );
};

export default SettingsPage;
