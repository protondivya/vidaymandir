import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as authApi from '../../api/auth'
import { useAuthStore } from '../../stores/authStore'

export const authKeys = {
  me: ['auth', 'me'] as const,
}

export function useCurrentUser() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)
  const setUser = useAuthStore((state) => state.setUser)

  return useQuery({
    queryKey: authKeys.me,
    queryFn: async () => {
      const user = await authApi.fetchCurrentUser()
      setUser(user)
      return user
    },
    enabled: isAuthenticated,
    retry: false,
  })
}

export function useLogin() {
  const queryClient = useQueryClient()
  const setSession = useAuthStore((state) => state.setSession)

  return useMutation({
    mutationFn: ({ email, password }: { email: string; password: string }) =>
      authApi.login(email, password),
    onSuccess: (result) => {
      setSession(result.access_token, result.expires_at, result.user)
      queryClient.setQueryData(authKeys.me, result.user)
    },
  })
}

export function useRegister() {
  return useMutation({
    mutationFn: ({
      display_name,
      email,
      password,
    }: {
      display_name: string
      email: string
      password: string
    }) => authApi.register(display_name, email, password),
  })
}

export function useLogout() {
  const queryClient = useQueryClient()
  const clearSession = useAuthStore((state) => state.clearSession)

  return useMutation({
    mutationFn: () => authApi.logout(),
    onSettled: () => {
      clearSession()
      queryClient.clear()
    },
  })
}

export function useSendPasswordResetLink() {
  return useMutation({
    mutationFn: (email: string) => authApi.sendPasswordResetLink(email),
  })
}

export function useResetPassword() {
  return useMutation({
    mutationFn: (input: authApi.ResetPasswordInput) => authApi.resetPassword(input),
  })
}

export function useVerifyEmail() {
  return useMutation({
    mutationFn: (params: {
      id: string
      hash: string
      expires: string
      signature: string
    }) => authApi.verifyEmail(params),
  })
}

export function useResendVerification() {
  return useMutation({
    mutationFn: () => authApi.resendVerificationEmail(),
  })
}
