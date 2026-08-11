import { Link } from 'react-router-dom'

export function BooksPage() {
  return (
    <div className="mx-auto w-full max-w-6xl px-4 py-16">
      <h1 className="text-3xl font-bold text-slate-900">Catalog</h1>
      <p className="mt-2 text-slate-600">
        The catalog module is coming next. Sign in to test the authentication flow.
      </p>
      <div className="mt-6">
        <Link to="/" className="btn-secondary">
          Back home
        </Link>
      </div>
    </div>
  )
}
