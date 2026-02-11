"use client";
import { useSearchParams } from "next/navigation";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";

export default function BookingPage() {
  const searchParams = useSearchParams();
  const selectedServiceFromURL = searchParams.get("service");
  const router = useRouter();

  const serviceList = [
    "Electrician",
    "Plumber",
    "Carpenter",
    "Driver",
    "Barber",
    "AC Repair",
    "House Cleaning",
    "Painter",
  ];

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

    if (name === "phone") {
      if (!/^\d{0,10}$/.test(value)) return;
    }

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

    setFormData({
      ...formData,
      [name]: value,
    });
  };

const handleSubmit = (e) => {
  e.preventDefault();

  if (formData.phone.length !== 10) {
    alert("Enter valid 10-digit Indian phone number");
    return;
  }

  const finalService =
    formData.service === "Other"
      ? formData.customService
      : formData.service;

  const query = new URLSearchParams({
    name: formData.name,
    phone: formData.phone,
    email: formData.email,
    service: finalService,
    pincode: formData.pincode,
    language: formData.language,
  }).toString();

  router.push(`/book-service/success?${query}`);
};

  return (
    <div className="min-h-screen bg-blue-50 py-12 px-4">
      <div className="max-w-3xl mx-auto bg-white shadow-xl rounded-2xl p-8">

        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-blue-700">
            Book a Service
          </h1>
          <p className="text-gray-500 mt-2">
            Fill the details and we will assign the best partner near you.
          </p>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-6">

          {/* Service Selector */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Select Service *
            </label>
            <select
              name="service"
              value={formData.service}
              onChange={handleChange}
              required
              className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
            >
              <option value="">Choose Service</option>
              {serviceList.map((service) => (
                <option key={service} value={service}>
                  {service}
                </option>
              ))}
              <option value="Other">Other (Type Manually)</option>
            </select>
          </div>

          {/* Custom Service */}
          {showCustomService && (
            <div>
              <input
                type="text"
                name="customService"
                placeholder="Enter your service name"
                value={formData.customService}
                onChange={handleChange}
                required
                className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
              />
            </div>
          )}

          {/* Name + Email */}
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Full Name *
              </label>
              <input
                type="text"
                name="name"
                required
                value={formData.name}
                onChange={handleChange}
                className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Email *
              </label>
              <input
                type="email"
                name="email"
                required
                value={formData.email}
                onChange={handleChange}
                className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
              />
            </div>
          </div>

          {/* Phone + Pincode */}
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Phone Number *
              </label>
              <input
                type="tel"
                name="phone"
                required
                maxLength={10}
                placeholder="10-digit mobile number"
                value={formData.phone}
                onChange={handleChange}
                className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Pincode *
              </label>
              <input
                type="text"
                name="pincode"
                required
                maxLength={6}
                value={formData.pincode}
                onChange={handleChange}
                className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
              />
            </div>
          </div>

          {/* Language */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Preferred Language *
            </label>
            <select
              name="language"
              required
              value={formData.language}
              onChange={handleChange}
              className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none"
            >
              <option value="">Select Language</option>
              {languages.map((lang) => (
                <option key={lang} value={lang}>
                  {lang}
                </option>
              ))}
            </select>
          </div>

          {/* Submit */}
          <button
            type="submit"
            className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition"
          >
            Submit Booking Request
          </button>
        </form>
      </div>
    </div>
  );
}
