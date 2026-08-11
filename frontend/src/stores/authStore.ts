import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User } from '../types/auth'

interface AuthState {
  accessToken: string | null
  expiresAt: string | null
  user: User | null
  isAuthenticated: boolean
  setSession: (accessToken: string, expiresAt: string | null, user: User) => void
  setUser: (user: User) => void
  clearSession: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      accessToken: null,
      expiresAt: null,
      user: null,
      isAuthenticated: false,
      setSession: (accessToken, expiresAt, user) =>
        set({ accessToken, expiresAt, user, isAuthenticated: true }),
      setUser: (user) => set({ user }),
      clearSession: () =>
        set({ accessToken: null, expiresAt: null, user: null, isAuthenticated: false }),
    }),
    {
      name: 'dfl-auth',
    },
  ),
)
