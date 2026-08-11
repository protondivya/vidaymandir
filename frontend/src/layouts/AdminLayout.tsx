import { Link, NavLink, Outlet } from 'react-router-dom'

const navLinkClass = ({ isActive }: { isActive: boolean }) =>
  `block rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
    isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
  }`

export function AdminLayout() {
  return (
    <div className="flex min-h-screen flex-col">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
          <div className="flex items-center gap-6">
            <Link to="/" className="text-lg font-semibold text-brand-700">
              Digital Free Library
            </Link>
            <span className="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
              Librarian console
            </span>
          </div>
          <nav className="flex items-center gap-3">
            <Link to="/books" className="text-sm text-slate-600 hover:text-slate-900">
              View catalog
            </Link>
            <Link to="/" className="btn-secondary">
              Back to site
            </Link>
          </nav>
        </div>
      </header>
      <div className="mx-auto flex w-full max-w-6xl flex-1 gap-8 px-4 py-8">
        <aside className="w-48 shrink-0">
          <nav className="sticky top-8 space-y-1">
            <NavLink to="/admin/books" className={navLinkClass}>
              Books
            </NavLink>
            <NavLink to="/admin/categories" className={navLinkClass}>
              Categories
            </NavLink>
            <NavLink to="/admin/authors" className={navLinkClass}>
              Authors
            </NavLink>
          </nav>
        </aside>
        <main className="min-w-0 flex-1">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
