export type UserRole = 'reader' | 'librarian' | 'admin'

export interface User {
  id: number
  display_name: string
  email: string
  role: UserRole
  avatar_url: string | null
  is_active: boolean
  is_verified: boolean
  created_at: string | null
}

export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
}

export interface ErrorResponse {
  success: boolean
  message: string
  errors?: Record<string, string[]>
}

export interface LoginResponse {
  access_token: string
  token_type: string
  expires_at: string | null
  user: User
}

export interface RegisterResponse {
  user: User
  message: string
}

export interface MessageResponse {
  message: string
}
