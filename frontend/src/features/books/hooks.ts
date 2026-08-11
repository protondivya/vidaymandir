import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as booksApi from '../../api/books'
import type { Book, BookInput, BookListParams } from '../../types/book'

export const bookKeys = {
  all: ['books'] as const,
  list: (params: object) => ['books', 'list', params] as const,
  detail: (idOrSlug: number | string) => ['books', 'detail', idOrSlug] as const,
  licenseTypes: ['books', 'license-types'] as const,
}

export function useBooks(params: BookListParams) {
  return useQuery({
    queryKey: bookKeys.list(params),
    queryFn: () => booksApi.fetchBooks(params),
    placeholderData: (previous) => previous,
  })
}

export function useAdminBooks(params: {
  q?: string
  status?: Book['status']
  page?: number
  limit?: number
} = {}) {
  return useQuery({
    queryKey: bookKeys.list({ ...params, admin: true }),
    queryFn: () => booksApi.fetchAdminBooks(params),
    placeholderData: (previous) => previous,
  })
}

export function useBook(idOrSlug: number | string | undefined) {
  return useQuery({
    queryKey: bookKeys.detail(idOrSlug ?? ''),
    queryFn: () => booksApi.fetchBook(idOrSlug as number | string),
    enabled: idOrSlug !== undefined,
  })
}

export function useLicenseTypes() {
  return useQuery({
    queryKey: bookKeys.licenseTypes,
    queryFn: () => booksApi.fetchLicenseTypes(),
  })
}

export function useCreateBook() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: BookInput) => booksApi.createBook(input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: bookKeys.all }),
  })
}

export function useUpdateBook() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Partial<BookInput> }) =>
      booksApi.updateBook(id, input),
    onSuccess: (book) => {
      queryClient.invalidateQueries({ queryKey: bookKeys.all })
      queryClient.setQueryData(bookKeys.detail(book.id), book)
    },
  })
}

export function useDeleteBook() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => booksApi.deleteBook(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: bookKeys.all }),
  })
}
