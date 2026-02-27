"use client";

import { useState } from "react";
import Link from "next/link";
import { requestProfessional } from "@/lib/api";
import {
  Smartphone,
  ShieldCheck,
  Clock,
  Wallet,
  UserPlus,
  FileCheck,
  Bell,
  TrendingUp,
} from "lucide-react";

export default function ProfessionalRegisterPage() {
  const [phone, setPhone] = useState("");
  const [referralCode, setReferralCode] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handlePhoneChange = (e) => {
    const value = e.target.value.replace(/\D/g, "").slice(0, 10);
    setPhone(value);
    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    if (!/^[6-9]\d{9}$/.test(phone)) {
      setError("Enter a valid 10-digit mobile number");
      return;
    }

    setLoading(true);
    try {
      await requestProfessional({
        phone,
        referral_code: referralCode || undefined,
      });
      setSubmitted(true);
    } catch (err) {
      setError(err.message || "Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  if (submitted) {
    return (
      <div className="min-h-screen bg-blue-50 flex items-center justify-center px-4 py-12">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
          <div className="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
            <svg className="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">Thank you!</h2>
          <p className="text-gray-600 text-sm">
            We&apos;ve received your details. Our team will get in touch with you shortly.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-blue-50">
      {/* Hero */}
      <section id="register-form" className="max-w-6xl mx-auto px-4 py-12 sm:py-16 lg:py-20">
        <div className="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
          <div>
            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-blue-700 mb-4 leading-tight">
              Join as a service professional
            </h1>
            <p className="text-gray-600 text-lg mb-2">
              Start earning with PinkySreya. Connect with customers who need your skills—electricians, plumbers, carpenters, cleaners and more.
            </p>
            <p className="text-gray-500 text-sm sm:text-base">
              Enter your number to get started. We&apos;ll guide you through the rest.
            </p>
          </div>

          <div className="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
            <form onSubmit={handleSubmit} className="space-y-5">
              <div>
                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1.5">
                  Mobile number
                </label>
                <input
                  id="phone"
                  type="tel"
                  inputMode="numeric"
                  placeholder="10-digit mobile number"
                  value={phone}
                  onChange={handlePhoneChange}
                  maxLength={10}
                  className={`w-full border rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition ${
                    error ? "border-red-500" : "border-gray-300"
                  }`}
                  autoComplete="tel"
                />
                {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
              </div>
              <div>
                <label htmlFor="referralCode" className="block text-sm font-medium text-gray-700 mb-1.5">
                  Referral code <span className="text-gray-400 font-normal">(optional)</span>
                </label>
                <input
                  id="referralCode"
                  type="text"
                  placeholder="Have a referral code?"
                  value={referralCode}
                  onChange={(e) => setReferralCode(e.target.value.trim())}
                  className="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                />
              </div>
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold text-base hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition shadow-sm disabled:opacity-70 disabled:cursor-not-allowed"
              >
                {loading ? "Submitting…" : "Get started"}
              </button>
            </form>
            <p className="text-gray-500 text-xs mt-4">
              By continuing, you agree to our{" "}
              <Link href="/terms-and-conditions" className="text-blue-600 hover:underline">Terms &amp; Conditions</Link>
              {" "}and{" "}
              <Link href="/privacy-policy" className="text-blue-600 hover:underline">Privacy Policy</Link>.
            </p>
          </div>
        </div>
      </section>

      {/* Why join PinkySreya */}
      <section className="bg-white border-y border-blue-100 py-12 sm:py-16">
        <div className="max-w-6xl mx-auto px-4">
          <h2 className="text-2xl sm:text-3xl font-bold text-blue-700 text-center mb-10">
            Why join PinkySreya?
          </h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            {[
              {
                icon: Wallet,
                title: "Earn on your terms",
                text: "Set your availability and get connected to customers in your area. More control, more flexibility.",
              },
              {
                icon: ShieldCheck,
                title: "Verified customers",
                text: "We connect you with genuine customers looking for trusted professionals. No cold calls.",
              },
              {
                icon: Clock,
                title: "Flexible schedule",
                text: "Choose when you want to work. Part-time or full-time—you decide how much you want to earn.",
              },
              {
                icon: Bell,
                title: "Direct requests",
                text: "Receive booking requests and manage them from one place. Simple and transparent.",
              },
            ].map(({ icon: Icon, title, text }) => (
              <div key={title} className="bg-blue-50/50 rounded-xl p-6 border border-blue-100">
                <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mb-4">
                  <Icon className="w-5 h-5 text-blue-600" />
                </div>
                <h3 className="font-semibold text-gray-900 mb-2">{title}</h3>
                <p className="text-gray-600 text-sm">{text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section className="max-w-6xl mx-auto px-4 py-12 sm:py-16">
        <h2 className="text-2xl sm:text-3xl font-bold text-blue-700 text-center mb-10">
          How it works
        </h2>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
          {[
            { step: "1", icon: UserPlus, title: "Register", text: "Enter your mobile number and we&apos;ll get in touch to complete your profile." },
            { step: "2", icon: FileCheck, title: "Get verified", text: "Share your skills and location. Our team verifies your details quickly." },
            { step: "3", icon: Smartphone, title: "Receive requests", text: "Get booking requests from customers near you. Accept the ones that fit your schedule." },
            { step: "4", icon: TrendingUp, title: "Earn", text: "Complete jobs, get paid, and grow your reputation. More jobs mean more earnings." },
          ].map(({ step, icon: Icon, title, text }) => (
            <div key={step} className="relative text-center">
              <div className="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-4 font-bold">
                {step}
              </div>
              <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mx-auto mb-3">
                <Icon className="w-5 h-5 text-blue-600" />
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">{title}</h3>
              <p className="text-gray-600 text-sm">{text}</p>
              {step !== "4" && (
                <div className="hidden lg:block absolute top-6 left-[60%] w-[80%] h-0.5 bg-blue-200" aria-hidden />
              )}
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
