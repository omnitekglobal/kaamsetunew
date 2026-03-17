"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X } from "lucide-react";
import Image from "next/image";

export default function Navbar() {
  const [open, setOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 bg-white shadow-sm border-b border-blue-50">
      <div className="max-w-7xl mx-auto px-6">
        <div className="flex items-center justify-between h-16">

          {/* Logo */}
<Link href="/" className="flex items-center">
  <Image 
    src="/PinkySreyaTrans.svg"          // Path to your image in the /public folder
    alt="PinkySreya Logo" 
    width={150}              // Adjust width as needed
    height={40}              // Adjust height as needed
    priority                 // Ensures the logo loads instantly
    className="object-contain" 
  />
</Link>

          {/* Desktop Menu */}
          <nav className="hidden md:flex items-center gap-8 text-gray-700 font-medium">
            <Link href="/services" className="hover:text-blue-600 transition">
              Services
            </Link>

            <Link href="/professional/register" className="hover:text-blue-600 transition">
              Become Professional
            </Link>

            <Link
              href="/book-service"
              className="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition shadow-sm"
            >
              Request Service
            </Link>
          </nav>

          {/* Mobile Toggle */}
          <button
            onClick={() => setOpen(!open)}
            className="md:hidden text-gray-700"
          >
            {open ? <X size={24} /> : <Menu size={24} />}
          </button>
        </div>
      </div>

      {/* Mobile Menu */}
      {open && (
        <div className="md:hidden bg-white border-t border-blue-50 shadow-sm">
          <div className="flex flex-col px-6 py-4 gap-4 font-medium text-gray-700">
            <Link href="/services" onClick={() => setOpen(false)}>
              Services
            </Link>
            <Link href="/professional/register" onClick={() => setOpen(false)}>
              Become Professional
            </Link>
            <Link
              href="/book-service"
              onClick={() => setOpen(false)}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg text-center"
            >
              Request Service
            </Link>
          </div>
        </div>
      )}
    </header>
  );
}
