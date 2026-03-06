import { notFound } from "next/navigation";
import { getBlog } from "@/lib/api";

export async function generateMetadata({ params }) {
  try {
    const blog = await getBlog(params.slug);
    return {
      title: blog?.title
        ? `${blog.title} | PinkySreya Blog`
        : "Blog | PinkySreya",
      description: blog?.excerpt || "Read the latest from PinkySreya.",
    };
  } catch {
    return {
      title: "Blog | PinkySreya",
      description: "Read the latest from PinkySreya.",
    };
  }
}

export default async function BlogDetailPage({ params }) {
  let blog;
  try {
    blog = await getBlog(params.slug);
  } catch {
    return notFound();
  }

  if (!blog || (!blog.title && !blog.body)) {
    return notFound();
  }

  return (
    <article className="max-w-3xl mx-auto px-6 py-12 md:py-16">
      <p className="text-xs text-gray-400 mb-2">
        {blog.published_at
          ? new Date(blog.published_at).toLocaleDateString()
          : ""}
      </p>
      <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        {blog.title}
      </h1>
      {blog.excerpt && (
        <p className="text-lg text-gray-600 mb-6">{blog.excerpt}</p>
      )}
      {blog.cover_image_url && (
        <img
          src={blog.cover_image_url}
          alt={blog.title}
          className="w-full rounded-2xl mb-8 object-cover max-h-96"
        />
      )}
      <div className="prose prose-blue max-w-none">
        {blog.body ? (
          <div
            dangerouslySetInnerHTML={{ __html: blog.body }}
          />
        ) : (
          <p className="text-gray-600">
            Full content will be available soon.
          </p>
        )}
      </div>
    </article>
  );
}

