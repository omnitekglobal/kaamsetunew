import Image from "next/image";
import Link from "next/link";

export default function Footer() {
  return (
    <footer className="bg-blue-700 text-white">
      <div className="max-w-7xl mx-auto px-6 py-14">
        <div className="grid md:grid-cols-4 gap-10">
          {/* Brand */}
          <div>
            <Link href="/" className="flex items-center">
              <Image
                src="/PinkySreyaTransRed.png" // Path to your image in the /public folder
                alt="PinkySreya Logo"
                width={150} // Adjust width as needed
                height={40} // Adjust height as needed
                priority // Ensures the logo loads instantly
                className="object-contain"
              />
            </Link>
            <p className="mt-4 text-blue-100 text-sm">
              Connecting trusted workers with people who need services. Fast,
              reliable and verified professionals near you.
            </p>
          </div>

          {/* Services */}
          <div>
            <h3 className="font-semibold mb-4">Services</h3>
            <ul className="space-y-2 text-blue-100 text-sm">
              <li>
                <Link href="/services">Electrician</Link>
              </li>
              <li>
                <Link href="/services">Plumber</Link>
              </li>
              <li>
                <Link href="/services">Carpenter</Link>
              </li>
              <li>
                <Link href="/services">Cleaning</Link>
              </li>
            </ul>
          </div>

          {/* Company */}
          <div>
            <h3 className="font-semibold mb-4">Company</h3>
            <ul className="space-y-2 text-blue-100 text-sm">
              <li>
                <Link href="/about">About Us</Link>
              </li>
              <li>
                <Link href="/privacy-policy">Privacy Policy</Link>
              </li>
              <li>
                <Link href="/terms-and-conditions">Terms &amp; Conditions</Link>
              </li>
              <li>
                <Link href="/contact">Contact</Link>
              </li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="font-semibold mb-4">Contact</h3>
            <p className="text-blue-100 text-sm">
              📍 India <br />
              📧 support@pinkysreya.com <br />
              📞 +91 90000 00000
            </p>
          </div>
        </div>

        {/* Bottom */}
        <div className="border-t border-blue-500 mt-10 pt-6 text-center text-sm text-blue-200">
          © {new Date().getFullYear()} PinkySreya. All rights reserved.
        </div>
      </div>
    </footer>
  );
}
