import { useCallback, useEffect, useRef, useState } from 'react'
import { Document, Page, pdfjs } from 'react-pdf'
import 'react-pdf/dist/Page/AnnotationLayer.css'
import 'react-pdf/dist/Page/TextLayer.css'
import { fetchBookPdf } from '../../api/reader'
import { Spinner } from '../../components/ui/Spinner'
import { Alert } from '../../components/ui/Alert'

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url,
).toString()

interface PDFViewerProps {
  slug: string
  initialPage: number | null
  onProgress: (currentPage: number, percent: number) => void
}

export function PDFViewer({ slug, initialPage, onProgress }: PDFViewerProps) {
  const [pdfUrl, setPdfUrl] = useState<string | null>(null)
  const [loadError, setLoadError] = useState(false)
  const [numPages, setNumPages] = useState<number | null>(null)
  const [pageNumber, setPageNumber] = useState(1)
  const [scale, setScale] = useState(1)

  const resumeRef = useRef(false)
  const saveTimer = useRef<number | null>(null)
  const onProgressRef = useRef(onProgress)

  onProgressRef.current = onProgress

  useEffect(() => {
    let cancelled = false
    let url: string | null = null

    fetchBookPdf(slug)
      .then((blob) => {
        if (cancelled) {
          return
        }

        url = URL.createObjectURL(blob)
        setPdfUrl(url)
      })
      .catch(() => {
        if (!cancelled) {
          setLoadError(true)
        }
      })

    return () => {
      cancelled = true

      if (saveTimer.current !== null) {
        window.clearTimeout(saveTimer.current)
      }

      if (url !== null) {
        URL.revokeObjectURL(url)
      }
    }
  }, [slug])

  useEffect(() => {
    if (numPages === null || resumeRef.current) {
      return
    }

    resumeRef.current = true

    if (initialPage !== null && initialPage >= 1 && initialPage <= numPages) {
      setPageNumber(initialPage)
    }
  }, [numPages, initialPage])

  const goToPage = useCallback(
    (next: number) => {
      const clamped = Math.min(Math.max(next, 1), numPages ?? 1)

      setPageNumber(clamped)

      if (numPages === null) {
        return
      }

      const percent = Math.min(100, Math.round((clamped / numPages) * 10000) / 100)

      if (saveTimer.current !== null) {
        window.clearTimeout(saveTimer.current)
      }

      saveTimer.current = window.setTimeout(() => {
        onProgressRef.current(clamped, percent)
      }, 800)
    },
    [numPages],
  )

  const percent = numPages ? Math.min(100, Math.round((pageNumber / numPages) * 10000) / 100) : 0

  return (
    <div className="flex flex-col">
      <div className="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-2">
        <div className="flex items-center gap-1">
          <button
            type="button"
            className="btn-secondary !px-2.5 !py-1.5"
            disabled={pageNumber <= 1}
            onClick={() => goToPage(pageNumber - 1)}
          >
            ←
          </button>
          <span className="min-w-28 text-center text-sm text-slate-600">
            Page {numPages ? pageNumber : '–'} of {numPages ?? '–'}
          </span>
          <button
            type="button"
            className="btn-secondary !px-2.5 !py-1.5"
            disabled={numPages === null || pageNumber >= numPages}
            onClick={() => goToPage(pageNumber + 1)}
          >
            →
          </button>
        </div>

        <div className="flex items-center gap-1">
          <button
            type="button"
            className="btn-secondary !px-2.5 !py-1.5"
            disabled={scale <= 0.5}
            onClick={() => setScale((value) => Math.max(0.5, Math.round((value - 0.25) * 4) / 4))}
          >
            −
          </button>
          <span className="min-w-12 text-center text-sm text-slate-600">{Math.round(scale * 100)}%</span>
          <button
            type="button"
            className="btn-secondary !px-2.5 !py-1.5"
            disabled={scale >= 2.5}
            onClick={() => setScale((value) => Math.min(2.5, Math.round((value + 0.25) * 4) / 4))}
          >
            +
          </button>
        </div>

        <div className="flex h-2 w-full max-w-56 flex-1 overflow-hidden rounded-full bg-slate-200">
          <div
            className="h-full bg-brand-600 transition-all"
            style={{ width: `${percent}%` }}
            aria-label={`${percent}% read`}
          />
        </div>
      </div>

      {loadError ? (
        <div className="mx-auto w-full max-w-lg px-4 py-16">
          <Alert variant="error">This book could not be loaded for reading.</Alert>
        </div>
      ) : pdfUrl === null ? (
        <div className="flex justify-center py-24">
          <Spinner className="h-8 w-8" />
        </div>
      ) : (
        <div className="flex-1 overflow-auto bg-slate-100 p-4" style={{ maxHeight: 'calc(100vh - 12rem)' }}>
          <div className="mx-auto flex w-fit flex-col items-center">
            <Document
              file={pdfUrl}
              onLoadSuccess={({ numPages: total }) => {
                setNumPages(total)
                setPageNumber(1)
              }}
              onLoadError={() => setLoadError(true)}
            >
              <Page pageNumber={pageNumber} scale={scale} className="rounded-lg shadow-lg" />
            </Document>
          </div>
        </div>
      )}
    </div>
  )
}
