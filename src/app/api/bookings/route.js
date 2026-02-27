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
  if (base) {
    try {
      const url = `${base.replace(/\/$/, "")}/api/bookings`;
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok) {
        const bookingId = data.data?.bookingId ?? data.bookingId;
        return NextResponse.json(
          { success: true, bookingId: bookingId || "KS" + Date.now() },
          { status: res.status }
        );
      }
    } catch (err) {
      console.error("[api bookings proxy]", err);
    }
  }

  // Fallback: create in local DB (same as book-service)
  try {
    const bookingId = "KS" + Date.now();
    await pool.execute(
      `INSERT INTO bookings (bookingId, name, email, phone, service, pincode, language)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [bookingId, name?.trim() ?? "", email?.trim() || null, phone?.trim() ?? "", service?.trim() ?? "", pincode?.trim() ?? "", language?.trim() ?? ""]
    );
    return NextResponse.json({ success: true, bookingId }, { status: 201 });
  } catch (err) {
    console.error("[api bookings local]", err);
    return NextResponse.json(
      { success: false, message: err?.message || "Failed to create booking" },
      { status: 500 }
    );
  }
}
