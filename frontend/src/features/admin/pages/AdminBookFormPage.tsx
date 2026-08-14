import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useBook, useCreateBook, useLicenseTypes, useUpdateBook } from '../../books/hooks'
import { useAuthors } from '../../authors/hooks'
import { useCategories } from '../../categories/hooks'
import { useUploadBookPdf } from '../../reader/hooks'
import { TextInput } from '../../../components/ui/TextInput'
import { TextArea } from '../../../components/ui/TextArea'
import { Select } from '../../../components/ui/Select'
import { Spinner } from '../../../components/ui/Spinner'
import { Alert } from '../../../components/ui/Alert'
import { getApiErrorMessage } from '../../../lib/apiError'
import type { Category } from '../../../types/book'

const bookFormSchema = z.object({
  title: z.string().min(1, 'Title is required').max(255),
  synopsis: z.string().optional(),
  language: z.string().regex(/^[a-z]{2}$/i, 'Use a two-letter language code'),
  page_count: z
    .union([z.literal(''), z.string().regex(/^\d+$/, 'Enter a whole number')])
    .optional(),
  word_count: z
    .union([z.literal(''), z.string().regex(/^\d+$/, 'Enter a whole number')])
    .optional(),
  cover_image_url: z
    .string()
    .refine((value) => value === '' || z.string().url().safeParse(value).success, {
      message: 'Enter a valid URL',
    })
    .optional(),
  rights_source: z.string().optional(),
  is_downloadable: z.boolean(),
  license_type_id: z.string().min(1, 'Select a license type'),
  status: z.enum(['draft', 'active', 'deactivated']),
  authors: z.array(z.string()).min(1, 'Select at least one author'),
  categories: z.array(z.string()).optional(),
})

type BookFormValues = z.infer<typeof bookFormSchema>

function flattenCategories(categories: Category[], depth = 0): Category[] {
  return categories.flatMap((category) => [
    { ...category },
    ...flattenCategories(category.children ?? [], depth + 1),
  ])
}

