/**
 * API client: talks to PHP API either directly or via Next.js proxy.
 *
 * - If NEXT_PUBLIC_API_URL is set: requests go to that base (e.g. http://localhost:8080).
 * - If not set: requests go to same-origin /api/* and are proxied by Next.js (app/api/[...path]/route.js)
 *   to the PHP API; set API_URL or NEXT_PUBLIC_API_URL on the server for the proxy.
 *
 * PHP API routes (php-api/index.php):
 *   GET /api/roles
 *   POST /api/auth/register, /api/auth/login
 *   /api/users, /api/categories, /api/services (CRUD)
 *   POST /api/bookings, GET /api/bookings/:id
 *   POST /api/professionals/register, GET /api/professionals/view/:id
 */

const getApiUrl = () => {
  return process.env.NEXT_PUBLIC_API_URL || "";
};

/** Base URL of this Next.js app (for server-side fetch to own API). */
export function getAppUrl() {
  if (typeof window !== "undefined") return "";
  return (
    process.env.NEXT_PUBLIC_APP_URL ||
    (process.env.VERCEL_URL ? `https://${process.env.VERCEL_URL}` : null) ||
    "http://127.0.0.1:3000"
  );
}

/**
 * Fetch API. path = e.g. '/api/categories'. Uses direct URL or same-origin proxy.
 * On the server with no API_URL, uses getAppUrl() so the proxy receives an absolute URL.
 */
export async function apiFetch(path, options = {}) {
  const base = getApiUrl();
  const pathStr = path.startsWith("/") ? path : `/${path}`;
  const url = base
    ? `${base.replace(/\/$/, "")}${pathStr}`
    : typeof window === "undefined"
      ? `${getAppUrl()}${pathStr}`
      : pathStr;
  const res = await fetch(url, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...options.headers,
    },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || `API error ${res.status}`);
  }
  return data;
}

// --- Categories ---

/** GET /api/categories. Returns array of { id, name, slug, description, sort_order, is_active }. */
export async function getCategories(all = false) {
  const q = all ? "?all=1" : "";
  const data = await apiFetch(`/api/categories${q}`);
  return data.data?.items ?? data.items ?? [];
}

/** GET /api/categories/:id */
export async function getCategory(id) {
  const res = await apiFetch(`/api/categories/${encodeURIComponent(id)}`);
  return res.data ?? res;
}

// --- Services ---

/** GET /api/services. Optional categoryId to filter. Returns array of { id, category_id, name, slug, description, category_name, ... }. */
export async function getServices(categoryId = null) {
  const q = categoryId != null ? `?category_id=${categoryId}` : "";
  const data = await apiFetch(`/api/services${q}`);
  return data.data?.items ?? data.items ?? [];
}

/** GET /api/services/:id */
export async function getService(id) {
  const res = await apiFetch(`/api/services/${encodeURIComponent(id)}`);
  return res.data ?? res;
}

// --- Bookings ---

/** POST /api/bookings. Body: { name, email?, phone, service, pincode, language }. Returns { bookingId }. */
export async function createBooking(body) {
  const res = await apiFetch("/api/bookings", { method: "POST", body: JSON.stringify(body) });
  console.log(res);
  return res.data ?? res;
}

/** GET /api/bookings/:id. Returns booking object. */
export async function getBooking(bookingId) {
  const res = await apiFetch(`/api/bookings/${encodeURIComponent(bookingId)}`);
  return res.data ?? res;
}

// --- Professionals ---

/** POST /api/professionals/register. Body: { name, phone, email?, city, state, pincode, language, services }. Returns { professionalId }. */
export async function registerProfessional(body) {
  const res = await apiFetch("/api/professionals/register", {
    method: "POST",
    body: JSON.stringify(body),
  });
  return res.data ?? res;
}

/** GET /api/professionals/view/:id. Returns professional object. */
export async function getProfessional(professionalId) {
  const res = await apiFetch(
    `/api/professional/${encodeURIComponent(professionalId)}`
  );
  return res.data ?? res;
}

// --- Auth (for future dashboard or app login) ---

/** POST /api/auth/login. Body: { phone, password }. Returns { user, token, expires_in }. */
export async function login(body) {
  const res = await apiFetch("/api/auth/login", {
    method: "POST",
    body: JSON.stringify(body),
  });
  return res.data ?? res;
}

/** POST /api/auth/register. Body: { name, email, password, phone?, role? }. Returns { user, token, expires_in }. */
export async function register(body) {
  const res = await apiFetch("/api/auth/register", {
    method: "POST",
    body: JSON.stringify(body),
  });
  return res.data ?? res;
}

// --- Roles (public list for dropdowns) ---

/** GET /api/roles. Returns array of { id, name, label }. */
export async function getRoles() {
  const res = await apiFetch("/api/roles");
  return res.data?.items ?? res.items ?? [];
}

/** URL for a service icon/image. Use placeholder if none. */
export function getServiceImageUrl(service) {
  const base = getApiUrl();
  if (service?.icon) {
    const path = service.icon.startsWith("/") ? service.icon : `/${service.icon}`;
    return base ? `${base.replace(/\/$/, "")}${path}` : path;
  }
  return "/service-placeholder.svg";
}

export { getApiUrl };
