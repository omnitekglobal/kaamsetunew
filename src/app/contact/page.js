export const metadata = {
  title: "Contact Us | PinkySreya",
  description: "Get in touch with the PinkySreya support team.",
};

export default function ContactPage() {
  return (
    <section className="max-w-4xl mx-auto px-6 py-16">
      <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
        Contact Us
      </h1>
      <p className="text-gray-700 leading-relaxed mb-6">
        Have questions, feedback, or need help with a booking? We&apos;d love
        to hear from you. Reach out using the details below and our team will
        get back to you as soon as possible.
      </p>

      <div className="space-y-3 text-gray-800">
        <p>
          <span className="font-semibold">Email:</span>{" "}
          <a
            href="mailto:support@pinkysreya.com"
            className="text-blue-600 hover:underline"
          >
            support@pinkysreya.com
          </a>
        </p>
        <p>
          <span className="font-semibold">Phone:</span>{" "}
          <a
            href="tel:+919000000000"
            className="text-blue-600 hover:underline"
          >
            +91 90000 00000
          </a>
        </p>
        <p>
          <span className="font-semibold">Location:</span> India
        </p>
      </div>
    </section>
  );
}

