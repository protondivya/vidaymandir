import { apiClient } from './client'
import type {
  ApiResponse,
} from '../types/auth'
import type {
  Book,
  BookInput,
  BookListParams,
  LicenseType,
  PaginatedResponse,
} from '../types/book'

export async function fetchBooks(params: BookListParams): Promise<PaginatedResponse<Book>> {
  const { data } = await apiClient.get<PaginatedResponse<Book>>('/books', { params })

  return data
}

export async function fetchAdminBooks(params: {
  q?: string
  status?: Book['status']
  page?: number
  limit?: number
} = {}): Promise<PaginatedResponse<Book>> {
  const { data } = await apiClient.get<PaginatedResponse<Book>>('/admin/books', { params })

  return data
}

export async function fetchBook(idOrSlug: number | string): Promise<Book> {
  const { data } = await apiClient.get<ApiResponse<{ book: Book }>>(`/books/${idOrSlug}`)

  return data.data.book
}

export async function createBook(input: BookInput): Promise<Book> {
  const { data } = await apiClient.post<ApiResponse<{ book: Book }>>('/books', input)

  return data.data.book
}

export async function updateBook(id: number, input: Partial<BookInput>): Promise<Book> {
  const { data } = await apiClient.put<ApiResponse<{ book: Book }>>(`/books/${id}`, input)

  return data.data.book
}

export async function deleteBook(id: number): Promise<void> {
  await apiClient.delete(`/books/${id}`)
}

export async function fetchLicenseTypes(): Promise<LicenseType[]> {
  const { data } = await apiClient.get<ApiResponse<LicenseType[]>>('/license-types')

  return data.data
}
