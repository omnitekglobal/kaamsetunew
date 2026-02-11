"use client";

import { Suspense } from "react";
import SuccessContent from "./SuccessContent";

export default function BookingSuccessPage() {
  return (
    <Suspense fallback={<div className="p-10 text-center">Loading...</div>}>
      <SuccessContent />
    </Suspense>
  );
}
