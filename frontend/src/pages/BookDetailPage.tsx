import { Link, useParams } from 'react-router-dom'
import { useBook } from '../features/books/hooks'
import { useDownloadBookPdf } from '../features/reader/hooks'
import { useAuthStore } from '../stores/authStore'
import { Spinner } from '../components/ui/Spinner'
import { Alert } from '../components/ui/Alert'

export function BookDetailPage() {
  const { slug } = useParams()
  const bookQuery = useBook(slug)
  const download = useDownloadBookPdf()
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)

  const handleDownload = () => {
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
  const authors = (book.authors ?? []).map((author) => author.name).join(', ')

  return (
    <div className="mx-auto w-full max-w-4xl px-4 py-12">
      <Link to="/books" className="text-sm text-brand-600 hover:underline">
        ← Back to catalog
      </Link>

      <div className="mt-6 flex flex-col gap-8 sm:flex-row">
        <div className="flex h-64 w-44 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-300">
          <span className="text-5xl font-bold">B</span>
        </div>
        <div className="min-w-0">
          <h1 className="text-3xl font-bold text-slate-900">{book.title}</h1>
          {authors ? <p className="mt-2 text-lg text-slate-600">by {authors}</p> : null}
          <div className="mt-3 flex flex-wrap items-center gap-2 text-sm">
            <span className="rounded bg-slate-100 px-2 py-0.5 uppercase text-slate-600">
              {book.language}
            </span>
            {book.license_type ? (
              <span className="rounded bg-slate-100 px-2 py-0.5 text-slate-600">
                {book.license_type.name}
              </span>
            ) : null}
            {book.published_at ? (
              <span className="text-slate-500">
                Published {new Date(book.published_at).toLocaleDateString()}
              </span>
            ) : null}
          </div>
          {(book.categories ?? []).length > 0 ? (
            <div className="mt-3 flex flex-wrap gap-2">
              {(book.categories ?? []).map((category) => (
                <Link
                  key={category.id}
                  to={`/books?category=${category.slug}`}
                  className="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 hover:bg-brand-100"
                >
                  {category.name}
                </Link>
              ))}
            </div>
          ) : null}

          {book.has_pdf ? (
            <div className="mt-5 flex flex-wrap items-center gap-3">
              {isAuthenticated ? (
                <Link to={`/books/${book.slug}/read`} className="btn-primary">
                  Read online
                </Link>
              ) : (
                <Link
                  to="/login"
                  state={{ from: `/books/${book.slug}/read` }}
                  className="btn-primary"
                >
                  Sign in to read
                </Link>
              )}

              {isAuthenticated ? (
                <button
                  type="button"
                  className="btn-secondary"
                  disabled={!book.is_downloadable || download.isPending}
                  onClick={handleDownload}
                  title={
                    book.is_downloadable
                      ? 'Download a watermarked PDF'
                      : 'Downloading is disabled for this book'
                  }
                >
                  {download.isPending ? <Spinner /> : null}
                  Download PDF
                </button>
              ) : (
                <Link to="/login" state={{ from: `/books/${book.slug}` }} className="btn-secondary">
                  Sign in to download
                </Link>
              )}
            </div>
          ) : null}

          {download.isError ? (
            <p className="mt-3 text-sm text-red-600">This book could not be downloaded right now.</p>
          ) : null}
        </div>
      </div>

      {book.synopsis ? (
        <section className="mt-10">
          <h2 className="text-lg font-semibold text-slate-900">Synopsis</h2>
          <p className="mt-2 leading-relaxed text-slate-700">{book.synopsis}</p>
        </section>
      ) : null}

      <div className="mt-8 grid gap-4 text-sm sm:grid-cols-3">
        {book.page_count ? (
          <div className="rounded-xl border border-slate-200 bg-white p-4">
            <p className="text-xs uppercase text-slate-500">Pages</p>
            <p className="mt-1 font-semibold text-slate-900">{book.page_count.toLocaleString()}</p>
          </div>
        ) : null}
        {book.word_count ? (
          <div className="rounded-xl border border-slate-200 bg-white p-4">
            <p className="text-xs uppercase text-slate-500">Words</p>
            <p className="mt-1 font-semibold text-slate-900">{book.word_count.toLocaleString()}</p>
          </div>
        ) : null}
        <div className="rounded-xl border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase text-slate-500">Views</p>
          <p className="mt-1 font-semibold text-slate-900">{book.view_count.toLocaleString()}</p>
        </div>
      </div>
    </div>
  )
}
