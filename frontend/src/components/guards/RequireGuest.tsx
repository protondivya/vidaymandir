import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore } from '../../stores/authStore'

export function RequireGuest() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)
  const location = useLocation()
  const from = (location.state as { from?: string } | null)?.from

  if (isAuthenticated) {
    return <Navigate to={from ?? '/'} replace />
  }

  return <Outlet />
}
