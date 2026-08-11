import { apiClient } from './client'
import type {
  ApiResponse,
  LoginResponse,
  MessageResponse,
  RegisterResponse,
  User,
} from '../types/auth'

export interface ResetPasswordInput {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await apiClient.post<ApiResponse<LoginResponse>>('/auth/login', {
    email,
    password,
  })

  return data.data
}

export async function register(
  display_name: string,
  email: string,
  password: string,
): Promise<RegisterResponse> {
  const { data } = await apiClient.post<ApiResponse<RegisterResponse>>('/auth/register', {
    display_name,
    email,
    password,
  })

  return data.data
}

export async function logout(): Promise<void> {
  await apiClient.post('/auth/logout')
}

export async function fetchCurrentUser(): Promise<User> {
  const { data } = await apiClient.get<ApiResponse<{ user: User }>>('/auth/me')

  return data.data.user
}

export async function resendVerificationEmail(): Promise<string> {
  const { data } = await apiClient.post<ApiResponse<MessageResponse>>(
    '/auth/email/verification-notification',
  )

  return data.data.message
}

export async function verifyEmail(params: {
  id: string
  hash: string
  expires: string
  signature: string
}): Promise<string> {
  const { data } = await apiClient.get<ApiResponse<MessageResponse>>(
    `/auth/email/verify/${params.id}/${params.hash}`,
    {
      params: {
        expires: params.expires,
        signature: params.signature,
      },
    },
  )

  return data.data.message
}

export async function sendPasswordResetLink(email: string): Promise<string> {
  const { data } = await apiClient.post<ApiResponse<MessageResponse>>('/auth/password/reset', {
    email,
  })

  return data.data.message
}

export async function resetPassword(input: ResetPasswordInput): Promise<string> {
  const { data } = await apiClient.post<ApiResponse<MessageResponse>>(
    '/auth/password/reset/confirm',
    input,
  )

  return data.data.message
}
