"use client";

import { Suspense } from "react";
import BookingForm from "./BookingForm";

export default function BookingPageWrapper() {
  return (
    <Suspense fallback={<div className="p-10 text-center">Loading...</div>}>
      <BookingForm />
    </Suspense>
  );
}
