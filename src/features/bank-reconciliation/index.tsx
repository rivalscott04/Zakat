import React, { useCallback, useEffect, useState } from "react";
import {
  Badge,
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
  Table,
} from "reactstrap";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import ErrorAlert from "../components/ErrorAlert";
import StatusBadge from "../components/StatusBadge";
import { api, getData, getPage } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type {
  BankAccountItem,
  BankTransactionItem,
  ReconciliationSessionItem,
  ReconciliationSummary,
} from "../api/types";

const emptyAccount = { bank_name: "", account_name: "", account_number: "" };
const emptyImport = { bank_account_id: "", period_start: "", period_end: "", closing_balance: "" };
const emptySession = { bank_account_id: "", period_start: "", period_end: "", opening_balance: "", closing_balance: "" };

const rupiah = (value: string) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(Number(value));

const BankReconciliationPage = () => {
  const { can } = useAuth();
  const [accounts, setAccounts] = useState<BankAccountItem[]>([]);
  const [transactions, setTransactions] = useState<BankTransactionItem[]>([]);
  const [sessions, setSessions] = useState<ReconciliationSessionItem[]>([]);
  const [summary, setSummary] = useState<ReconciliationSummary | null>(null);
  const [selected, setSelected] = useState<ReconciliationSessionItem | null>(null);
  const [error, setError] = useState<unknown>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const [account, setAccount] = useState(emptyAccount);
  const [imp, setImp] = useState(emptyImport);
  const [file, setFile] = useState<File | null>(null);
  const [session, setSession] = useState(emptySession);
  const [range, setRange] = useState({ from: "", to: "" });

  const load = useCallback(async () => {
    try {
      if (can("bank_account.view")) {
        setAccounts((await getPage<BankAccountItem>("/bank-accounts", { per_page: 100 })).data);
      }
      if (can("bank_transaction.view")) {
        setTransactions((await getPage<BankTransactionItem>("/bank-transactions", { per_page: 25 })).data);
      }
      if (can("bank_reconciliation.view")) {
        setSessions((await getPage<ReconciliationSessionItem>("/reconciliation-sessions", { per_page: 25 })).data);
      }
      setError(null);
    } catch (caught) {
      setError(caught);
    }
  }, [can]);

  useEffect(() => {
    void load();
  }, [load]);

  const run = async (action: () => Promise<unknown>, message?: string) => {
    try {
      await action();
      setError(null);
      setNotice(message ?? null);
      await load();
    } catch (caught) {
      setError(caught);
      setNotice(null);
    }
  };

  const openSession = async (item: ReconciliationSessionItem) => {
    setSelected(item);
    try {
      setSummary(await getData<ReconciliationSummary>(`/reconciliation-sessions/${item.id}/summary`));
    } catch (caught) {
      setError(caught);
    }
  };

  const importStatement = (event: React.FormEvent) => {
    event.preventDefault();
    if (!file) return;

    const body = new FormData();
    body.append("file", file);
    Object.entries(imp).forEach(([key, value]) => value !== "" && body.append(key, value));

    void run(async () => {
      await api.post("/bank-statements/import", body, { headers: { "Content-Type": "multipart/form-data" } });
      setImp(emptyImport);
      setFile(null);
    }, "Mutasi berhasil diimpor.");
  };

  document.title = "Rekonsiliasi Bank | ZETRA";

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title="Rekonsiliasi Bank" pageTitle="Keuangan" />

        {error ? <ErrorAlert error={error} onClose={() => setError(null)} /> : null}
        {notice ? <div className="alert alert-success">{notice}</div> : null}

        <Row>
          <Col lg={5}>
            {can("bank_account.create") ? (
              <Card>
                <CardBody>
                  <h5 className="mb-3">Tambah Rekening Bank</h5>
                  <Form
                    onSubmit={(event) => {
                      event.preventDefault();
                      void run(async () => {
                        await api.post("/bank-accounts", account);
                        setAccount(emptyAccount);
                      }, "Rekening tersimpan.");
                    }}
                  >
                    <Input className="mb-2" placeholder="Nama bank" required value={account.bank_name} onChange={(e) => setAccount({ ...account, bank_name: e.target.value })} />
                    <Input className="mb-2" placeholder="Nama rekening" required value={account.account_name} onChange={(e) => setAccount({ ...account, account_name: e.target.value })} />
                    <Input className="mb-2" placeholder="Nomor rekening" required value={account.account_number} onChange={(e) => setAccount({ ...account, account_number: e.target.value })} />
                    <small className="text-muted d-block mb-2">Nomor rekening disimpan terenkripsi dan hanya ditampilkan sebagian.</small>
                    <Button color="primary">Simpan Rekening</Button>
                  </Form>
                </CardBody>
              </Card>
            ) : null}

            {can("bank_statement.import") ? (
              <Card>
                <CardBody>
                  <h5 className="mb-3">Impor Mutasi</h5>
                  <Form onSubmit={importStatement}>
                    <Label for="imp-account">Rekening</Label>
                    <Input id="imp-account" type="select" required value={imp.bank_account_id} onChange={(e) => setImp({ ...imp, bank_account_id: e.target.value })}>
                      <option value="">Pilih rekening</option>
                      {accounts.map((item) => (
                        <option key={item.id} value={item.id}>
                          {item.account_code} · {item.bank_name} · {item.account_number_masked}
                        </option>
                      ))}
                    </Input>
                    <Row className="mt-2">
                      <Col><Input type="date" required value={imp.period_start} onChange={(e) => setImp({ ...imp, period_start: e.target.value })} /></Col>
                      <Col><Input type="date" required value={imp.period_end} onChange={(e) => setImp({ ...imp, period_end: e.target.value })} /></Col>
                    </Row>
                    <Input className="mt-2" type="number" placeholder="Saldo akhir menurut rekening koran" value={imp.closing_balance} onChange={(e) => setImp({ ...imp, closing_balance: e.target.value })} />
                    <Input className="my-2" type="file" accept=".csv,.xlsx" required onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
                    <Button color="success">Impor CSV atau XLSX</Button>
                  </Form>
                </CardBody>
              </Card>
            ) : null}

            {can("bank_reconciliation.create") ? (
              <Card>
                <CardBody>
                  <h5 className="mb-3">Sesi Rekonsiliasi</h5>

                  <Form
                    className="mb-3"
                    onSubmit={(event) => {
                      event.preventDefault();
                      void run(async () => {
                        await api.post("/reconciliation-sessions", session);
                        setSession(emptySession);
                      }, "Sesi dibuat.");
                    }}
                  >
                    <Input className="mb-2" type="select" required value={session.bank_account_id} onChange={(e) => setSession({ ...session, bank_account_id: e.target.value })}>
                      <option value="">Pilih rekening</option>
                      {accounts.map((item) => <option key={item.id} value={item.id}>{item.account_code}</option>)}
                    </Input>
                    <Row>
                      <Col><Input type="date" required value={session.period_start} onChange={(e) => setSession({ ...session, period_start: e.target.value })} /></Col>
                      <Col><Input type="date" required value={session.period_end} onChange={(e) => setSession({ ...session, period_end: e.target.value })} /></Col>
                    </Row>
                    <Row className="mt-2">
                      <Col><Input type="number" placeholder="Saldo awal" value={session.opening_balance} onChange={(e) => setSession({ ...session, opening_balance: e.target.value })} /></Col>
                      <Col><Input type="number" placeholder="Saldo akhir" value={session.closing_balance} onChange={(e) => setSession({ ...session, closing_balance: e.target.value })} /></Col>
                    </Row>
                    <Button className="mt-2" color="primary">Buat Sesi</Button>
                  </Form>

                  <FormGroup className="border-top pt-3">
                    <Label>Tarik transaksi internal</Label>
                    <p className="text-muted fs-12">
                      Menarik payment, collection, dan penyaluran pada rentang tanggal agar tersedia sebagai pasangan pencocokan.
                    </p>
                    <Row>
                      <Col><Input type="date" value={range.from} onChange={(e) => setRange({ ...range, from: e.target.value })} /></Col>
                      <Col><Input type="date" value={range.to} onChange={(e) => setRange({ ...range, to: e.target.value })} /></Col>
                    </Row>
                    <Button
                      className="mt-2"
                      color="secondary"
                      disabled={!range.from || !range.to}
                      onClick={() => void run(() => api.post("/reconciliation-transactions/sync", range), "Transaksi internal disinkronkan.")}
                    >
                      Sinkronkan
                    </Button>
                  </FormGroup>
                </CardBody>
              </Card>
            ) : null}
          </Col>

          <Col lg={7}>
            <Card>
              <CardBody>
                <h5 className="mb-3">Sesi</h5>
                {sessions.length === 0 ? (
                  <p className="text-muted mb-0">Belum ada sesi rekonsiliasi.</p>
                ) : (
                  <Table responsive hover className="align-middle">
                    <thead><tr><th>Sesi</th><th>Periode</th><th>Selisih</th><th>Status</th><th /></tr></thead>
                    <tbody>
                      {sessions.map((item) => (
                        <tr key={item.id}>
                          <td>
                            <button type="button" className="btn btn-link p-0" onClick={() => void openSession(item)}>{item.session_number}</button>
                          </td>
                          <td className="fs-12">{item.period_start} sampai {item.period_end}</td>
                          <td>{rupiah(item.difference_amount)}</td>
                          <td><StatusBadge status={item.status.toLowerCase()} /></td>
                          <td className="text-end">
                            {can("bank_reconciliation.auto_match") ? (
                              <Button size="sm" color="soft-primary" className="me-1" onClick={() => void run(() => api.post(`/reconciliation-sessions/${item.id}/auto-match`), "Auto match selesai.")}>Auto Match</Button>
                            ) : null}
                            {can("bank_reconciliation.complete") ? (
                              <Button size="sm" color="soft-success" className="me-1" onClick={() => void run(() => api.post(`/reconciliation-sessions/${item.id}/complete`), "Sesi diselesaikan.")}>Selesai</Button>
                            ) : null}
                            {can("bank_reconciliation.close") ? (
                              <Button size="sm" color="soft-dark" onClick={() => void run(() => api.post(`/reconciliation-sessions/${item.id}/close`), "Sesi ditutup.")}>Tutup</Button>
                            ) : null}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                )}

                {selected && summary ? (
                  <div className="border rounded p-3 mt-3">
                    <h6>Ringkasan {selected.session_number}</h6>
                    <Row className="fs-13">
                      {[
                        ["Saldo awal", rupiah(summary.opening_balance)],
                        ["Total kredit", rupiah(summary.total_credit)],
                        ["Total debit", rupiah(summary.total_debit)],
                        ["Saldo akhir seharusnya", rupiah(summary.expected_closing_balance)],
                        ["Saldo akhir rekening koran", rupiah(summary.closing_balance)],
                        ["Selisih", rupiah(summary.difference_amount)],
                      ].map(([label, value]) => (
                        <Col md={4} key={label} className="mb-2">
                          <div className="text-muted fs-12 text-uppercase">{label}</div>
                          <div className="fw-medium">{value}</div>
                        </Col>
                      ))}
                    </Row>
                    <div className="mt-2">
                      {summary.balance_valid
                        ? <Badge color="success">Saldo cocok</Badge>
                        : <Badge color="danger">Saldo belum cocok</Badge>}
                      {" "}
                      <span className="text-muted fs-12">
                        {summary.matched} cocok · {summary.partially_matched} sebagian · {summary.unmatched} belum · {summary.possible_duplicates} dugaan duplikat
                      </span>
                    </div>
                  </div>
                ) : null}
              </CardBody>
            </Card>

            <Card>
              <CardBody>
                <h5 className="mb-3">Transaksi Bank</h5>
                {transactions.length === 0 ? (
                  <p className="text-muted mb-0">Belum ada mutasi yang diimpor.</p>
                ) : (
                  <Table responsive hover className="align-middle">
                    <thead><tr><th>Tanggal</th><th>Referensi</th><th>Keterangan</th><th className="text-end">Nominal</th><th>Status</th></tr></thead>
                    <tbody>
                      {transactions.map((item) => (
                        <tr key={item.id}>
                          <td className="fs-12">{item.transaction_date}</td>
                          <td>{item.transaction_reference}</td>
                          <td className="fs-12">
                            {item.description}
                            {item.duplicate_status === "POSSIBLE_DUPLICATE" ? <Badge color="warning" className="ms-1">dugaan duplikat</Badge> : null}
                          </td>
                          <td className="text-end">
                            {item.credit_amount !== "0.00" ? `+${rupiah(item.credit_amount)}` : `-${rupiah(item.debit_amount)}`}
                          </td>
                          <td><StatusBadge status={item.match_status.toLowerCase()} /></td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                )}
              </CardBody>
            </Card>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default BankReconciliationPage;
