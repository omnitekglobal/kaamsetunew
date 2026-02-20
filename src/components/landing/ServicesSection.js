"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { getServices } from "@/lib/api";

const FALLBACK_SERVICES = [
  { id: 1, name: "Electrician", icon: "⚡" },
  { id: 2, name: "Plumber", icon: "🚰" },
  { id: 3, name: "Carpenter", icon: "🪚" },
  { id: 4, name: "Driver", icon: "🚗" },
  { id: 5, name: "Barber", icon: "✂️" },
  { id: 6, name: "House Cleaning", icon: "🧹" },
];

const ICONS = ["⚡", "🚰", "🪚", "🚗", "✂️", "🧹"];

export default function ServicesSection() {
  const [services, setServices] = useState(FALLBACK_SERVICES);

  useEffect(() => {
    let mounted = true;
    getServices()
      .then((list) => {
        if (!mounted || !list?.length) return;
        setServices(
          list.slice(0, 8).map((s, i) => ({
            id: s.id || i,
            name: s.name,
            icon: ICONS[i % ICONS.length],
          }))
        );
      })
      .catch(() => {});
    return () => { mounted = false; };
  }, []);

  return (
    <section className="bg-gray-50 py-20">
      <div className="max-w-7xl mx-auto px-6">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900">Explore Our Services</h2>
          <p className="mt-4 text-gray-600 text-lg">Choose from trusted professionals near you</p>
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
          {services.map((service) => (
            <Link
              key={service.id}
              href={`/book-service?service=${encodeURIComponent(service.name)}`}
              className="bg-white p-6 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition duration-200 text-center border border-gray-100"
            >
              <div className="text-4xl mb-4">{service.icon}</div>
              <h3 className="font-medium text-gray-800">{service.name}</h3>
            </Link>
          ))}
        </div>
        <div className="text-center mt-10">
          <Link
            href="/services"
            className="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition"
          >
            View All Services
          </Link>
        </div>
      </div>
    </section>
  );
}
