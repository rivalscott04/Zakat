import React, { useCallback, useEffect, useState } from "react";
import {
  Alert,
  Button,
  Card,
  CardBody,
  CardHeader,
  Col,
  Container,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Input,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Nav,
  NavItem,
  NavLink,
  Row,
  TabContent,
  TabPane,
  UncontrolledDropdown,
} from "reactstrap";
import { useParams } from "react-router-dom";
import BreadCrumb from "../../shared/components/Common/BreadCrumb";
import DataTable, { Column } from "../components/DataTable";
import StatusBadge from "../components/StatusBadge";
import { api, ApiError, getData, getPage, PaginationMeta } from "../api/client";
import { useAuth } from "../auth/AuthProvider";
import type { Organization, OrganizationMember, OrganizationSummary, User } from "../api/types";

const MEMBER_TYPES = ["employee", "amil", "volunteer", "auditor", "external"];

/** PRD 02 §42 — detail organisasi beserta member dan sub unit. */
const OrganizationDetail = () => {
  const { organizationId = "" } = useParams();
  const { can } = useAuth();

  const [organization, setOrganization] = useState<Organization | null>(null);
  const [children, setChildren] = useState<OrganizationSummary[]>([]);
  const [members, setMembers] = useState<OrganizationMember[]>([]);
  const [membersMeta, setMembersMeta] = useState<PaginationMeta | undefined>();
  const [membersPage, setMembersPage] = useState(1);
  const [candidates, setCandidates] = useState<User[]>([]);
  const [tab, setTab] = useState("profil");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [addOpen, setAddOpen] = useState(false);
  const [newMember, setNewMember] = useState({ user_id: "", member_type: "employee" });

  const loadOrganization = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setOrganization(await getData<Organization>(`/organizations/${organizationId}`));
      setChildren(await getData<OrganizationSummary[]>(`/organizations/${organizationId}/children`));
    } catch (caught) {
      setError((caught as ApiError).message);
    } finally {
      setLoading(false);
    }
  }, [organizationId]);

  const loadMembers = useCallback(async () => {
    if (!can("members.view")) return;
    try {
      const result = await getPage<OrganizationMember>(`/organizations/${organizationId}/members`, {
        page: membersPage,
      });
      setMembers(result.data);
      setMembersMeta(result.meta);
    } catch (caught) {
      setError((caught as ApiError).message);
    }
  }, [organizationId, membersPage, can]);

  useEffect(() => {
    loadOrganization();
  }, [loadOrganization]);

  useEffect(() => {
    loadMembers();
  }, [loadMembers]);

  useEffect(() => {
    if (!addOpen) return;
    getPage<User>("/users", { per_page: 100 })
      .then((result) => setCandidates(result.data))
      .catch(() => setCandidates([]));
  }, [addOpen]);

  const runMemberAction = async (member: OrganizationMember, action: string) => {
    setError(null);
    try {
      await api.post(`/organizations/${organizationId}/members/${member.id}/${action}`);
      loadMembers();
    } catch (caught) {
      setError((caught as ApiError).message);
    }
  };

  const submitMember = async () => {
    setError(null);
    try {
      await api.post(`/organizations/${organizationId}/members`, newMember);
      setAddOpen(false);
      setNewMember({ user_id: "", member_type: "employee" });
      loadMembers();
      loadOrganization();
    } catch (caught) {
      setError((caught as ApiError).message);
    }
  };

  const memberColumns: Column<OrganizationMember>[] = [
    {
      header: "User",
      render: (member) => (
        <div>
          <div className="fw-medium">{member.user?.name ?? "-"}</div>
          <div className="text-muted fs-12">{member.user?.email}</div>
        </div>
      ),
    },
    { header: "Jenis", render: (member) => member.member_type },
    { header: "Status", render: (member) => <StatusBadge status={member.status} /> },
    {
      header: "Bergabung",
      render: (member) => (member.joined_at ? new Date(member.joined_at).toLocaleDateString("id-ID") : "-"),
    },
    {
      header: "Aksi",
      className: "text-end",
      render: (member) =>
        can("members.update") ? (
          <UncontrolledDropdown>
            <DropdownToggle tag="button" className="btn btn-soft-secondary btn-sm">
              <i className="ri-more-fill" />
            </DropdownToggle>
            <DropdownMenu end>
              {member.status !== "active" && member.status !== "terminated" ? (
                <DropdownItem onClick={() => runMemberAction(member, "activate")}>Aktifkan</DropdownItem>
              ) : null}
              {member.status === "active" ? (
                <DropdownItem onClick={() => runMemberAction(member, "deactivate")}>
                  Nonaktifkan
                </DropdownItem>
              ) : null}
              {member.status !== "terminated" ? (
                <DropdownItem onClick={() => runMemberAction(member, "terminate")}>
                  Akhiri Membership
                </DropdownItem>
              ) : null}
            </DropdownMenu>
          </UncontrolledDropdown>
        ) : (
          <span className="text-muted fs-12">-</span>
        ),
    },
  ];

  document.title = "Detail Organisasi | ZETRA";

  if (loading && !organization) {
    return (
      <div className="page-content">
        <Container fluid>
          <p className="text-muted">Memuat data organisasi...</p>
        </Container>
      </div>
    );
  }

  return (
    <div className="page-content">
      <Container fluid>
        <BreadCrumb title={organization?.name ?? "Organisasi"} pageTitle="Organisasi" />

        {error ? <Alert color="danger">{error}</Alert> : null}

        {organization ? (
          <Card>
            <CardHeader className="border-0 pb-0">
              <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <h5 className="mb-1">{organization.name}</h5>
                  <p className="text-muted mb-0">
                    {organization.business_number} · {organization.code} ·{" "}
                    {organization.organization_type}
                  </p>
                </div>
                <StatusBadge status={organization.status} />
              </div>

              <Nav tabs className="nav-tabs-custom mt-3">
                {["profil", "member", "sub-unit"].map((item) => (
                  <NavItem key={item}>
                    <NavLink
                      href="#"
                      className={tab === item ? "active" : ""}
                      onClick={(event) => {
                        event.preventDefault();
                        setTab(item);
                      }}
                    >
                      {item === "profil" ? "Profil" : item === "member" ? "Member" : "Sub Unit"}
                    </NavLink>
                  </NavItem>
                ))}
              </Nav>
            </CardHeader>

            <CardBody>
              <TabContent activeTab={tab}>
                <TabPane tabId="profil">
                  <Row>
                    {[
                      ["Nama Legal", organization.legal_name],
                      ["Parent", organization.parent?.name],
                      ["Email", organization.email],
                      ["Telepon", organization.phone],
                      ["Website", organization.website],
                      ["Mata Uang", organization.currency],
                      ["Timezone", organization.timezone],
                      ["Jumlah Member", String(organization.members_count ?? 0)],
                      ["Jumlah Amil", String(organization.amils_count ?? 0)],
                    ].map(([label, value]) => (
                      <Col md={4} key={label as string} className="mb-3">
                        <div className="text-muted fs-12 text-uppercase">{label}</div>
                        <div className="fw-medium">{value || "-"}</div>
                      </Col>
                    ))}
                  </Row>
                </TabPane>

                <TabPane tabId="member">
                  <div className="d-flex justify-content-end mb-3">
                    {can("members.create") ? (
                      <Button color="success" size="sm" onClick={() => setAddOpen(true)}>
                        <i className="ri-add-line align-bottom me-1" />
                        Tambah Member
                      </Button>
                    ) : null}
                  </div>
                  <DataTable
                    columns={memberColumns}
                    rows={members}
                    meta={membersMeta}
                    onPageChange={setMembersPage}
                    rowKey={(member) => member.id}
                    emptyMessage="Belum ada member."
                  />
                </TabPane>

                <TabPane tabId="sub-unit">
                  {children.length === 0 ? (
                    <p className="text-muted mb-0">Organisasi ini belum memiliki sub unit.</p>
                  ) : (
                    <ul className="list-group">
                      {children.map((child) => (
                        <li className="list-group-item" key={child.id}>
                          <span className="fw-medium">{child.name}</span>{" "}
                          <span className="text-muted">({child.code})</span>
                        </li>
                      ))}
                    </ul>
                  )}
                </TabPane>
              </TabContent>
            </CardBody>
          </Card>
        ) : null}

        <Modal isOpen={addOpen} toggle={() => setAddOpen(false)} centered>
          <ModalHeader toggle={() => setAddOpen(false)}>Tambah Member</ModalHeader>
          <ModalBody>
            <div className="mb-3">
              <Label htmlFor="member-user">User</Label>
              <Input
                id="member-user"
                type="select"
                value={newMember.user_id}
                onChange={(event) => setNewMember({ ...newMember, user_id: event.target.value })}
              >
                <option value="">Pilih user</option>
                {candidates.map((candidate) => (
                  <option key={candidate.id} value={candidate.id}>
                    {candidate.name} ({candidate.email})
                  </option>
                ))}
              </Input>
            </div>
            <div className="mb-3">
              <Label htmlFor="member-type">Jenis Keanggotaan</Label>
              <Input
                id="member-type"
                type="select"
                value={newMember.member_type}
                onChange={(event) => setNewMember({ ...newMember, member_type: event.target.value })}
              >
                {MEMBER_TYPES.map((type) => (
                  <option key={type} value={type}>
                    {type}
                  </option>
                ))}
              </Input>
            </div>
            <div className="text-end">
              <Button color="light" className="me-2" onClick={() => setAddOpen(false)}>
                Batal
              </Button>
              <Button color="success" disabled={!newMember.user_id} onClick={submitMember}>
                Simpan
              </Button>
            </div>
          </ModalBody>
        </Modal>
      </Container>
    </div>
  );
};

export default OrganizationDetail;
