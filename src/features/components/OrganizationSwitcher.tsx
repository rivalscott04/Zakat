import React, { useState } from "react";
import { Input, Spinner } from "reactstrap";
import { useAuth } from "../auth/AuthProvider";

/** PRD 02 §26 — active organization hanya berubah lewat endpoint backend tervalidasi. */
const OrganizationSwitcher = () => {
  const { user, organizations, switchOrganization } = useAuth();
  const [switching, setSwitching] = useState(false);

  if (!user?.organization || organizations.length < 2) return null;

  return (
    <div className="d-none d-md-flex align-items-center me-2">
      {switching ? <Spinner size="sm" color="primary" className="me-2" /> : null}
      <Input
        type="select"
        bsSize="sm"
        aria-label="Pilih organisasi aktif"
        value={user.organization.id}
        disabled={switching}
        onChange={async (event) => {
          setSwitching(true);
          try {
            await switchOrganization(event.target.value);
          } finally {
            setSwitching(false);
          }
        }}
      >
        {organizations.map((organization) => (
          <option key={organization.id} value={organization.id}>
            {organization.name}
          </option>
        ))}
      </Input>
    </div>
  );
};

export default OrganizationSwitcher;