export function AdminBookFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEdit = id !== undefined

  const bookQuery = useBook(isEdit ? Number(id) : undefined)
  const { data: licenseTypes } = useLicenseTypes()
  const { data: authorsData } = useAuthors({ limit: 100 })
  const { data: categories } = useCategories()
  const uploadPdf = useUploadBookPdf()

  const pdfInputRef = useRef<HTMLInputElement>(null)
  const [pdfFile, setPdfFile] = useState<File | null>(null)

  const createBook = useCreateBook()
  const updateBook = useUpdateBook()

  const authors = authorsData?.data ?? []
  const categoryOptions = useMemo(() => flattenCategories(categories ?? []), [categories])

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<BookFormValues>({
    resolver: zodResolver(bookFormSchema),
    defaultValues: {
      title: '',
      synopsis: '',
      language: 'en',
      page_count: '',
      word_count: '',
      cover_image_url: '',
      rights_source: '',
      is_downloadable: true,
      license_type_id: '',
      status: 'draft',
      authors: [],
      categories: [],
    },
  })

  useEffect(() => {
    if (isEdit && bookQuery.data) {
      reset({
        title: bookQuery.data.title,
        synopsis: bookQuery.data.synopsis ?? '',
        language: bookQuery.data.language,
        page_count: bookQuery.data.page_count ? String(bookQuery.data.page_count) : '',
        word_count: bookQuery.data.word_count ? String(bookQuery.data.word_count) : '',
        cover_image_url: bookQuery.data.cover_image_url ?? '',
        rights_source: bookQuery.data.rights_source ?? '',
        is_downloadable: bookQuery.data.is_downloadable,
        license_type_id: bookQuery.data.license_type?.id ? String(bookQuery.data.license_type.id) : '',
        status: bookQuery.data.status,
        authors: (bookQuery.data.authors ?? []).map((author) => String(author.id)),
        categories: (bookQuery.data.categories ?? []).map((category) => String(category.id)),
      })
    }
  }, [isEdit, bookQuery.data, reset])

  const onSubmit = (values: BookFormValues) => {
    const input = {
      title: values.title,
      synopsis: values.synopsis || null,
      language: values.language.toLowerCase(),
      page_count: values.page_count ? Number(values.page_count) : null,
      word_count: values.word_count ? Number(values.word_count) : null,
      cover_image_url: values.cover_image_url || null,
      rights_source: values.rights_source || null,
      is_downloadable: values.is_downloadable,
      license_type_id: Number(values.license_type_id),
      status: values.status,
      authors: values.authors.map(Number),
      categories: (values.categories ?? []).map(Number),
    }

    if (isEdit) {
      updateBook.mutate(
        { id: Number(id), input },
        {
          onSuccess: (book) => {
            if (pdfFile !== null) {
              uploadPdf.mutate({ id: book.id, file: pdfFile })
            }
            navigate('/admin/books')
          },
        },
      )
    } else {
      createBook.mutate(input, {
        onSuccess: (book) => {
          if (pdfFile !== null) {
            uploadPdf.mutate({ id: book.id, file: pdfFile })
          }
          navigate('/admin/books')
        },
      })
    }
  }

  if (isEdit && bookQuery.isPending) {
    return (
      <div className="flex justify-center py-16">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (isEdit && bookQuery.isError) {
    return <Alert variant="error">Unable to load this book.</Alert>
  }

  const isSaving = createBook.isPending || updateBook.isPending

  return (
    <div className="mx-auto max-w-3xl">
      <h1 className="text-2xl font-bold text-slate-900">
        {isEdit ? 'Edit book' : 'Add book'}
      </h1>

      {(createBook.isError || updateBook.isError) ? (
        <Alert variant="error" className="mt-4">
          {getApiErrorMessage(createBook.error ?? updateBook.error)}
        </Alert>
      ) : null}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <TextInput
          id="title"
          label="Title"
          placeholder="The Great Gatsby"
          error={errors.title?.message}
          {...register('title')}
        />
        <TextArea
          id="synopsis"
          label="Synopsis"
          placeholder="A short description of the book…"
          error={errors.synopsis?.message}
          {...register('synopsis')}
        />

        <div className="grid gap-4 sm:grid-cols-3">
          <TextInput
            id="language"
            label="Language (ISO 639-1)"
            placeholder="en"
            error={errors.language?.message}
            {...register('language')}
          />
          <TextInput
            id="page_count"
            label="Page count"
            type="number"
            inputMode="numeric"
            error={errors.page_count?.message}
            {...register('page_count')}
          />
          <TextInput
            id="word_count"
            label="Word count"
            type="number"
            inputMode="numeric"
            error={errors.word_count?.message}
            {...register('word_count')}
          />
        </div>

        <TextInput
          id="cover_image_url"
          label="Cover image URL"
          placeholder="https://example.com/cover.jpg"
          error={errors.cover_image_url?.message}
          {...register('cover_image_url')}
        />
        <TextInput
          id="rights_source"
          label="Rights source"
          placeholder="e.g. Project Gutenberg"
          error={errors.rights_source?.message}
          {...register('rights_source')}
        />

        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
          <input
            type="checkbox"
            className="h-4 w-4 accent-brand-600"
            {...register('is_downloadable')}
          />
          <span className="text-sm text-slate-700">
            Allow readers to download this book (as a watermarked PDF)
          </span>
        </label>

        <div className="grid gap-4 sm:grid-cols-2">
          <Select
            id="license_type_id"
            label="License type"
            error={errors.license_type_id?.message}
            {...register('license_type_id')}
          >
            <option value="">Select a license…</option>
            {(licenseTypes ?? []).map((license) => (
              <option key={license.id} value={license.id}>
                {license.name}
              </option>
            ))}
          </Select>
          <Select id="status" label="Status" error={errors.status?.message} {...register('status')}>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="deactivated">Deactivated</option>
          </Select>
        </div>

        <div className="w-full">
          <label htmlFor="authors" className="field-label">
            Authors
          </label>
          <select
            id="authors"
            multiple
            size={6}
            aria-invalid={errors.authors ? true : undefined}
            className={`input ${errors.authors ? 'border-red-500' : ''}`}
            {...register('authors')}
          >
            {authors.map((author) => (
              <option key={author.id} value={author.id}>
                {author.name}
              </option>
            ))}
          </select>
          {errors.authors ? <p className="field-error">{errors.authors.message}</p> : null}
        </div>

        <div className="w-full">
          <span className="field-label">Categories</span>
          {categoryOptions.length === 0 ? (
            <p className="text-sm text-slate-500">
              No categories yet.{' '}
              <a href="/admin/categories" className="text-brand-600 hover:underline">
                Create one first
              </a>
            </p>
          ) : (
            <div className="grid gap-2 sm:grid-cols-2">
              {categoryOptions.map((category) => (
                <label
                  key={category.id}
                  className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                >
                  <input type="checkbox" value={category.id} className="accent-brand-600" {...register('categories')} />
                  {category.name}
                </label>
              ))}
            </div>
          )}
        </div>

        <div className="w-full rounded-xl border border-slate-200 bg-white p-4">
          <span className="field-label">PDF file</span>
          {isEdit && bookQuery.data?.has_pdf ? (
            <p className="mt-1 text-sm text-emerald-600">A PDF is currently uploaded.</p>
          ) : (
            <p className="mt-1 text-sm text-slate-500">No PDF uploaded yet.</p>
          )}

          <div className="mt-3 flex flex-wrap items-center gap-3">
            <input
              ref={pdfInputRef}
              type="file"
              accept="application/pdf,.pdf"
              className="block w-full max-w-sm text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
              onChange={(event) => setPdfFile(event.target.files?.[0] ?? null)}
            />
            {pdfFile !== null ? (
              <button
                type="button"
                className="btn-secondary"
                onClick={() => {
                  setPdfFile(null)
                  if (pdfInputRef.current) {
                    pdfInputRef.current.value = ''
                  }
                }}
              >
                Clear
              </button>
            ) : null}
          </div>
          {pdfFile !== null ? (
            <p className="mt-2 text-xs text-slate-500">
              {pdfFile.name} will be uploaded after the book is saved.
            </p>
          ) : null}
          {uploadPdf.isError ? (
            <p className="mt-2 text-sm text-red-600">The PDF could not be uploaded.</p>
          ) : null}
        </div>

        <div className="flex items-center gap-3 pt-2">
          <button type="submit" className="btn-primary" disabled={isSaving}>
            {isSaving ? <Spinner /> : null}
            {isEdit ? 'Save changes' : 'Create book'}
          </button>
          <button type="button" className="btn-secondary" onClick={() => navigate('/admin/books')}>
            Cancel
          </button>
        </div>
      </form>
    </div>
  )
}
