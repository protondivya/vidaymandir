import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAdminBooks, useDeleteBook } from '../../books/hooks'
import { useDebounce } from '../../../hooks/useDebounce'
import { Pagination } from '../../../components/shared/Pagination'
import { Spinner } from '../../../components/ui/Spinner'
import { Alert } from '../../../components/ui/Alert'
import { StatusBadge } from '../../../components/ui/StatusBadge'
import { getApiErrorMessage } from '../../../lib/apiError'
import type { BookStatus } from '../../../types/book'

const LIMIT = 20

export function AdminBooksPage() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState<'' | BookStatus>('')
  const [page, setPage] = useState(1)
  const [notice, setNotice] = useState<string | null>(null)

  const debouncedSearch = useDebounce(search, 300)

  const booksQuery = useAdminBooks({
    q: debouncedSearch,
    status: status || undefined,
    page,
    limit: LIMIT,
  })

  const deleteBook = useDeleteBook()

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const handleDelete = (id: number, title: string) => {
    if (!window.confirm(`Deactivate "${title}"? It will be hidden from the public catalog.`)) {
      return
    }

    deleteBook.mutate(id, {
      onSuccess: () => setNotice(`"${title}" has been deactivated.`),
      onError: () => setNotice(null),
    })
  }

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Books</h1>
          <p className="mt-1 text-sm text-slate-500">Manage the catalog across all statuses.</p>
        </div>
        <Link to="/admin/books/new" className="btn-primary">
          Add book
        </Link>
      </div>

      {notice ? <Alert variant="success" className="mt-4">{notice}</Alert> : null}
      {deleteBook.isError ? (
        <Alert variant="error" className="mt-4">{getApiErrorMessage(deleteBook.error)}</Alert>
      ) : null}

      <div className="mt-6 grid gap-3 sm:grid-cols-[1fr_180px]">
        <input
          type="search"
          className="input"
          placeholder="Search by title or author…"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
        <select
          className="input"
          aria-label="Filter by status"
          value={status}
          onChange={(event) => setStatus(event.target.value as '' | BookStatus)}
        >
          <option value="">All statuses</option>
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="deactivated">Deactivated</option>
        </select>
      </div>

      <div className="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        {booksQuery.isPending ? (
          <div className="flex justify-center py-16">
            <Spinner className="h-8 w-8" />
          </div>
        ) : booksQuery.isError ? (
          <div className="p-6">
            <Alert variant="error">Failed to load books.</Alert>
          </div>
        ) : (booksQuery.data?.data.length ?? 0) === 0 ? (
          <p className="py-16 text-center text-sm text-slate-500">No books found.</p>
        ) : (
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3 font-medium">Title</th>
                <th className="px-4 py-3 font-medium">Authors</th>
                <th className="px-4 py-3 font-medium">Language</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {(booksQuery.data?.data ?? []).map((book) => (
                <tr key={book.id} className="hover:bg-slate-50">
                  <td className="max-w-64 px-4 py-3">
                    <p className="truncate font-medium text-slate-900">{book.title}</p>
                  </td>
                  <td className="px-4 py-3 text-slate-600">
                    {(book.authors ?? []).map((author) => author.name).join(', ') || '—'}
                  </td>
                  <td className="px-4 py-3 uppercase text-slate-500">{book.language}</td>
                  <td className="px-4 py-3">
                    <StatusBadge status={book.status} />
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex gap-2">
                      <Link to={`/admin/books/${book.id}/edit`} className="btn-secondary !px-3 !py-1">
                        Edit
                      </Link>
                      <button
                        type="button"
                        className="btn-secondary !px-3 !py-1 text-red-600"
                        disabled={deleteBook.isPending}
                        onClick={() => handleDelete(book.id, book.title)}
                      >
                        Deactivate
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {booksQuery.data?.meta ? (
        <Pagination meta={booksQuery.data.meta} onPageChange={setPage} />
      ) : null}
    </div>
  )
}
