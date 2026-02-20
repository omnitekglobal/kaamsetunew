import { NextResponse } from "next/server";

/**
 * Proxy to PHP API. Forwards all /api/* requests to the backend.
 * Set API_URL or NEXT_PUBLIC_API_URL in .env (e.g. http://localhost:8080).
 */
function getBackendUrl() {
  return process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || "";
}

export async function GET(req, context) {
  return proxyToBackend(req, context);
}

export async function POST(req, context) {
  return proxyToBackend(req, context);
}

export async function PUT(req, context) {
  return proxyToBackend(req, context);
}

export async function PATCH(req, context) {
  return proxyToBackend(req, context);
}

export async function DELETE(req, context) {
  return proxyToBackend(req, context);
}

async function proxyToBackend(req, context) {
  const base = getBackendUrl();
  if (!base) {
    return NextResponse.json(
      { success: false, message: "API_URL / NEXT_PUBLIC_API_URL not configured" },
      { status: 503 }
    );
  }

  const { path } = await context.params;
  const pathStr = Array.isArray(path) ? path.join("/") : path || "";
  const targetPath = `/api/${pathStr}`;
  const search = req.nextUrl?.search || "";
  const url = `${base.replace(/\/$/, "")}${targetPath}${search}`;

  try {
    const headers = new Headers();
    req.headers.forEach((value, key) => {
      if (
        key.toLowerCase() !== "host" &&
        key.toLowerCase() !== "connection"
      ) {
        headers.set(key, value);
      }
    });

    const body = ["GET", "HEAD"].includes(req.method) ? undefined : await req.text();
    const res = await fetch(url, {
      method: req.method,
      headers,
      body: body || undefined,
    });

    const data = await res.json().catch(() => ({}));
    return NextResponse.json(data, { status: res.status });
  } catch (err) {
    console.error("[api proxy]", err);
    return NextResponse.json(
      { success: false, message: err.message || "Proxy error" },
      { status: 502 }
    );
  }
}
