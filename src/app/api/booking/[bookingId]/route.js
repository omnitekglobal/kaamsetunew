import pool from "@/lib/db";
import { NextResponse } from "next/server";

export async function GET(req, context) {
  try {
    // ✅ unwrap params (Next.js 16 fix)
    const { bookingId } = await context.params;

    if (!bookingId) {
      return NextResponse.json(
        { message: "Booking ID missing" },
        { status: 400 }
      );
    }

    const [rows] = await pool.execute(
      "SELECT * FROM bookings WHERE bookingId = ?",
      [bookingId]
    );

    if (rows.length === 0) {
      return NextResponse.json(
        { message: "Booking not found" },
        { status: 404 }
      );
    }

    return NextResponse.json(rows[0]);

  } catch (error) {
    console.error(error);
    return NextResponse.json(
      { message: "Server error" },
      { status: 500 }
    );
  }
}
