/** @type {import('next').NextConfig} */
const nextConfig = {
  reactCompiler: true,
  async rewrites() {
    const apiUrl = process.env.API_URL || "http://localhost:8080";
    return [{ source: "/uploads/:path*", destination: `${apiUrl}/uploads/:path*` }];
  },
};

export default nextConfig;
