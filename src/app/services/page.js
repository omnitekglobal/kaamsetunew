"use client";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { getCategories, getServices } from "@/lib/api";

const FALLBACK_SERVICES = {
  Home: [
    { name: "Electrician", desc: "Wiring, fittings, repair" },
    { name: "Plumber", desc: "Leakage, fittings, bathroom work" },
    { name: "Carpenter", desc: "Furniture & wood work" },
    { name: "Painter", desc: "Home & office painting" },
  ],
  Personal: [
    { name: "Barber", desc: "Home haircut service" },
    { name: "Driver", desc: "Personal & commercial driver" },
  ],
  Appliance: [
    { name: "AC Repair", desc: "Installation & servicing" },
    { name: "Washing Machine", desc: "Repair & maintenance" },
  ],
  Cleaning: [
    { name: "House Cleaning", desc: "Deep cleaning services" },
    { name: "Bathroom Cleaning", desc: "Sanitization service" },
  ],
};

export default function ServicesPage() {
  const router = useRouter();
  const [servicesByCategory, setServicesByCategory] = useState(FALLBACK_SERVICES);
  const [categories, setCategories] = useState(Object.keys(FALLBACK_SERVICES));
  const [activeCategory, setActiveCategory] = useState("");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    async function load() {
      try {
        const [cats, services] = await Promise.all([getCategories(), getServices()]);
        if (!mounted) return;
        if (cats?.length) {
          setCategories(cats.map((c) => c.name));
          if (!activeCategory) setActiveCategory(cats[0]?.name || "");
        }
        if (services?.length) {
          const byCat = {};
          services.forEach((s) => {
            const cat = s.category_name || "Other";
            if (!byCat[cat]) byCat[cat] = [];
            byCat[cat].push({
              name: s.name,
              desc: s.description || "",
            });
          });
          if (Object.keys(byCat).length) {
            setServicesByCategory(byCat);
            if (!activeCategory && categories.length === 0) setActiveCategory(Object.keys(byCat)[0] || "");
          }
        }
      } catch (e) {
        if (mounted) setCategories(Object.keys(FALLBACK_SERVICES));
        if (mounted && !activeCategory) setActiveCategory(Object.keys(FALLBACK_SERVICES)[0] || "");
      } finally {
        if (mounted) setLoading(false);
      }
    }
    load();
    return () => { mounted = false; };
  }, []);

  useEffect(() => {
    if (categories.length && !activeCategory) setActiveCategory(categories[0]);
  }, [categories, activeCategory]);

  const currentCategory = activeCategory || categories[0];
  const list = servicesByCategory[currentCategory] || [];
  const filteredServices = list.filter((service) =>
    service.name.toLowerCase().includes(search.toLowerCase())
  );

  const handleBook = (serviceName) => {
    router.push(`/book-service?service=${encodeURIComponent(serviceName)}`);
  };

  return (
    <div className="min-h-screen bg-blue-50">
      <div className="relative bg-gradient-to-br from-blue-700 via-blue-600 to-blue-500 text-white overflow-hidden">
        <div className="absolute -top-20 -right-20 w-72 h-72 bg-blue-400 opacity-30 rounded-full blur-3xl"></div>
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-blue-300 opacity-20 rounded-full blur-3xl"></div>
        <div className="max-w-7xl mx-auto px-6 py-16 relative z-10">
          <h1 className="text-4xl md:text-5xl font-bold leading-tight">Explore Services</h1>
          <p className="mt-4 text-blue-100 text-lg md:text-xl max-w-2xl">
            Book trusted professionals near you. Fast, reliable and verified.
          </p>
          <div className="mt-10 max-w-2xl">
            <div className="flex items-center bg-white/10 backdrop-blur-lg border border-white/20 rounded-full px-6 py-4 shadow-2xl">
              <input
                type="text"
                placeholder="Search Electrician, Plumber..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="flex-1 bg-transparent text-white placeholder-blue-200 outline-none text-lg"
              />
              <button className="bg-white text-blue-600 px-5 py-2 rounded-full font-semibold hover:bg-blue-50 transition">
                Search
              </button>
            </div>
          </div>
        </div>
        <div className="absolute bottom-0 left-0 w-full overflow-hidden leading-none">
          <svg className="relative block w-full h-16" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path fill="#ffffff" fillOpacity="1" d="M0,192L80,186.7C160,181,320,171,480,165.3C640,160,800,160,960,170.7C1120,181,1280,203,1360,213.3L1440,224V320H0Z"></path>
          </svg>
        </div>
      </div>

      <div className="bg-white shadow-md sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4">
          <div className="flex gap-4 overflow-x-auto py-4 scrollbar-hide">
            {categories.map((category) => (
              <button
                key={category}
                onClick={() => { setActiveCategory(category); setSearch(""); }}
                className={`px-6 py-2 rounded-full text-sm font-semibold transition whitespace-nowrap ${
                  activeCategory === category ? "bg-blue-600 text-white shadow-md" : "bg-blue-50 text-blue-700 hover:bg-blue-100"
                }`}
              >
                {category}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-10">
        <h2 className="text-xl font-semibold text-gray-700 mb-6">{currentCategory} Services</h2>
        {loading ? (
          <p className="text-gray-500">Loading...</p>
        ) : filteredServices.length === 0 ? (
          <p className="text-gray-500">No services found.</p>
        ) : (
          <div className="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {filteredServices.map((service) => (
              <div key={service.name} className="bg-white p-6 rounded-xl shadow-sm hover:shadow-lg transition transform hover:-translate-y-1">
                <div className="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold text-lg">
                  {service.name.charAt(0)}
                </div>
                <h3 className="mt-4 text-lg font-semibold text-gray-800">{service.name}</h3>
                <p className="text-sm text-gray-500 mt-1">{service.desc}</p>
                <button
                  onClick={() => handleBook(service.name)}
                  className="mt-4 w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition text-sm"
                >
                  Book Now
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
