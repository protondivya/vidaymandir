import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import {
  useCategories,
  useCreateCategory,
  useDeleteCategory,
  useUpdateCategory,
} from '../../categories/hooks'
import { TextInput } from '../../../components/ui/TextInput'
import { TextArea } from '../../../components/ui/TextArea'
import { Select } from '../../../components/ui/Select'
import { Spinner } from '../../../components/ui/Spinner'
import { Alert } from '../../../components/ui/Alert'
import { getApiErrorMessage } from '../../../lib/apiError'
import type { Category } from '../../../types/book'

const categoryFormSchema = z.object({
  name: z.string().min(1, 'Name is required').max(120),
  parent_id: z.string().optional(),
  description: z.string().optional(),
})

type CategoryFormValues = z.infer<typeof categoryFormSchema>

interface FlattenedCategory extends Category {
  depth: number
}

function flattenTree(categories: Category[], depth = 0): FlattenedCategory[] {
  return categories.flatMap((category) => [
    { ...category, depth },
    ...flattenTree(category.children ?? [], depth + 1),
  ])
}

export function AdminCategoriesPage() {
  const categoriesQuery = useCategories()
  const createCategory = useCreateCategory()
  const updateCategory = useUpdateCategory()
  const deleteCategory = useDeleteCategory()

  const [editing, setEditing] = useState<Category | null>(null)

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<CategoryFormValues>({
    resolver: zodResolver(categoryFormSchema),
    defaultValues: { name: '', parent_id: '', description: '' },
  })

  useEffect(() => {
    if (editing) {
      reset({
        name: editing.name,
        parent_id: editing.parent_id ? String(editing.parent_id) : '',
        description: editing.description ?? '',
      })
    } else {
      reset({ name: '', parent_id: '', description: '' })
    }
  }, [editing, reset])

  const rows = flattenTree(categoriesQuery.data ?? [])
  const isSaving = createCategory.isPending || updateCategory.isPending

  const onSubmit = (values: CategoryFormValues) => {
    const input = {
      name: values.name,
      parent_id: values.parent_id ? Number(values.parent_id) : null,
      description: values.description || null,
    }

    if (editing) {
      updateCategory.mutate(
        { id: editing.id, input },
        { onSuccess: () => setEditing(null) },
      )
    } else {
      createCategory.mutate(input, { onSuccess: () => reset({ name: '', parent_id: '', description: '' }) })
    }
  }

  const handleDelete = (category: Category) => {
    if (!window.confirm(`Delete the category "${category.name}"?`)) {
      return
    }

    deleteCategory.mutate(category.id)
  }

  const mutationError = createCategory.error ?? updateCategory.error ?? deleteCategory.error

  return (
    <div>
      <h1 className="text-2xl font-bold text-slate-900">Categories</h1>
      <p className="mt-1 text-sm text-slate-500">
        Organize the catalog into a nested tree.
      </p>

      {mutationError ? (
        <Alert variant="error" className="mt-4">{getApiErrorMessage(mutationError)}</Alert>
      ) : null}

      <form className="mt-6 rounded-xl border border-slate-200 bg-white p-5" onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className="grid gap-4 sm:grid-cols-[1fr_200px]">
          <TextInput
            id="name"
            label={editing ? 'Edit category name' : 'Category name'}
            placeholder="e.g. Science Fiction"
            error={errors.name?.message}
            {...register('name')}
          />
          <Select
            id="parent_id"
            label="Parent"
            error={errors.parent_id?.message}
            {...register('parent_id')}
          >
            <option value="">(none — top level)</option>
            {rows
              .filter((row) => row.id !== editing?.id)
              .map((row) => (
                <option key={row.id} value={row.id}>
                  {'\u00A0'.repeat(row.depth * 2)}{row.name}
                </option>
              ))}
          </Select>
        </div>
        <TextArea
          id="description"
          label="Description"
          className="mt-4"
          error={errors.description?.message}
          {...register('description')}
        />
        <div className="mt-4 flex items-center gap-3">
          <button type="submit" className="btn-primary" disabled={isSaving}>
            {isSaving ? <Spinner /> : null}
            {editing ? 'Save changes' : 'Add category'}
          </button>
          {editing ? (
            <button type="button" className="btn-secondary" onClick={() => setEditing(null)}>
              Cancel
            </button>
          ) : null}
        </div>
      </form>

      <div className="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        {categoriesQuery.isPending ? (
          <div className="flex justify-center py-16">
            <Spinner className="h-8 w-8" />
          </div>
        ) : rows.length === 0 ? (
          <p className="py-16 text-center text-sm text-slate-500">
            No categories yet. Add your first one above.
          </p>
        ) : (
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">Slug</th>
                <th className="px-4 py-3 font-medium">Books</th>
                <th className="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {rows.map((row) => (
                <tr key={row.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-medium text-slate-900">
                    <span style={{ paddingLeft: `${row.depth * 1.25}rem` }}>
                      {row.depth > 0 ? '↳ ' : ''}
                      {row.name}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-slate-500">{row.slug}</td>
                  <td className="px-4 py-3 text-slate-600">{row.books_count ?? 0}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex gap-2">
                      <button
                        type="button"
                        className="btn-secondary !px-3 !py-1"
                        onClick={() => setEditing(row)}
                      >
                        Edit
                      </button>
                      <button
                        type="button"
                        className="btn-secondary !px-3 !py-1 text-red-600"
                        disabled={deleteCategory.isPending}
                        onClick={() => handleDelete(row)}
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
    </div>
  )
}
