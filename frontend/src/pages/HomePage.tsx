import { Link } from 'react-router-dom'
import { useAuthStore } from '../stores/authStore'
import { useLogout } from '../features/auth/hooks'
import { Spinner } from '../components/ui/Spinner'

export function HomePage() {
  const user = useAuthStore((state) => state.user)
  const logout = useLogout()

  return (
    <div className="mx-auto w-full max-w-6xl px-4 py-16">
      <section className="text-center">
        <h1 className="text-4xl font-bold text-slate-900">
          Free literature, open to everyone
        </h1>
        <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
          Read classic and open-licensed books online — no cost, no downloads, no sign-up
          required for reading.
        </p>
        <div className="mt-8 flex items-center justify-center gap-3">
          <Link to="/books" className="btn-primary">
            Browse the catalog
          </Link>
          {!user ? (
            <Link to="/register" className="btn-secondary">
              Create an account
            </Link>
          ) : null}
        </div>
      </section>

      <section className="mt-16 rounded-2xl border border-slate-200 bg-white p-8">
        <h2 className="text-lg font-semibold text-slate-900">
          {user ? `Welcome back, ${user.display_name}` : 'Your account'}
        </h2>
        <p className="mt-2 text-sm text-slate-600">
          {user ? (
            <>
              Signed in as <span className="font-medium">{user.email}</span>
              {user.is_verified ? null : (
                <span className="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                  Email not verified
                </span>
              )}
            </>
          ) : (
            'Sign in to save favorites, track reading progress, and review books.'
          )}
        </p>
        {user ? (
          <div className="mt-4 flex items-center gap-3">
            <Link to="/account" className="btn-secondary">
              My account
            </Link>
            <button
              type="button"
              className="btn-secondary"
              onClick={() => logout.mutate()}
              disabled={logout.isPending}
            >
              {logout.isPending ? <Spinner /> : null}
              Sign out
            </button>
          </div>
        ) : (
          <div className="mt-4 flex items-center gap-3">
            <Link to="/login" className="btn-primary">
              Sign in
            </Link>
            <Link to="/account" className="btn-secondary">
              Try protected page
            </Link>
          </div>
        )}
      </section>
    </div>
  )
}
