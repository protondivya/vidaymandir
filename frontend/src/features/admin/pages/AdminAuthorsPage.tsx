import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import {
  useAuthors,
  useCreateAuthor,
  useDeleteAuthor,
  useUpdateAuthor,
} from '../../authors/hooks'
import { useDebounce } from '../../../hooks/useDebounce'
import { Pagination } from '../../../components/shared/Pagination'
import { TextInput } from '../../../components/ui/TextInput'
import { TextArea } from '../../../components/ui/TextArea'
import { Spinner } from '../../../components/ui/Spinner'
import { Alert } from '../../../components/ui/Alert'
import { getApiErrorMessage } from '../../../lib/apiError'
import type { Author } from '../../../types/book'

const LIMIT = 20

const authorFormSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  birth_year: z
    .union([z.literal(''), z.string().regex(/^\d{1,4}$/, 'Enter a valid year')])
    .optional(),
  death_year: z
    .union([z.literal(''), z.string().regex(/^\d{1,4}$/, 'Enter a valid year')])
    .optional(),
  bio: z.string().optional(),
})

type AuthorFormValues = z.infer<typeof authorFormSchema>

export function AdminAuthorsPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState<Author | null>(null)

  const debouncedSearch = useDebounce(search, 300)

  const authorsQuery = useAuthors({ q: debouncedSearch, page, limit: LIMIT })
  const createAuthor = useCreateAuthor()
  const updateAuthor = useUpdateAuthor()
  const deleteAuthor = useDeleteAuthor()

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<AuthorFormValues>({
    resolver: zodResolver(authorFormSchema),
    defaultValues: { name: '', birth_year: '', death_year: '', bio: '' },
  })

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  useEffect(() => {
    if (editing) {
      reset({
        name: editing.name,
        birth_year: editing.birth_year ? String(editing.birth_year) : '',
        death_year: editing.death_year ? String(editing.death_year) : '',
        bio: editing.bio ?? '',
      })
    } else {
      reset({ name: '', birth_year: '', death_year: '', bio: '' })
    }
  }, [editing, reset])

  const isSaving = createAuthor.isPending || updateAuthor.isPending

  const onSubmit = (values: AuthorFormValues) => {
    const input = {
      name: values.name,
      birth_year: values.birth_year ? Number(values.birth_year) : null,
      death_year: values.death_year ? Number(values.death_year) : null,
      bio: values.bio || null,
    }

    if (editing) {
      updateAuthor.mutate(
        { id: editing.id, input },
        { onSuccess: () => setEditing(null) },
      )
    } else {
      createAuthor.mutate(input, {
        onSuccess: () => reset({ name: '', birth_year: '', death_year: '', bio: '' }),
      })
    }
  }

  const handleDelete = (author: Author) => {
    if (!window.confirm(`Delete the author "${author.name}"?`)) {
      return
    }

    deleteAuthor.mutate(author.id)
  }

  const mutationError = createAuthor.error ?? updateAuthor.error ?? deleteAuthor.error

  return (
    <div>
      <h1 className="text-2xl font-bold text-slate-900">Authors</h1>
      <p className="mt-1 text-sm text-slate-500">
        Manage the catalog's author records.
      </p>

      {mutationError ? (
        <Alert variant="error" className="mt-4">{getApiErrorMessage(mutationError)}</Alert>
      ) : null}

      <form className="mt-6 rounded-xl border border-slate-200 bg-white p-5" onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className="grid gap-4 sm:grid-cols-3">
          <TextInput
            id="name"
            label={editing ? 'Edit author name' : 'Author name'}
            placeholder="e.g. Jane Austen"
            error={errors.name?.message}
            {...register('name')}
          />
          <TextInput
            id="birth_year"
            label="Birth year"
            type="number"
            inputMode="numeric"
            error={errors.birth_year?.message}
            {...register('birth_year')}
          />
          <TextInput
            id="death_year"
            label="Death year"
            type="number"
            inputMode="numeric"
            error={errors.death_year?.message}
            {...register('death_year')}
          />
        </div>
        <TextArea
          id="bio"
          label="Biography"
          className="mt-4"
          error={errors.bio?.message}
          {...register('bio')}
        />
        <div className="mt-4 flex items-center gap-3">
          <button type="submit" className="btn-primary" disabled={isSaving}>
            {isSaving ? <Spinner /> : null}
            {editing ? 'Save changes' : 'Add author'}
          </button>
          {editing ? (
            <button type="button" className="btn-secondary" onClick={() => setEditing(null)}>
              Cancel
            </button>
          ) : null}
        </div>
      </form>

      <div className="mt-6 flex">
        <input
          type="search"
          className="input"
          placeholder="Search authors…"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
      </div>

      <div className="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
        {authorsQuery.isPending ? (
          <div className="flex justify-center py-16">
            <Spinner className="h-8 w-8" />
          </div>
        ) : (authorsQuery.data?.data.length ?? 0) === 0 ? (
          <p className="py-16 text-center text-sm text-slate-500">No authors found.</p>
        ) : (
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">Years</th>
                <th className="px-4 py-3 font-medium">Books</th>
                <th className="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {(authorsQuery.data?.data ?? []).map((author) => (
                <tr key={author.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-medium text-slate-900">{author.name}</td>
                  <td className="px-4 py-3 text-slate-500">
                    {author.birth_year ? `${author.birth_year}–${author.death_year ?? '…'}` : '—'}
                  </td>
                  <td className="px-4 py-3 text-slate-600">{author.books_count ?? 0}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex gap-2">
                      <button
                        type="button"
                        className="btn-secondary !px-3 !py-1"
                        onClick={() => setEditing(author)}
                      >
                        Edit
                      </button>
                      <button
                        type="button"
                        className="btn-secondary !px-3 !py-1 text-red-600"
                        disabled={deleteAuthor.isPending}
                        onClick={() => handleDelete(author)}
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {authorsQuery.data?.meta ? (
        <Pagination meta={authorsQuery.data.meta} onPageChange={setPage} />
      ) : null}
    </div>
  )
}
