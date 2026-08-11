import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as categoriesApi from '../../api/categories'
import type { CategoryInput } from '../../types/book'

export const categoryKeys = {
  all: ['categories'] as const,
  books: (slug: string, params: { page?: number; limit?: number }) =>
    ['categories', slug, 'books', params] as const,
}

export function useCategories() {
  return useQuery({
    queryKey: categoryKeys.all,
    queryFn: () => categoriesApi.fetchCategories(),
  })
}

export function useCategoryBooks(slug: string, params: { page?: number; limit?: number } = {}) {
  return useQuery({
    queryKey: categoryKeys.books(slug, params),
    queryFn: () => categoriesApi.fetchCategoryBooks(slug, params),
    enabled: slug.length > 0,
  })
}

export function useCreateCategory() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: CategoryInput) => categoriesApi.createCategory(input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: categoryKeys.all }),
  })
}

export function useUpdateCategory() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Partial<CategoryInput> }) =>
      categoriesApi.updateCategory(id, input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: categoryKeys.all }),
  })
}

export function useDeleteCategory() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => categoriesApi.deleteCategory(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: categoryKeys.all }),
  })
}
