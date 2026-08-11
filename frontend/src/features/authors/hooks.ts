import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as authorsApi from '../../api/authors'
import type { AuthorInput } from '../../types/book'

export const authorKeys = {
  all: ['authors'] as const,
  list: (params: { q?: string; page?: number; limit?: number }) =>
    ['authors', 'list', params] as const,
}

export function useAuthors(params: { q?: string; page?: number; limit?: number } = {}) {
  return useQuery({
    queryKey: authorKeys.list(params),
    queryFn: () => authorsApi.fetchAuthors(params),
    placeholderData: (previous) => previous,
  })
}

export function useCreateAuthor() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: AuthorInput) => authorsApi.createAuthor(input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: authorKeys.all }),
  })
}

export function useUpdateAuthor() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Partial<AuthorInput> }) =>
      authorsApi.updateAuthor(id, input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: authorKeys.all }),
  })
}

export function useDeleteAuthor() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => authorsApi.deleteAuthor(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: authorKeys.all }),
  })
}
