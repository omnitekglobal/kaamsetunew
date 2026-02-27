export const metadata = {
  title: "Privacy Policy | PinkySreya",
  description: "Understand how PinkySreya collects and uses your data.",
};

export default function PrivacyPolicyPage() {
  return (
    <section className="max-w-4xl mx-auto px-6 py-16">
      <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
        Privacy Policy
      </h1>
      <p className="text-gray-700 leading-relaxed mb-4">
        At PinkySreya, we respect your privacy and are committed to protecting
        your personal information. This Privacy Policy explains how we collect,
        use, and safeguard your data when you use our platform.
      </p>
      <p className="text-gray-700 leading-relaxed mb-4">
        We collect only the information necessary to provide and improve our
        services, such as your contact details, booking information, and usage
        data. We do not sell your personal information to third parties.
      </p>
      <p className="text-gray-700 leading-relaxed">
        By using PinkySreya, you agree to the collection and use of information
        in accordance with this policy. If you have any questions about how
        your data is handled, please contact us at{" "}
        <span className="font-medium">support@pinkysreya.com</span>.
      </p>
    </section>
  );
}

