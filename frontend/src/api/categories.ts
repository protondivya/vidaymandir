import { apiClient } from './client'
import type { ApiResponse } from '../types/auth'
import type {
  Book,
  Category,
  CategoryInput,
  PaginatedResponse,
} from '../types/book'

export async function fetchCategories(): Promise<Category[]> {
  const { data } = await apiClient.get<ApiResponse<Category[]>>('/categories')

  return data.data
}

export async function fetchCategoryBooks(
  slug: string,
  params: { page?: number; limit?: number } = {},
): Promise<PaginatedResponse<Book>> {
  const { data } = await apiClient.get<PaginatedResponse<Book>>(`/categories/${slug}/books`, {
    params,
  })

  return data
}

export async function createCategory(input: CategoryInput): Promise<Category> {
  const { data } = await apiClient.post<ApiResponse<{ category: Category }>>('/categories', input)

  return data.data.category
}

export async function updateCategory(id: number, input: Partial<CategoryInput>): Promise<Category> {
  const { data } = await apiClient.put<ApiResponse<{ category: Category }>>(`/categories/${id}`, input)

  return data.data.category
}

export async function deleteCategory(id: number): Promise<void> {
  await apiClient.delete(`/categories/${id}`)
}
