import pool from "@/lib/db";

export const dynamic = "force-dynamic";

export default async function Page({ params }) {
  const { professionalId } = await params;

  const [rows] = await pool.execute(
    "SELECT * FROM professionals WHERE professionalId = ?",
    [professionalId]
  );

  if (rows.length === 0) {
    return <div className="p-10 text-center">Professional Not Found</div>;
  }

  const data = rows[0];

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 flex items-center justify-center px-4 py-12">
      <div className="max-w-3xl w-full bg-white rounded-2xl shadow-2xl p-10">

        <div className="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center">
          <span className="text-green-600 text-4xl">✓</span>
        </div>

        <h1 className="mt-6 text-3xl font-bold text-center text-purple-700">
          Professional Registration Successful!
        </h1>

        <p className="mt-3 text-center text-gray-600">
          Welcome <span className="font-semibold">{data.name}</span> 🎉
        </p>

        <div className="mt-4 text-center">
          <span className="text-sm text-gray-500">Professional ID</span>
          <p className="text-lg font-bold text-purple-700">
            {data.professionalId}
          </p>
        </div>

        <div className="mt-8 bg-purple-50 border border-purple-100 rounded-xl p-6 space-y-3 text-sm">

          <Detail label="Phone" value={data.phone} />
          <Detail label="Email" value={data.email} />
          <Detail label="City" value={data.city} />
          <Detail label="State" value={data.state} />
          <Detail label="Pincode" value={data.pincode} />
          <Detail label="Language" value={data.language} />
          <Detail label="Selected Services" value={data.services} />
          <Detail label="Status" value={data.status} />

        </div>
      </div>
    </div>
  );
}

function Detail({ label, value }) {
  return (
    <div className="flex justify-between border-b border-purple-100 pb-2">
      <span className="text-gray-600">{label}</span>
      <span className="font-semibold text-purple-700">
        {value || "—"}
      </span>
    </div>
  );
}
