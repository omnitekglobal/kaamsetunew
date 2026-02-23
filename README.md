This is a [Next.js](https://nextjs.org) project bootstrapped with [`create-next-app`](https://github.com/vercel/next.js/tree/canary/packages/create-next-app).

## Linking to PHP API

The frontend uses the **PHP API** (`php-api/`) for categories, services, bookings, and professional registration.

### Local development

1. **Run the PHP API** (from repo root):
   ```bash
   cd php-api && php -S localhost:8080 -t . router.php
   ```
2. **Create `.env.local`** in the Next.js root (copy from `.env.example`):
   - Either set `API_URL=http://localhost:8080` and leave `NEXT_PUBLIC_API_URL` unset (browser uses same-origin `/api/*`, Next proxies to PHP), or
   - Set `NEXT_PUBLIC_API_URL=http://localhost:8080` to call the API directly from the browser.
3. Run the Next.js app: `npm run dev` and open http://localhost:3000.

### Production (avoid CORS)

To avoid CORS and “Redirect is not allowed for a preflight request”:

- **Do not set** `NEXT_PUBLIC_API_URL` in production. The browser must call your app’s origin only (e.g. `https://pinkysreya.com/api/...`).
- **Set** `API_URL` to your PHP API base URL in your hosting env (e.g. Vercel → Project → Settings → Environment Variables). The API can be on a subdomain (e.g. `https://setu.pinkysreya.com`) or any other domain.
- Next.js will proxy `/api/*` to that URL on the server. No cross-origin requests from the browser, so no CORS and no preflight redirect issues.

If you set `NEXT_PUBLIC_API_URL` to the API domain, the browser hits that domain directly and the API server must not redirect `OPTIONS` requests (many hosts redirect and cause the preflight error).

## Getting Started

First, run the development server:

```bash
npm run dev
# or
yarn dev
# or
pnpm dev
# or
bun dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

You can start editing the page by modifying `app/page.js`. The page auto-updates as you edit the file.

This project uses [`next/font`](https://nextjs.org/docs/app/building-your-application/optimizing/fonts) to automatically optimize and load [Geist](https://vercel.com/font), a new font family for Vercel.

## Learn More

To learn more about Next.js, take a look at the following resources:

- [Next.js Documentation](https://nextjs.org/docs) - learn about Next.js features and API.
- [Learn Next.js](https://nextjs.org/learn) - an interactive Next.js tutorial.

You can check out [the Next.js GitHub repository](https://github.com/vercel/next.js) - your feedback and contributions are welcome!

## Deploy on Vercel

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

Check out our [Next.js deployment documentation](https://nextjs.org/docs/app/building-your-application/deploying) for more details.
