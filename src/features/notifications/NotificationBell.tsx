import React, { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Badge, Dropdown, DropdownItem, DropdownMenu, DropdownToggle } from "reactstrap";
import { api, getData, getPage } from "../api/client";
import type { NotificationItem } from "../api/types";

/** PRD 16T §41 dan §42 — notification center, versi awal memakai polling. */
const POLL_INTERVAL_MS = 60_000;

const NotificationBell = () => {
  const [open, setOpen] = useState(false);
  const [unread, setUnread] = useState(0);
  const [items, setItems] = useState<NotificationItem[]>([]);

  const loadCount = useCallback(async () => {
    try {
      setUnread((await getData<{ unread_count: number }>("/notifications/unread-count")).unread_count);
    } catch {
      // Lonceng tidak boleh mengganggu halaman kalau backend sedang bermasalah.
    }
  }, []);

  useEffect(() => {
    void loadCount();
    const timer = window.setInterval(() => void loadCount(), POLL_INTERVAL_MS);
    return () => window.clearInterval(timer);
  }, [loadCount]);

  const toggle = async () => {
    const next = !open;
    setOpen(next);

    if (next) {
      try {
        setItems((await getPage<NotificationItem>("/notifications", { per_page: 8 })).data);
      } catch {
        setItems([]);
      }
    }
  };

  const read = async (item: NotificationItem) => {
    if (item.read_at) return;
    await api.post(`/notifications/${item.id}/read`);
    setItems((current) => current.map((row) => (row.id === item.id ? { ...row, read_at: new Date().toISOString() } : row)));
    void loadCount();
  };

  const readAll = async () => {
    await api.post("/notifications/read-all");
    setItems((current) => current.map((row) => ({ ...row, read_at: row.read_at ?? new Date().toISOString() })));
    setUnread(0);
  };

  return (
    <Dropdown isOpen={open} toggle={() => void toggle()} direction="down">
      <DropdownToggle tag="button" className="btn btn-sm btn-outline-secondary position-relative" aria-label="Notifikasi">
        Notifikasi
        {unread > 0 ? (
          <Badge color="danger" pill className="position-absolute top-0 start-100 translate-middle">
            {unread > 99 ? "99+" : unread}
          </Badge>
        ) : null}
      </DropdownToggle>
      <DropdownMenu end style={{ minWidth: 340, maxHeight: 420, overflowY: "auto" }}>
        <div className="d-flex justify-content-between align-items-center px-3 py-2">
          <span className="fw-semibold">Notifikasi</span>
          {unread > 0 ? (
            <button className="btn btn-link btn-sm p-0" onClick={() => void readAll()}>
              Tandai semua dibaca
            </button>
          ) : null}
        </div>
        <DropdownItem divider />

        {items.length === 0 ? (
          <div className="text-muted text-center small py-3">Belum ada notifikasi.</div>
        ) : (
          items.map((item) => (
            <button
              key={item.id}
              type="button"
              className={`dropdown-item text-wrap${item.read_at ? "" : " bg-light"}`}
              onClick={() => void read(item)}
            >
              <div className="d-flex justify-content-between gap-2">
                <span className="fw-medium">{item.title}</span>
                {item.priority === "urgent" ? <Badge color="danger">urgent</Badge> : null}
              </div>
              <div className="small text-muted text-truncate">{item.message}</div>
              <div className="small text-muted">
                {item.created_at ? new Date(item.created_at).toLocaleString("id-ID") : ""}
              </div>
            </button>
          ))
        )}

        <DropdownItem divider />
        <Link className="dropdown-item text-center" to="/notifications">
          Lihat semua
        </Link>
      </DropdownMenu>
    </Dropdown>
  );
};

export default NotificationBell;
