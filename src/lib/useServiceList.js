"use client";

import { useState, useEffect } from "react";
import { getServices } from "@/lib/api";

export const FALLBACK_SERVICE_NAMES = [
  "AC Repair",
  "Barber",
  "Bathroom Cleaning",
  "Carpenter",
  "Driver",
  "Electrician",
  "House Cleaning",
  "Painter",
  "Plumber",
  "Washing Machine Repair",
];

function sortServiceNames(names) {
  return [...names].filter(Boolean).sort((a, b) => a.localeCompare(b, "en"));
}

export function useServiceList() {
  const [serviceList, setServiceList] = useState(() =>
    sortServiceNames([...FALLBACK_SERVICE_NAMES, "Other"])
  );

  useEffect(() => {
    getServices()
      .then((list) => {
        const names = list?.length
          ? list.map((s) => s.name).filter(Boolean)
          : [];
        const merged = [...new Set([...names, ...FALLBACK_SERVICE_NAMES, "Other"])];
        setServiceList(sortServiceNames(merged));
      })
      .catch(() => {});
  }, []);

  return { serviceList };
}
