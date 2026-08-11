import { apiClient } from './client'
import type { ApiResponse } from '../types/auth'
import type {
  Author,
  AuthorInput,
  PaginatedResponse,
} from '../types/book'

export async function fetchAuthors(params: {
  q?: string
  page?: number
  limit?: number
} = {}): Promise<PaginatedResponse<Author>> {
  const { data } = await apiClient.get<PaginatedResponse<Author>>('/authors', { params })

  return data
}

export async function createAuthor(input: AuthorInput): Promise<Author> {
  const { data } = await apiClient.post<ApiResponse<{ author: Author }>>('/authors', input)

  return data.data.author
}

export async function updateAuthor(id: number, input: Partial<AuthorInput>): Promise<Author> {
  const { data } = await apiClient.put<ApiResponse<{ author: Author }>>(`/authors/${id}`, input)

  return data.data.author
}

export async function deleteAuthor(id: number): Promise<void> {
  await apiClient.delete(`/authors/${id}`)
}
