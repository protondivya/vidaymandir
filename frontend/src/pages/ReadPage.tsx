import { useCallback } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import { PDFViewer } from '../features/reader/PDFViewer'
import { useBook } from '../features/books/hooks'
import { useDownloadBookPdf, useReadingProgress, useSaveReadingProgress } from '../features/reader/hooks'
import { Spinner } from '../components/ui/Spinner'
import { Alert } from '../components/ui/Alert'

export function ReadPage() {
  const { slug } = useParams()
  const bookQuery = useBook(slug)
  const progressQuery = useReadingProgress(slug)
  const saveProgress = useSaveReadingProgress(slug ?? '')
  const download = useDownloadBookPdf()

  const handleProgress = useCallback(
    (currentPage: number, percent: number) => {
      saveProgress.mutate({ current_page: currentPage, percent })
    },
    [saveProgress],
  )

  const handleDownload = useCallback(() => {
    if (!slug || !bookQuery.data) {
      return
    }

    download.mutate(slug, {
      onSuccess: (blob) => {
        const url = URL.createObjectURL(blob)
        const anchor = document.createElement('a')
        anchor.href = url
        anchor.download = `${bookQuery.data.slug}-download.pdf`
        document.body.appendChild(anchor)
        anchor.click()
        anchor.remove()
        URL.revokeObjectURL(url)
      },
    })
  }, [slug, bookQuery.data, download])

  if (slug === undefined) {
    return <Navigate to="/books" replace />
  }

  if (bookQuery.isPending) {
    return (
      <div className="flex justify-center py-24">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (bookQuery.isError || !bookQuery.data) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-16">
        <Alert variant="error">This book could not be found.</Alert>
        <Link to="/books" className="btn-secondary mt-6">
          Back to catalog
        </Link>
      </div>
    )
  }

  const book = bookQuery.data

  if (!book.has_pdf) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-16">
        <Alert variant="info">This book has no PDF available for reading yet.</Alert>
        <Link to={`/books/${book.slug}`} className="btn-secondary mt-6">
          Back to book
        </Link>
      </div>
    )
  }

  const initialPage = progressQuery.data?.current_page ?? null

  return (
    <div className="flex min-h-screen flex-col">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
          <Link to={`/books/${book.slug}`} className="text-sm font-medium text-brand-700 hover:underline">
            ← {book.title}
          </Link>
          <button
            type="button"
            className="btn-secondary"
            onClick={handleDownload}
            disabled={!book.is_downloadable || download.isPending}
          >
            {download.isPending ? <Spinner /> : null}
            Download PDF
          </button>
        </div>
      </header>

      {download.isError ? (
        <div className="mx-auto w-full max-w-lg px-4 pt-4">
          <Alert variant="error">This book could not be downloaded right now.</Alert>
        </div>
      ) : null}

      <main className="flex flex-1 flex-col">
        <PDFViewer slug={book.slug} initialPage={initialPage} onProgress={handleProgress} />
      </main>
    </div>
  )
}
