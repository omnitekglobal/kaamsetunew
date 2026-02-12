import pool from "@/lib/db";
import { NextResponse } from "next/server";

export async function POST(req) {
  try {
    const body = await req.json();

    const {
      name,
      phone,
      email,
      city,
      state,
      pincode,
      language,
      services,
    } = body;

    if (
      !name ||
      !phone ||
      !city ||
      !state ||
      !pincode ||
      !language ||
      !services
    ) {
      return NextResponse.json(
        { message: "All required fields are required" },
        { status: 400 }
      );
    }

    const professionalId = "PR" + Date.now();

    await pool.execute(
      `INSERT INTO professionals
      (professionalId, name, phone, email, city, state, pincode, language, services)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        professionalId,
        name,
        phone,
        email || null,
        city,
        state,
        pincode,
        language,
        services,
      ]
    );

    return NextResponse.json(
      { success: true, professionalId },
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
