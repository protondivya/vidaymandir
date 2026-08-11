import { Link, Outlet } from 'react-router-dom'

export function PublicLayout() {
  return (
    <div className="flex min-h-screen flex-col">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
          <Link to="/" className="text-lg font-semibold text-brand-700">
            Digital Free Library
          </Link>
          <nav className="flex items-center gap-3">
            <Link to="/books" className="text-sm text-slate-600 hover:text-slate-900">
              Books
            </Link>
            <Link to="/login" className="btn-secondary">
              Sign in
            </Link>
            <Link to="/register" className="btn-primary">
              Create account
            </Link>
          </nav>
        </div>
      </header>
      <main className="flex-1">
        <Outlet />
      </main>
      <footer className="border-t border-slate-200 bg-white py-6">
        <div className="mx-auto w-full max-w-6xl px-4 text-center text-sm text-slate-500">
          Free access to literature for everyone.
        </div>
      </footer>
    </div>
  )
}
