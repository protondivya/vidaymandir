import type { BookStatus } from '../../types/book'

const statusStyles: Record<BookStatus, string> = {
  draft: 'bg-slate-100 text-slate-600',
  active: 'bg-emerald-100 text-emerald-700',
  deactivated: 'bg-red-100 text-red-700',
}

export function StatusBadge({ status }: { status: BookStatus }) {
  return (
    <span className={`inline-block rounded px-2 py-0.5 text-xs font-medium capitalize ${statusStyles[status]}`}>
      {status}
    </span>
  )
}
