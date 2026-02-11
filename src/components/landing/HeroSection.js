import Link from "next/link";
export default function HeroSection() {
  return (
    <section className="bg-white">
      <div className="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-12 items-center">
        {/* Left Content */}
        <div>
          <h1 className="text-4xl md:text-5xl font-bold text-gray-900 leading-tight">
            Find Trusted Workers <br />
            <span className="text-blue-600">Near You Instantly</span>
          </h1>
          <p className="mt-6 text-lg text-gray-600">
            Electrician, Plumber, Barber, Driver and more. KaamSetu connects you
            with verified professionals in minutes.
          </p>
          <div className="mt-8 flex flex-col sm:flex-row gap-4">
            <Link
              href="/book-service"
              className="bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-medium hover:bg-blue-700 transition text-center"
            >
              Post a Service Request
            </Link>

            <Link
              href="/partner/register"
              className="border border-blue-600 text-blue-600 px-6 py-3 rounded-lg text-lg font-medium hover:bg-blue-50 transition text-center"
            >
              Become a Partner
            </Link>
          </div>
        </div>

        {/* Right Image */}
        <div className="hidden md:block">
          <img
            src="https://images.unsplash.com/photo-1581578731548-c64695cc6952"
            alt="Worker"
            className="rounded-xl shadow-lg"
          />
        </div>
      </div>
    </section>
  );
}
