import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useBooks } from '../features/books/hooks'
import { useCategories } from '../features/categories/hooks'
import { useDebounce } from '../hooks/useDebounce'
import { BookCard } from '../components/shared/BookCard'
import { Pagination } from '../components/shared/Pagination'
import { Spinner } from '../components/ui/Spinner'
import { Alert } from '../components/ui/Alert'
import type { BookListParams } from '../types/book'

type BookSort = NonNullable<BookListParams['sort']>

const LIMIT = 12

export function BooksPage() {
  const [search, setSearch] = useState('')
  const [category, setCategory] = useState('')
  const [sort, setSort] = useState<BookSort>('newest')
  const [page, setPage] = useState(1)

  const debouncedSearch = useDebounce(search, 300)

  const { data: categories } = useCategories()
  const booksQuery = useBooks({
    q: debouncedSearch,
    category: category || undefined,
    sort,
    page,
    limit: LIMIT,
  })

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, category, sort])

  const meta = booksQuery.data?.meta

  return (
    <div className="mx-auto w-full max-w-6xl px-4 py-12">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Catalog</h1>
          <p className="mt-1 text-slate-600">
            Browse legally free, public-domain literature.
          </p>
        </div>
        <Link to="/" className="btn-secondary">
          Back home
        </Link>
      </div>

      <div className="mt-8 grid gap-3 sm:grid-cols-[1fr_200px_200px]">
        <input
          type="search"
          className="input"
          placeholder="Search by title or author…"
          aria-label="Search the catalog"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
        <select
          className="input"
          aria-label="Filter by category"
          value={category}
          onChange={(event) => setCategory(event.target.value)}
        >
          <option value="">All categories</option>
          {(categories ?? []).map((item) => (
            <option key={item.id} value={item.slug}>
              {item.name}
            </option>
          ))}
        </select>
        <select
          className="input"
          aria-label="Sort books"
          value={sort}
          onChange={(event) => setSort(event.target.value as BookSort)}
        >
          <option value="newest">Newest</option>
          <option value="popular">Most popular</option>
          <option value="title_asc">Title A–Z</option>
          <option value="title_desc">Title Z–A</option>
          <option value="oldest">Oldest</option>
        </select>
      </div>

      <div className="mt-8">
        {booksQuery.isPending ? (
          <div className="flex justify-center py-16">
            <Spinner className="h-8 w-8" />
          </div>
        ) : booksQuery.isError ? (
          <Alert variant="error">Failed to load the catalog. Please try again.</Alert>
        ) : (booksQuery.data?.data.length ?? 0) === 0 ? (
          <div className="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
            <p className="text-slate-500">No books match your search.</p>
            <button
              type="button"
              className="btn-secondary mt-4"
              onClick={() => {
                setSearch('')
                setCategory('')
                setSort('newest')
              }}
            >
              Clear filters
            </button>
          </div>
        ) : (
          <>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {(booksQuery.data?.data ?? []).map((book) => (
                <BookCard key={book.id} book={book} />
              ))}
            </div>
            {meta ? <Pagination meta={meta} onPageChange={setPage} /> : null}
          </>
        )}
      </div>
    </div>
  )
}
