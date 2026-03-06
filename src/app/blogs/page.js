import Link from "next/link";
import { getBlogs } from "@/lib/api";

export const metadata = {
  title: "Blogs | PinkySreya",
  description: "Tips, stories, and updates from the PinkySreya team.",
};

export default async function BlogsPage() {
  let blogs = [];
  let hasError = false;

  try {
    blogs = await getBlogs();
  } catch (e) {
    hasError = true;
  }

  return (
    <section className="max-w-6xl mx-auto px-6 py-12 md:py-16">
      <div className="mb-10 text-center">
        <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
          PinkySreya Blog
        </h1>
        <p className="text-gray-600 max-w-2xl mx-auto">
          Learn how to get the most out of home services, grow your business as
          a professional, and stay up to date with PinkySreya news.
        </p>
      </div>

      {hasError && (
        <p className="text-center text-red-500 mb-6">
          Blog content will be available soon.
        </p>
      )}

      {blogs.length === 0 && !hasError && (
        <p className="text-center text-gray-500">
          No blog posts yet. Please check back soon.
        </p>
      )}

      {blogs.length > 0 && (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          {blogs.map((blog) => (
            <article
              key={blog.id || blog.slug}
              className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            >
              {blog.cover_image_url && (
                <div className="relative h-44 w-full">
                  <img
                    src={blog.cover_image_url}
                    alt={blog.title || "Blog cover"}
                    className="object-cover w-full h-full"
                    loading="lazy"
                  />
                </div>
              )}
              <div className="p-5 flex flex-col flex-1">
                <p className="text-xs text-gray-400 mb-1">
                  {blog.published_at
                    ? new Date(blog.published_at).toLocaleDateString()
                    : ""}
                </p>
                <h2 className="font-semibold text-lg text-gray-900 mb-2 line-clamp-2">
                  {blog.title}
                </h2>
                {blog.excerpt && (
                  <p className="text-sm text-gray-600 mb-4 line-clamp-3">
                    {blog.excerpt}
                  </p>
                )}
                <div className="mt-auto">
                  <Link
                    href={`/blogs/${blog.slug || blog.id}`}
                    className="text-blue-600 text-sm font-semibold hover:underline"
                  >
                    Read more
                  </Link>
                </div>
              </div>
            </article>
          ))}
        </div>
      )}
    </section>
  );
}

