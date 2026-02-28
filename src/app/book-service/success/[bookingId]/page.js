import Link from "next/link";
import { getAppUrl } from "@/lib/api";

const getApiUrl = () =>
  process.env.API_URL || process.env.NEXT_PUBLIC_API_URL || "";

export default async function SuccessPage({ params }) {
  const { bookingId } = await params;
  let booking = null;
  const base = getApiUrl();

  if (base) {
    try {
      const res = await fetch(`${base.replace(/\/$/, "")}/api/bookings/${encodeURIComponent(bookingId)}`, {
        cache: "no-store",
      });
      const data = await res.json().catch(() => null);
      if (res.ok && data) {
        // PHP returns { success, message, data: row }; Next may return row directly
        const row = data.data ?? data;
        if (row && (row.bookingId || row.booking_id)) {
          booking = row;
        }
      }
    } catch (e) {
      booking = null;
    }
  }

  if (!booking && bookingId) {
    try {
      const appBase = getAppUrl();
      if (appBase) {
        const res = await fetch(`${appBase.replace(/\/$/, "")}/api/booking/${encodeURIComponent(bookingId)}`, {
          cache: "no-store",
        });
        const data = await res.json().catch(() => null);
        if (res.ok && data && typeof data === "object" && (data.bookingId || data.booking_id)) {
          booking = data;
        }
      }
    } catch (e) {
      booking = null;
    }
  }

  // Normalize keys (API may return bookingId or booking_id, etc.)
  if (booking) {
    booking = {
      ...booking,
      bookingId: booking.bookingId ?? booking.booking_id,
      name: booking.name ?? "",
      phone: booking.phone ?? "",
      email: booking.email ?? "",
      service: booking.service ?? "",
      pincode: booking.pincode ?? "",
      language: booking.language ?? "",
      referral_code: booking.referral_code,
    };
  }

  if (!booking) {
    return (
      <div className="min-h-screen bg-blue-50 flex items-center justify-center px-4">
        <div className="bg-white max-w-2xl w-full rounded-2xl shadow-xl p-10 text-center">
          <p className="text-gray-600">Booking not found or API unavailable.</p>
          <Link href="/" className="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg">
            Back to Home
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-blue-50 flex items-center justify-center px-4">
      <div className="bg-white max-w-2xl w-full rounded-2xl shadow-xl p-10">
        <div className="text-center">
          <div className="text-green-600 text-5xl">✓</div>
          <h1 className="mt-4 text-3xl font-bold text-blue-700">Booking Confirmed!</h1>
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
          {booking.referral_code && (
            <div className="flex justify-between">
              <span>Referral</span>
              <span>{booking.referral_code}</span>
            </div>
          )}
        </div>
        <div className="mt-8 flex gap-4">
          <Link href="/" className="flex-1 bg-blue-600 text-white py-3 rounded-lg text-center">
            Back to Home
          </Link>
          <Link href="/services" className="flex-1 border border-blue-600 text-blue-600 py-3 rounded-lg text-center">
            Explore More Services
          </Link>
        </div>
      </div>
    </div>
  );
}
