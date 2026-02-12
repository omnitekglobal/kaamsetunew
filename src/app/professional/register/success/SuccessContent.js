"use client";

import { useSearchParams } from "next/navigation";
import Link from "next/link";

export default function SuccessContent() {
  const searchParams = useSearchParams();

  const professionalId = searchParams.get("professionalId");
  const name = searchParams.get("name");
  const phone = searchParams.get("phone");
  const email = searchParams.get("email");
  const address = searchParams.get("address");
  const city = searchParams.get("city");
  const state = searchParams.get("state");
  const pincode = searchParams.get("pincode");
  const aadhaar = searchParams.get("aadhaar");
  const language = searchParams.get("language");
  const services = searchParams.get("services");

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 flex items-center justify-center px-4 py-12">
      <div className="max-w-3xl w-full bg-white rounded-2xl shadow-2xl p-10">

        <div className="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center">
          <span className="text-green-600 text-4xl">✓</span>
        </div>

        <h1 className="mt-6 text-3xl font-bold text-center text-purple-700">
          Professional Registration Successful!
        </h1>

        <p className="mt-3 text-center text-gray-600">
          Welcome <span className="font-semibold">{name}</span> 🎉
        </p>

        <div className="mt-4 text-center">
          <span className="text-sm text-gray-500">Professional ID</span>
          <p className="text-lg font-bold text-purple-700">{professionalId}</p>
        </div>

        <div className="mt-8 bg-purple-50 border border-purple-100 rounded-xl p-6 space-y-3 text-sm">

          <Detail label="Phone" value={phone} />
          <Detail label="Email" value={email || "—"} />
          <Detail label="Address" value={address} />
          <Detail label="City" value={city} />
          <Detail label="State" value={state} />
          <Detail label="Pincode" value={pincode} />
          <Detail label="Aadhaar" value={aadhaar} />
          <Detail label="Language" value={language} />
          <Detail label="Selected Services" value={services} />

        </div>

        <div className="mt-6 text-center text-sm text-gray-500">
          📞 Please keep your phone active for verification call.
        </div>

        <div className="mt-8 flex flex-col sm:flex-row gap-4">
          <Link
            href="/"
            className="flex-1 bg-purple-600 text-white py-3 rounded-lg text-center font-semibold hover:bg-purple-700 transition"
          >
            Back to Home
          </Link>

          <Link
            href="/services"
            className="flex-1 border border-purple-600 text-purple-600 py-3 rounded-lg text-center font-semibold hover:bg-purple-50 transition"
          >
            Explore Services
          </Link>
        </div>
      </div>
    </div>
  );
}

function Detail({ label, value }) {
  return (
    <div className="flex justify-between border-b border-purple-100 pb-2">
      <span className="text-gray-600">{label}</span>
      <span className="font-semibold text-purple-700 text-right max-w-[60%] break-words">
        {value || "—"}
      </span>
    </div>
  );
}
