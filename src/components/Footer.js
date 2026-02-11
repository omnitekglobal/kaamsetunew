import Link from "next/link";

export default function Footer() {
  return (
    <footer className="bg-blue-700 text-white">
      <div className="max-w-7xl mx-auto px-6 py-14">

        <div className="grid md:grid-cols-4 gap-10">

          {/* Brand */}
          <div>
            <h2 className="text-2xl font-bold">
              Kaam<span className="text-blue-300">Setu</span>
            </h2>
            <p className="mt-4 text-blue-100 text-sm">
              Connecting trusted workers with people who need services.
              Fast, reliable and verified professionals near you.
            </p>
          </div>

          {/* Services */}
          <div>
            <h3 className="font-semibold mb-4">Services</h3>
            <ul className="space-y-2 text-blue-100 text-sm">
              <li><Link href="/services">Electrician</Link></li>
              <li><Link href="/services">Plumber</Link></li>
              <li><Link href="/services">Carpenter</Link></li>
              <li><Link href="/services">Cleaning</Link></li>
            </ul>
          </div>

          {/* Company */}
          <div>
            <h3 className="font-semibold mb-4">Company</h3>
            <ul className="space-y-2 text-blue-100 text-sm">
              <li><Link href="#">About Us</Link></li>
              <li><Link href="#">Privacy Policy</Link></li>
              <li><Link href="#">Terms & Conditions</Link></li>
              <li><Link href="#">Contact</Link></li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="font-semibold mb-4">Contact</h3>
            <p className="text-blue-100 text-sm">
              📍 India <br />
              📧 support@kaamsetu.com <br />
              📞 +91 90000 00000
            </p>
          </div>

        </div>

        {/* Bottom */}
        <div className="border-t border-blue-500 mt-10 pt-6 text-center text-sm text-blue-200">
          © {new Date().getFullYear()} KaamSetu. All rights reserved.
        </div>
      </div>
    </footer>
  );
}
