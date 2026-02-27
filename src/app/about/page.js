export const metadata = {
  title: "About Us | PinkySreya",
  description: "Learn more about PinkySreya and our mission.",
};

export default function AboutPage() {
  return (
    <section className="max-w-4xl mx-auto px-6 py-16">
      <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
        About Us
      </h1>
      <p className="text-gray-700 leading-relaxed mb-4">
        PinkySreya is a platform dedicated to connecting you with trusted,
        verified professionals for all your home service needs. From
        electricians and plumbers to carpenters and cleaners, we make it easy
        to find reliable help near you.
      </p>
      <p className="text-gray-700 leading-relaxed mb-4">
        Our mission is to bring convenience, transparency, and trust to local
        services. Every professional on our platform is carefully vetted so you
        can book with confidence and focus on what matters most to you.
      </p>
      <p className="text-gray-700 leading-relaxed">
        Whether you need urgent assistance or are planning routine maintenance,
        PinkySreya is here to support you with a smooth, hassle-free experience
        from booking to completion.
      </p>
    </section>
  );
}

