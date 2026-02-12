import pool from "@/lib/db";
import { NextResponse } from "next/server";

export async function POST(req) {
  try {
    const body = await req.json();

    const { name, email, phone, service, pincode, language } = body;

    if (!name || !phone || !service || !pincode || !language) {
      return NextResponse.json(
        { message: "All required fields are required" },
        { status: 400 }
      );
    }

    // Generate unique booking ID
    const bookingId = "KS" + Date.now();

    await pool.execute(
      `INSERT INTO bookings
       (bookingId, name, email, phone, service, pincode, language)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [bookingId, name, email || null, phone, service, pincode, language]
    );

    return NextResponse.json(
      { success: true, bookingId },
      { status: 201 }
    );

  } catch (error) {
    console.error(error);

    return NextResponse.json(
      { message: "Server error" },
      { status: 500 }
    );
  }
}
