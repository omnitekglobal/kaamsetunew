import pool from "@/lib/db";
import { NextResponse } from "next/server";

export async function GET(req, context) {
  try {
    const { professionalId } = await context.params;

    const [rows] = await pool.execute(
      "SELECT * FROM professionals WHERE professionalId = ?",
      [professionalId]
    );

    if (rows.length === 0) {
      return NextResponse.json(
        { message: "Professional not found" },
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
