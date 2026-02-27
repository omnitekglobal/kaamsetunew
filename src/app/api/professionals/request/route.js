import { NextResponse } from "next/server";

const getBackendUrl = () =>
  process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || "";

/**
 * POST /api/professionals/request — proxy to PHP API (phone + optional referral_code).
 * Avoids CORS: browser calls same-origin, server forwards to PHP.
 */
export async function POST(req) {
  const base = getBackendUrl();
  if (!base) {
    return NextResponse.json(
      {
        success: false,
        message:
          "API_URL / NEXT_PUBLIC_API_URL not configured. Set API_URL in .env.",
      },
      { status: 503 }
    );
  }

  let body;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json(
      { success: false, message: "Invalid JSON body" },
      { status: 400 }
    );
  }

  const url = `${base.replace(/\/$/, "")}/api/professionals/request`;
  try {
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      return NextResponse.json(
        { success: false, message: data.message || `API error ${res.status}` },
        { status: res.status }
      );
    }
    return NextResponse.json(data, { status: res.status });
  } catch (err) {
    console.error("[api professionals/request proxy]", err);
    return NextResponse.json(
      {
        success: false,
        message:
          "PHP API unreachable. Start it from php-api with: php -S localhost:8000 router.php",
      },
      { status: 502 }
    );
  }
}


export async function GET(req) {
  return NextResponse.json({ message: "Hello, world!" });
}
