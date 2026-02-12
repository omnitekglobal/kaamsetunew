"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function ProfessionalRegisterPage() {
  const router = useRouter();

  const servicesData = {
    Home: ["Electrician", "Plumber", "Carpenter", "Painter"],
    Personal: ["Barber", "Driver"],
    Appliance: ["AC Repair", "Washing Machine Repair"],
    Cleaning: ["House Cleaning", "Bathroom Cleaning"],
  };

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

  const indianStatesUT = [
    "Andhra Pradesh",
    "Arunachal Pradesh",
    "Assam",
    "Bihar",
    "Chhattisgarh",
    "Goa",
    "Gujarat",
    "Haryana",
    "Himachal Pradesh",
    "Jharkhand",
    "Karnataka",
    "Kerala",
    "Madhya Pradesh",
    "Maharashtra",
    "Manipur",
    "Meghalaya",
    "Mizoram",
    "Nagaland",
    "Odisha",
    "Punjab",
    "Rajasthan",
    "Sikkim",
    "Tamil Nadu",
    "Telangana",
    "Tripura",
    "Uttar Pradesh",
    "Uttarakhand",
    "West Bengal",
    "Andaman and Nicobar Islands",
    "Chandigarh",
    "Dadra and Nagar Haveli and Daman and Diu",
    "Delhi",
    "Jammu and Kashmir",
    "Ladakh",
    "Lakshadweep",
    "Puducherry",
  ];

  const [formData, setFormData] = useState({
    photo: null,
    name: "",
    phone: "",
    email: "",
    city: "",
    state: "",
    pincode: "",
    language: "",
    services: [],
  });

  const [preview, setPreview] = useState(null);
  const [errors, setErrors] = useState({});

  // Handle Input Change
  const handleChange = (e) => {
    const { name, value } = e.target;

    if (name === "phone" && !/^\d{0,10}$/.test(value)) return;
    if (name === "pincode" && !/^\d{0,6}$/.test(value)) return;

    setFormData({ ...formData, [name]: value });
  };

  // Handle Photo Upload (optional)
  const handlePhoto = (e) => {
    const file = e.target.files[0];
    if (file) {
      setFormData({ ...formData, photo: file });
      setPreview(URL.createObjectURL(file));
    }
  };

  // Toggle Services
  const toggleService = (service) => {
    const updated = formData.services.includes(service)
      ? formData.services.filter((s) => s !== service)
      : [...formData.services, service];

    setFormData({ ...formData, services: updated });
  };

  // Validation
  const validate = () => {
    let newErrors = {};

    if (!formData.name) newErrors.name = "Name is required";
    if (!/^[6-9]\d{9}$/.test(formData.phone))
      newErrors.phone = "Valid 10-digit mobile required";
    if (!formData.city) newErrors.city = "City is required";
    if (!formData.state) newErrors.state = "State is required";
    if (formData.pincode.length !== 6)
      newErrors.pincode = "Valid 6-digit Pincode required";
    if (!formData.language) newErrors.language = "Language is required";
    if (formData.services.length === 0)
      newErrors.services = "Select at least one service";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

const handleSubmit = async (e) => {
  e.preventDefault();
  if (!validate()) return;

  try {
    const res = await fetch("/api/professional/register", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        ...formData,
        services: formData.services.join(", "),
      }),
    });

    const data = await res.json();

    if (!res.ok) {
      alert(data.message || "Something went wrong");
      return;
    }

    router.push(`/professional/register/success/${data.professionalId}`);

  } catch (err) {
    console.error(err);
    alert("Server error");
  }
};


  return (
    <div className="min-h-screen bg-blue-50 py-12 px-4">
      <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-8">
        <h1 className="text-3xl font-bold text-blue-700 text-center mb-8">
          Become a Professional
        </h1>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Photo Upload (optional) */}
          <div className="flex flex-col items-center">
            <label className="cursor-pointer">
              <div className="w-28 h-28 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden border-2 border-blue-200">
                {preview ? (
                  <img
                    src={preview}
                    alt="Preview"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <span className="text-blue-600 text-sm">Upload Photo</span>
                )}
              </div>
              <input type="file" accept="image/*" hidden onChange={handlePhoto} />
            </label>
          </div>

          {/* Basic Details */}
          <div className="grid md:grid-cols-2 gap-4">
            <Input label="Full Name *" name="name" value={formData.name} onChange={handleChange} error={errors.name} />
            <Input label="Phone Number *" name="phone" value={formData.phone} onChange={handleChange} error={errors.phone} />
            <Input label="Email (Optional)" name="email" value={formData.email} onChange={handleChange} />
            <Input label="City/Town *" name="city" value={formData.city} onChange={handleChange} error={errors.city} />
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            {/* State Dropdown */}
            <div>
              <label className="block font-medium mb-2">State / UT *</label>
              <select
                name="state"
                value={formData.state}
                onChange={handleChange}
                className={`w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-500 ${
                  errors.state ? "border-red-500" : "border-gray-300"
                }`}
              >
                <option value="">Select State / UT</option>
                {indianStatesUT.map((state) => (
                  <option key={state} value={state}>
                    {state}
                  </option>
                ))}
              </select>
              {errors.state && <p className="text-red-500 text-sm mt-1">{errors.state}</p>}
            </div>

            <Input label="Pincode *" name="pincode" value={formData.pincode} onChange={handleChange} error={errors.pincode} />
          </div>

          {/* Language */}
          <div>
            <label className="block font-medium mb-2">Preferred Language *</label>
            <select
              name="language"
              value={formData.language}
              onChange={handleChange}
              className={`w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-500 ${
                errors.language ? "border-red-500" : "border-gray-300"
              }`}
            >
              <option value="">Select Language</option>
              {languages.map((lang) => (
                <option key={lang} value={lang}>
                  {lang}
                </option>
              ))}
            </select>
            {errors.language && <p className="text-red-500 text-sm mt-1">{errors.language}</p>}
          </div>

          {/* Services */}
          <div>
            <label className="block font-medium mb-3">Select Services *</label>

            {Object.entries(servicesData).map(([category, services]) => (
              <div key={category} className="mb-4">
                <h3 className="text-blue-700 font-semibold mb-2">{category}</h3>
                <div className="flex flex-wrap gap-3">
                  {services.map((service) => (
                    <button
                      type="button"
                      key={service}
                      onClick={() => toggleService(service)}
                      className={`px-4 py-2 rounded-full text-sm border transition ${
                        formData.services.includes(service)
                          ? "bg-blue-600 text-white border-blue-600"
                          : "bg-blue-50 text-blue-700 border-blue-200"
                      }`}
                    >
                      {service}
                    </button>
                  ))}
                </div>
              </div>
            ))}

            {errors.services && (
              <p className="text-red-500 text-sm mt-2">{errors.services}</p>
            )}
          </div>

          <button
            type="submit"
            className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition"
          >
            Register as Professional
          </button>
        </form>
      </div>
    </div>
  );
}

/* Reusable Input Component */
function Input({ label, name, value, onChange, error }) {
  return (
    <div>
      <label className="block font-medium mb-2">{label}</label>
      <input
        name={name}
        value={value}
        onChange={onChange}
        className={`w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-500 ${
          error ? "border-red-500" : "border-gray-300"
        }`}
      />
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
}