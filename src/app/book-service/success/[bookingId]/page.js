import pool from "@/lib/db";
import Link from "next/link";

export default async function SuccessPage({ params }) {
  const { bookingId } = await params;

  const [rows] = await pool.execute(
    "SELECT * FROM bookings WHERE bookingId = ?",
    [bookingId]
  );

  const booking = rows[0];

  if (!booking) {
    return (
      <div className="p-10 text-center">
        Booking not found
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-blue-50 flex items-center justify-center px-4">
      <div className="bg-white max-w-2xl w-full rounded-2xl shadow-xl p-10">

        <div className="text-center">
          <div className="text-green-600 text-5xl">✓</div>

          <h1 className="mt-4 text-3xl font-bold text-blue-700">
            Booking Confirmed!
          </h1>

          <p className="mt-2 text-gray-600">
            Thank you <strong>{booking.name}</strong>
          </p>
        </div>

        <div className="mt-8 bg-blue-50 rounded-xl p-6 space-y-3 text-sm">

          <div className="flex justify-between">
            <span>Booking ID</span>
            <span className="font-semibold">{booking.bookingId}</span>
          </div>

          <div className="flex justify-between">
            <span>Service</span>
            <span>{booking.service}</span>
          </div>

          <div className="flex justify-between">
            <span>Phone</span>
            <span>{booking.phone}</span>
          </div>

          <div className="flex justify-between">
            <span>Email</span>
            <span>{booking.email || "-"}</span>
          </div>

          <div className="flex justify-between">
            <span>Pincode</span>
            <span>{booking.pincode}</span>
          </div>

          <div className="flex justify-between">
            <span>Language</span>
            <span>{booking.language}</span>
          </div>

        </div>

        <div className="mt-8 flex gap-4">
          <Link
            href="/"
            className="flex-1 bg-blue-600 text-white py-3 rounded-lg text-center"
          >
            Back to Home
          </Link>

          <Link
            href="/services"
            className="flex-1 border border-blue-600 text-blue-600 py-3 rounded-lg text-center"
          >
            Explore More Services
          </Link>
        </div>

      </div>
    </div>
  );
}
