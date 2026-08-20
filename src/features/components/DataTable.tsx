import React from "react";
import { Spinner, Table } from "reactstrap";
import type { PaginationMeta } from "../api/client";

export interface Column<T> {
  header: string;
  /** Kelas Bootstrap opsional untuk kolom, misalnya `text-end`. */
  className?: string;
  render: (row: T) => React.ReactNode;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  rows: T[];
  meta?: PaginationMeta;
  loading?: boolean;
  emptyMessage?: string;
  onPageChange?: (page: number) => void;
  rowKey: (row: T) => string;
}

/**
 * Tabel dengan paginasi sisi server. ZETRA TableContainer dan Pagination
 * keduanya memuat seluruh dataset di client, sedangkan API ZETRA wajib
 * berpaginasi (PRD 00 §23), jadi bagian paginasinya ditangani di sini.
 * Markup dan kelasnya tetap memakai gaya ZETRA.
 */
function DataTable<T>({
  columns,
  rows,
  meta,
  loading = false,
  emptyMessage = "Belum ada data.",
  onPageChange,
  rowKey,
}: DataTableProps<T>) {
  const pages = meta ? Array.from({ length: meta.last_page }, (_, index) => index + 1) : [];

  return (
    <>
      <div className="table-responsive table-card">
        <Table className="align-middle table-nowrap mb-0">
          <thead className="table-light">
            <tr>
              {columns.map((column) => (
                <th key={column.header} scope="col" className={column.className}>
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={columns.length} className="text-center py-4">
                  <Spinner size="sm" color="primary" /> <span className="ms-2">Memuat data...</span>
                </td>
              </tr>
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={columns.length} className="text-center text-muted py-4">
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              rows.map((row) => (
                <tr key={rowKey(row)}>
                  {columns.map((column) => (
                    <td key={column.header} className={column.className}>
                      {column.render(row)}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </Table>
      </div>

      {meta && meta.last_page > 1 && onPageChange ? (
        <div className="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
          <div className="text-muted">
            Menampilkan halaman {meta.current_page} dari {meta.last_page} ({meta.total} data)
          </div>
          <ul className="pagination pagination-separated mb-0">
            <li className={`page-item ${meta.current_page <= 1 ? "disabled" : ""}`}>
              <button type="button" className="page-link" onClick={() => onPageChange(meta.current_page - 1)}>
                Sebelumnya
              </button>
            </li>
            {pages.map((page) => (
              <li key={page} className={`page-item ${page === meta.current_page ? "active" : ""}`}>
                <button type="button" className="page-link" onClick={() => onPageChange(page)}>
                  {page}
                </button>
              </li>
            ))}
            <li className={`page-item ${meta.current_page >= meta.last_page ? "disabled" : ""}`}>
              <button type="button" className="page-link" onClick={() => onPageChange(meta.current_page + 1)}>
                Berikutnya
              </button>
            </li>
          </ul>
        </div>
      ) : null}
    </>
  );
}

export default DataTable;
