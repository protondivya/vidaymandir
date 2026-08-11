import axios from 'axios'
import type { ErrorResponse } from '../types/auth'

export function getApiErrorMessage(error: unknown): string {
  if (axios.isAxiosError<ErrorResponse>(error)) {
    return error.response?.data?.message ?? error.message
  }

  return 'An unexpected error occurred. Please try again.'
}

export function getApiFieldErrors(error: unknown): Record<string, string[]> {
  if (axios.isAxiosError<ErrorResponse>(error)) {
    return error.response?.data?.errors ?? {}
  }

  return {}
}
