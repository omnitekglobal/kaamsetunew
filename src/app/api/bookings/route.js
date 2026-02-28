import { NextResponse } from "next/server";
import pool from "@/lib/db";

const getBackendUrl = () =>
  process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || "";

/**
 * POST /api/bookings — Try PHP API first; on 404/502 or missing API_URL, create booking in local DB.
 * Always returns { success: true, bookingId } so the frontend works.
 */
export async function POST(req) {
  const body = await req.json().catch(() => ({}));
  const {
    name,
    email,
    phone,
    service,
    pincode,
    language,
  } = body;

  const required = { name, phone, service, pincode, language };
  const missing = Object.entries(required).filter(([, v]) => !v || (typeof v === "string" && !v.trim()));
  if (missing.length) {
    return NextResponse.json(
      { success: false, message: "Name, phone, service, pincode and language are required" },
      { status: 400 }
    );
  }

  const base = getBackendUrl();

  // In all environments we expect API_URL (or NEXT_PUBLIC_API_URL on the server)
  // to point at the PHP API. If it's not set, fail fast with a clear error
  // instead of silently falling back to a local DB with different credentials.
  if (!base) {
    console.error("[api bookings] API_URL / NEXT_PUBLIC_API_URL not configured on server");
    return NextResponse.json(
      {
        success: false,
        message:
          "Booking API is not configured. Set API_URL to your PHP API base URL on the server.",
      },
      { status: 500 }
    );
  }

  try {
    const url = `${base.replace(/\/$/, "")}/api/bookings`;
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      console.error("[api bookings proxy] PHP API error", res.status, data);
      return NextResponse.json(
        {
          success: false,
          message: data.message || `PHP API error ${res.status}`,
        },
        { status: res.status }
      );
    }

    const bookingId = data.data?.bookingId ?? data.bookingId;
    if (!bookingId || typeof bookingId !== "string" || !bookingId.trim()) {
      console.error("[api bookings proxy] PHP did not return bookingId", data);
      return NextResponse.json(
        {
          success: false,
          message: "Booking API did not return a booking ID. Check PHP API and database.",
        },
        { status: 502 }
      );
    }
    return NextResponse.json(
      { success: true, bookingId: bookingId.trim() },
      { status: 201 }
    );
  } catch (err) {
    console.error("[api bookings proxy] Failed to call PHP API", err);
    return NextResponse.json(
      {
        success: false,
        message: err?.message || "Failed to call PHP booking API",
      },
      { status: 502 }
    );
  }
}
