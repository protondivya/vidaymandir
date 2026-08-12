import { apiClient } from './client'
import type { ApiResponse } from '../types/auth'
import type { Book, ReadingProgress } from '../types/book'

export interface ProgressInput {
  current_page?: number | null
  percent?: number | null
}

export async function fetchBookPdf(idOrSlug: number | string): Promise<Blob> {
  const { data } = await apiClient.get<Blob>(`/books/${idOrSlug}/pdf`, {
    responseType: 'blob',
  })

  return data
}

export async function downloadBookPdf(idOrSlug: number | string): Promise<Blob> {
  const { data } = await apiClient.get<Blob>(`/books/${idOrSlug}/download`, {
    responseType: 'blob',
  })

  return data
}

export async function fetchReadingProgress(
  idOrSlug: number | string,
): Promise<ReadingProgress | null> {
  const { data } = await apiClient.get<ApiResponse<{ progress: ReadingProgress | null }>>(
    `/books/${idOrSlug}/progress`,
  )

  return data.data.progress
}

export async function saveReadingProgress(
  idOrSlug: number | string,
  progress: ProgressInput,
): Promise<ReadingProgress> {
  const { data } = await apiClient.put<ApiResponse<{ progress: ReadingProgress }>>(
    `/books/${idOrSlug}/progress`,
    progress,
  )

  return data.data.progress
}

export async function uploadBookPdf(id: number, file: File): Promise<Book> {
  const form = new FormData()
  form.append('pdf', file)

  const { data } = await apiClient.post<ApiResponse<{ book: Book }>>(`/books/${id}/pdf`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  return data.data.book
}
