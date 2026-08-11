import { Link } from 'react-router-dom'
import { useCurrentUser, useLogout } from '../features/auth/hooks'
import { Spinner } from '../components/ui/Spinner'
import { Alert } from '../components/ui/Alert'

export function AccountPage() {
  const { data: user, isLoading } = useCurrentUser()
  const logout = useLogout()

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-16">
      <h1 className="text-3xl font-bold text-slate-900">My account</h1>

      {isLoading ? (
        <div className="mt-8 flex items-center gap-3 text-slate-500">
          <Spinner className="text-brand-600" /> Loading your profile…
        </div>
      ) : user ? (
        <div className="mt-8 space-y-6">
          <section className="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="text-lg font-semibold text-slate-900">{user.display_name}</h2>
            <dl className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <dt className="text-sm text-slate-500">Email</dt>
                <dd className="mt-1 text-sm font-medium text-slate-900">{user.email}</dd>
              </div>
              <div>
                <dt className="text-sm text-slate-500">Role</dt>
                <dd className="mt-1 text-sm font-medium text-slate-900">{user.role}</dd>
              </div>
              <div>
                <dt className="text-sm text-slate-500">Verification</dt>
                <dd className="mt-1 text-sm font-medium text-slate-900">
                  {user.is_verified ? 'Verified' : 'Not verified'}
                </dd>
              </div>
              <div>
                <dt className="text-sm text-slate-500">Member since</dt>
                <dd className="mt-1 text-sm font-medium text-slate-900">
                  {user.created_at ? new Date(user.created_at).toLocaleDateString() : '—'}
                </dd>
              </div>
            </dl>
          </section>

          {!user.is_verified ? (
            <Alert variant="info">
              Your email is not verified yet. Check your inbox for the verification link we
              sent when you registered.
            </Alert>
          ) : null}

          <div className="flex items-center gap-3">
            <button
              type="button"
              className="btn-secondary"
              onClick={() => logout.mutate()}
              disabled={logout.isPending}
            >
              {logout.isPending ? <Spinner /> : null}
              Sign out
            </button>
            <Link to="/" className="btn-secondary">
              Back home
            </Link>
          </div>
        </div>
      ) : null}
    </div>
  )
}
