"use client";
import { useSearchParams } from "next/navigation";
import Link from "next/link";

export default function BookingSuccessPage() {
  const searchParams = useSearchParams();

  const name = searchParams.get("name");
  const phone = searchParams.get("phone");
  const email = searchParams.get("email");
  const service = searchParams.get("service");
  const pincode = searchParams.get("pincode");
  const language = searchParams.get("language");

  return (
    <div className="min-h-screen bg-blue-50 flex items-center justify-center px-4">
      <div className="bg-white max-w-2xl w-full rounded-2xl shadow-xl p-10 relative overflow-hidden">

        {/* Decorative Background */}
        <div className="absolute -top-20 -right-20 w-48 h-48 bg-blue-100 rounded-full"></div>

        {/* Success Icon */}
        <div className="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center">
          <span className="text-green-600 text-4xl">✓</span>
        </div>

        {/* Heading */}
        <h1 className="mt-6 text-3xl font-bold text-center text-blue-700">
          Booking Confirmed!
        </h1>

        <p className="mt-3 text-center text-gray-600">
          Thank you <span className="font-semibold">{name} </span>   
          Our team will contact you shortly.
        </p>

        {/* Booking Details */}
        <div className="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-6 space-y-3 text-sm">

          <div className="flex justify-between">
            <span className="text-gray-600">Service</span>
            <span className="font-semibold text-blue-700">{service}</span>
          </div>

          <div className="flex justify-between">
            <span className="text-gray-600">Phone</span>
            <span>{phone}</span>
          </div>

          <div className="flex justify-between">
            <span className="text-gray-600">Email</span>
            <span>{email}</span>
          </div>

          <div className="flex justify-between">
            <span className="text-gray-600">Pincode</span>
            <span>{pincode}</span>
          </div>

          <div className="flex justify-between">
            <span className="text-gray-600">Preferred Language</span>
            <span>{language}</span>
          </div>

        </div>

        {/* Info */}
        <div className="mt-6 text-center text-sm text-gray-500">
          📞 Please keep your phone available for confirmation.
        </div>

        {/* Buttons */}
        <div className="mt-8 flex flex-col sm:flex-row gap-4">
          <Link
            href="/"
            className="flex-1 bg-blue-600 text-white py-3 rounded-lg text-center font-semibold hover:bg-blue-700 transition"
          >
            Back to Home
          </Link>

          <Link
            href="/services"
            className="flex-1 border border-blue-600 text-blue-600 py-3 rounded-lg text-center font-semibold hover:bg-blue-50 transition"
          >
            Explore More Services
          </Link>
        </div>
      </div>
    </div>
  );
}
