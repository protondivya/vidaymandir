import type { PaginationMeta } from '../../types/book'

interface PaginationProps {
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

export function Pagination({ meta, onPageChange }: PaginationProps) {
  if (meta.last_page <= 1) {
    return null
  }

  const pages: number[] = []
  const start = Math.max(1, meta.page - 2)
  const end = Math.min(meta.last_page, start + 4)

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  return (
    <nav aria-label="Pagination" className="mt-8 flex items-center justify-center gap-1">
      <button
        type="button"
        className="btn-secondary"
        disabled={meta.page <= 1}
        onClick={() => onPageChange(meta.page - 1)}
      >
        Previous
      </button>
      {pages.map((page) => (
        <button
          key={page}
          type="button"
          className={
            page === meta.page
              ? 'btn-primary'
              : 'btn-secondary'
          }
          onClick={() => onPageChange(page)}
        >
          {page}
        </button>
      ))}
      <button
        type="button"
        className="btn-secondary"
        disabled={meta.page >= meta.last_page}
        onClick={() => onPageChange(meta.page + 1)}
      >
        Next
      </button>
    </nav>
  )
}
