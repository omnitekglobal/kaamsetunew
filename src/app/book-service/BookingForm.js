"use client";

import { useSearchParams, useRouter } from "next/navigation";
import { useState, useEffect } from "react";
import { getServices, createBooking } from "@/lib/api";

const FALLBACK_SERVICE_NAMES = [
  "Electrician", "Plumber", "Carpenter", "Driver", "Barber", "AC Repair", "House Cleaning", "Painter",
];

export default function BookingForm() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const selectedServiceFromURL = searchParams.get("service");
  const [serviceList, setServiceList] = useState(FALLBACK_SERVICE_NAMES);
  useEffect(() => {
    getServices()
      .then((list) => {
        if (list?.length) setServiceList([...new Set(list.map((s) => s.name).filter(Boolean)), "Other"]);
      })
      .catch(() => {});
  }, []);

  const languages = [
    "Hindi",
    "English",
    "Marathi",
    "Gujarati",
    "Tamil",
    "Telugu",
    "Kannada",
    "Punjabi",
  ];

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    pincode: "",
    service: "",
    language: "",
    customService: "",
  });

  const [showCustomService, setShowCustomService] = useState(false);

  useEffect(() => {
    if (selectedServiceFromURL) {
      setFormData((prev) => ({
        ...prev,
        service: selectedServiceFromURL,
      }));
    }
  }, [selectedServiceFromURL]);

  const handleChange = (e) => {
    const { name, value } = e.target;

    if (name === "phone" && !/^\d{0,10}$/.test(value)) return;

    if (name === "service") {
      if (value === "Other") {
        setShowCustomService(true);
      } else {
        setShowCustomService(false);
        setFormData((prev) => ({
          ...prev,
          customService: "",
        }));
      }
    }

    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!/^[6-9]\d{9}$/.test(formData.phone)) {
      alert("Enter valid 10-digit Indian mobile number");
      return;
    }
    const service = formData.service === "Other" ? formData.customService : formData.service;
    if (!service?.trim()) {
      alert("Please select or enter a service");
      return;
    }
    try {
      const data = await createBooking({
        name: formData.name,
        email: formData.email || undefined,
        phone: formData.phone,
        pincode: formData.pincode,
        language: formData.language,
        service: service.trim(),
      });
      router.push(`/book-service/success/${data.bookingId}`);
    } catch (err) {
      console.log(err);
      alert(err.message || "Something went wrong");
    }
  };


  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-100 via-white to-indigo-100 flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-3xl bg-white/80 backdrop-blur-lg shadow-2xl rounded-3xl p-10 border border-white/40">
        {/* Header */}
        <div className="text-center mb-10">
          <h1 className="text-4xl font-extrabold text-gray-800">
            Book a Service
          </h1>
          <p className="text-gray-500 mt-3">
            Fast • Reliable • Verified Professionals Near You
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Service */}
          <div>
            <label className="block text-sm font-semibold text-gray-600 mb-2">
              Select Service
            </label>
            <select
              name="service"
              value={formData.service}
              onChange={handleChange}
              required
              className="w-full border border-gray-200 rounded-xl p-3 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            >
              <option value="">Choose Service</option>
              {serviceList.filter((s) => s !== "Other").map((service) => (
                <option key={service} value={service}>
                  {service}
                </option>
              ))}
              <option value="Other">Other</option>
            </select>
          </div>

          {showCustomService && (
            <input
              type="text"
              name="customService"
              placeholder="Enter your service name"
              value={formData.customService}
              onChange={handleChange}
              required
              className="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 transition"
            />
          )}

          {/* Name + Email */}
          <div className="grid md:grid-cols-2 gap-5">
            <input
              type="text"
              name="name"
              placeholder="Full Name"
              required
              value={formData.name}
              onChange={handleChange}
              className="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 transition"
            />

            <input
              type="email"
              name="email"
              placeholder="Email Address (Optional)"
              value={formData.email}
              onChange={handleChange}
              className="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 transition"
            />
          </div>

          {/* Phone + Pincode */}
          <div className="grid md:grid-cols-2 gap-5">
            <input
              type="tel"
              name="phone"
              placeholder="10-digit Mobile Number"
              maxLength={10}
              required
              value={formData.phone}
              onChange={handleChange}
              className="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 transition"
            />

            <input
              type="text"
              name="pincode"
              placeholder="Pincode"
              maxLength={6}
              required
              value={formData.pincode}
              onChange={handleChange}
              className="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 transition"
            />
          </div>

          {/* Language */}
          <div>
            <label className="block text-sm font-semibold text-gray-600 mb-2">
              Preferred Language
            </label>
            <select
              name="language"
              required
              value={formData.language}
              onChange={handleChange}
              className="w-full border border-gray-200 rounded-xl p-3 bg-white focus:ring-2 focus:ring-blue-500 transition"
            >
              <option value="">Select Language</option>
              {languages.map((lang) => (
                <option key={lang} value={lang}>
                  {lang}
                </option>
              ))}
            </select>
          </div>

          {/* Button */}
          <button
            type="submit"
            className="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold text-lg shadow-lg hover:scale-[1.02] hover:shadow-xl transition-all duration-300"
          >
            Submit Booking Request
          </button>
        </form>
      </div>
    </div>
  );
}