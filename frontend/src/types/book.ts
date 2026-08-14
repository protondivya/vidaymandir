export type BookStatus = 'draft' | 'active' | 'deactivated'

export interface Author {
  id: number
  name: string
  bio: string | null
  birth_year: number | null
  death_year: number | null
  books_count?: number
}

export interface Category {
  id: number
  parent_id: number | null
  name: string
  slug: string
  description: string | null
  books_count?: number
  children?: Category[]
}

export interface LicenseType {
  id: number
  code: string
  name: string
  description?: string | null
}

export interface BookAuthor {
  id: number
  name: string
}

export interface BookCategory {
  id: number
  name: string
  slug: string
}

export interface Book {
  id: number
  title: string
  slug: string
  synopsis: string | null
  language: string
  page_count: number | null
  word_count: number | null
  cover_image_url: string | null
  has_pdf: boolean
  is_downloadable: boolean
  rights_source: string | null
  status: BookStatus
  view_count: number
  published_at: string | null
  created_at: string | null
  updated_at: string | null
  license_type?: LicenseType | null
  created_by?: number
  authors?: BookAuthor[]
  categories?: BookCategory[]
}

export interface ReadingProgress {
  book_id: number
  current_page: number | null
  percent: number | null
  updated_at: string | null
}

export interface PaginationMeta {
  total: number
  page: number
  limit: number
  last_page: number
}

export interface PaginatedResponse<T> {
  success: boolean
  data: T[]
  meta: PaginationMeta
}

export interface BookListParams {
  q?: string
  category?: string
  language?: string
  sort?: 'newest' | 'popular' | 'title_asc' | 'title_desc' | 'oldest'
  page?: number
  limit?: number
}

export interface BookInput {
  title: string
  slug?: string
  synopsis?: string | null
  language: string
  page_count?: number | null
  word_count?: number | null
  cover_image_url?: string | null
  is_downloadable?: boolean
  license_type_id: number
  rights_source?: string | null
  status?: BookStatus
  authors: number[]
  categories?: number[]
}

export interface CategoryInput {
  name: string
  slug?: string
  parent_id?: number | null
  description?: string | null
}

export interface AuthorInput {
  name: string
  bio?: string | null
  birth_year?: number | null
  death_year?: number | null
}
