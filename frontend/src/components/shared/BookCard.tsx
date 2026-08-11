import { Link } from 'react-router-dom'
import type { Book } from '../../types/book'

export function BookCard({ book }: { book: Book }) {
  const authors = (book.authors ?? []).map((author) => author.name).join(', ')

  return (
    <Link
      to={`/books/${book.slug}`}
      className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
    >
      <div className="flex h-40 items-center justify-center rounded-lg bg-slate-100 text-slate-300">
        <span className="text-4xl font-bold">B</span>
      </div>
      <h3 className="mt-4 line-clamp-2 text-base font-semibold text-slate-900 group-hover:text-brand-700">
        {book.title}
      </h3>
      {authors ? <p className="mt-1 text-sm text-slate-500">{authors}</p> : null}
      <div className="mt-auto flex items-center justify-between pt-3 text-xs text-slate-400">
        <span className="uppercase">{book.language}</span>
        <span>{book.view_count.toLocaleString()} views</span>
      </div>
    </Link>
  )
}
