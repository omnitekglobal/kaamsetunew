export const metadata = {
  title: "Terms & Conditions | PinkySreya",
  description: "Read the terms and conditions for using PinkySreya.",
};

export default function TermsPage() {
  return (
    <section className="max-w-4xl mx-auto px-6 py-16">
      <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
        Terms &amp; Conditions
      </h1>
      <p className="text-gray-700 leading-relaxed mb-4">
        By accessing or using PinkySreya, you agree to be bound by these Terms
        &amp; Conditions. Please read them carefully before using our platform
        or booking any services.
      </p>
      <p className="text-gray-700 leading-relaxed mb-4">
        PinkySreya connects you with independent service professionals. While we
        take steps to verify and onboard professionals, the actual services are
        provided by them directly. You agree to use the platform responsibly and
        comply with all applicable laws.
      </p>
      <p className="text-gray-700 leading-relaxed">
        We may update these Terms &amp; Conditions from time to time. Continued
        use of the platform after changes are posted constitutes your acceptance
        of the updated terms.
      </p>
    </section>
  );
}

