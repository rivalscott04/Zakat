import { useCallback, useEffect, useState } from "react";
import { getPage, PaginationMeta } from "../api/client";

/** State standar untuk halaman list: query, paginasi, reload. */
export function usePagedResource<T>(url: string, extraParams: Record<string, unknown> = {}) {
  const [rows, setRows] = useState<T[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");

  const serialisedParams = JSON.stringify(extraParams);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page, ...(search ? { search } : {}), ...JSON.parse(serialisedParams) };
      const result = await getPage<T>(url, params);
      setRows(result.data);
      setMeta(result.meta);
    } catch (caught) {
      setError((caught as Error).message);
      setRows([]);
    } finally {
      setLoading(false);
    }
  }, [url, page, search, serialisedParams]);

  useEffect(() => {
    load();
  }, [load]);

  return { rows, meta, loading, error, page, setPage, search, setSearch, reload: load };
}
