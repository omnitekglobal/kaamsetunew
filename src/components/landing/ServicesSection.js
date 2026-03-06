"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { getServices, getServiceImageUrl } from "@/lib/api";

const FALLBACK_SERVICES = [
  { id: 1, name: "Electrician" },
  { id: 2, name: "Plumber" },
  { id: 3, name: "Carpenter" },
  { id: 4, name: "Driver" },
  { id: 5, name: "Barber" },
  { id: 6, name: "House Cleaning" },
];

export default function ServicesSection() {
  const [topServices, setTopServices] = useState(FALLBACK_SERVICES);
  const [servicesByCategory, setServicesByCategory] = useState(null);

  useEffect(() => {
    let mounted = true;
    getServices()
      .then((list) => {
        if (!mounted || !list?.length) return;

        const servicesWithMeta = list.map((s) => ({
          id: s.id,
          name: s.name,
          icon: s.icon,
          description: s.description || "",
          category_name: s.category_name || "Other",
          is_popular: s.is_popular || s.is_featured || false,
          bookings_count: s.bookings_count ?? 0,
          sort_order: s.sort_order ?? 0,
        }));

        // Pick top services: prefer popular/booked ones, fall back to first N
        const popularSorted = [...servicesWithMeta].sort((a, b) => {
          const aScore = (a.is_popular ? 1 : 0) + (a.bookings_count || 0);
          const bScore = (b.is_popular ? 1 : 0) + (b.bookings_count || 0);
          if (bScore !== aScore) return bScore - aScore;
          return a.sort_order - b.sort_order;
        });

        const top = popularSorted.slice(0, 8);
        const topIds = new Set(top.map((s) => s.id));

        // Group remaining services category-wise for \"less popular\" sections
        const byCat = {};
        servicesWithMeta.forEach((s) => {
          if (topIds.has(s.id)) return;
          const cat = s.category_name || "Other";
          if (!byCat[cat]) byCat[cat] = [];
          byCat[cat].push(s);
        });

        setTopServices(top);
        setServicesByCategory(Object.keys(byCat).length ? byCat : null);
      })
      .catch(() => {});
    return () => { mounted = false; };
  }, []);

  return (
    <section className="bg-gray-50 py-20">
      <div className="max-w-7xl mx-auto px-6">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900">
            Explore Our Services
          </h2>
          <p className="mt-4 text-gray-600 text-lg">
            Most booked services near you, plus everything else you need.
          </p>
        </div>

        {/* Top / most booked services */}
        <div className="mb-12">
          <h3 className="text-xl font-semibold text-gray-800 mb-5 text-left">
            Top services people book
          </h3>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            {topServices.map((service) => (
              <Link
                key={service.id || service.name}
                href={`/book-service?service=${encodeURIComponent(service.name)}`}
                className="bg-white p-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition duration-200 text-center border border-gray-100"
              >
                <div className="h-14 w-14 mx-auto mb-4 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                  <img
                    src={getServiceImageUrl(service)}
                    alt=""
                    className="h-full w-full object-cover"
                  />
                </div>
                <h3 className="font-medium text-gray-800 text-sm sm:text-base">
                  {service.name}
                </h3>
              </Link>
            ))}
          </div>
        </div>

        {/* Category-wise sections for the rest */}
        {servicesByCategory && (
          <div className="space-y-10">
            {Object.entries(servicesByCategory).map(([category, list]) => (
              <div key={category}>
                <div className="flex items-baseline justify-between mb-4">
                  <h3 className="text-lg md:text-xl font-semibold text-gray-900">
                    {category}
                  </h3>
                  <Link
                    href="/services"
                    className="text-sm text-blue-600 hover:underline"
                  >
                    View more
                  </Link>
                </div>
                <div className="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                  {list.slice(0, 4).map((service) => (
                    <Link
                      key={service.id || service.name}
                      href={`/book-service?service=${encodeURIComponent(service.name)}`}
                      className="bg-white p-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition duration-200 border border-gray-100 text-left"
                    >
                      <div className="flex items-center gap-4">
                        <div className="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                          <img
                            src={getServiceImageUrl(service)}
                            alt=""
                            className="h-full w-full object-cover"
                          />
                        </div>
                        <div>
                          <h4 className="font-semibold text-gray-800 text-sm sm:text-base">
                            {service.name}
                          </h4>
                          {service.description && (
                            <p className="text-xs sm:text-sm text-gray-500 mt-1 line-clamp-2">
                              {service.description}
                            </p>
                          )}
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}

        <div className="text-center mt-12">
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
