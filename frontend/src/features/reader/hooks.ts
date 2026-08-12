import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as readerApi from '../../api/reader'
import type { ProgressInput } from '../../api/reader'
import type { Book, ReadingProgress } from '../../types/book'
import { bookKeys } from '../books/hooks'

export const readerKeys = {
  progress: (idOrSlug: number | string) => ['reader', 'progress', idOrSlug] as const,
}

export function useReadingProgress(idOrSlug: number | string | undefined) {
  return useQuery({
    queryKey: readerKeys.progress(idOrSlug ?? ''),
    queryFn: () => readerApi.fetchReadingProgress(idOrSlug as number | string),
    enabled: idOrSlug !== undefined,
  })
}

export function useSaveReadingProgress(idOrSlug: number | string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (progress: ProgressInput) => readerApi.saveReadingProgress(idOrSlug, progress),
    onSuccess: (data) => {
      queryClient.setQueryData<ReadingProgress | null>(readerKeys.progress(idOrSlug), data)
    },
  })
}

export function useDownloadBookPdf() {
  return useMutation({
    mutationFn: (idOrSlug: number | string) => readerApi.downloadBookPdf(idOrSlug),
  })
}

export function useUploadBookPdf() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, file }: { id: number; file: File }) => readerApi.uploadBookPdf(id, file),
    onSuccess: (book: Book) => {
      queryClient.invalidateQueries({ queryKey: bookKeys.all })
      queryClient.setQueryData(bookKeys.detail(book.id), book)
    },
  })
}
