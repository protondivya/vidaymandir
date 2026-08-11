import { createBrowserRouter } from 'react-router-dom'
import { PublicLayout } from '../layouts/PublicLayout'
import { AuthLayout } from '../layouts/AuthLayout'
import { AdminLayout } from '../layouts/AdminLayout'
import { RequireAuth } from '../components/guards/RequireAuth'
import { RequireGuest } from '../components/guards/RequireGuest'
import { RequireRole } from '../components/guards/RequireRole'
import { HomePage } from '../pages/HomePage'
import { BooksPage } from '../pages/BooksPage'
import { BookDetailPage } from '../pages/BookDetailPage'
import { AccountPage } from '../pages/AccountPage'
import { AdminBooksPage } from '../features/admin/pages/AdminBooksPage'
import { AdminBookFormPage } from '../features/admin/pages/AdminBookFormPage'
import { AdminCategoriesPage } from '../features/admin/pages/AdminCategoriesPage'
import { AdminAuthorsPage } from '../features/admin/pages/AdminAuthorsPage'
import { LoginPage } from '../features/auth/pages/LoginPage'
import { RegisterPage } from '../features/auth/pages/RegisterPage'
import { ForgotPasswordPage } from '../features/auth/pages/ForgotPasswordPage'
import { ResetPasswordPage } from '../features/auth/pages/ResetPasswordPage'
import { VerifyEmailPage } from '../features/auth/pages/VerifyEmailPage'

export const router = createBrowserRouter([
  {
    element: <PublicLayout />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'books', element: <BooksPage /> },
      { path: 'books/:slug', element: <BookDetailPage /> },
    ],
  },
  {
    element: <RequireGuest />,
    children: [
      {
        element: <AuthLayout />,
        children: [
          { path: 'login', element: <LoginPage /> },
          { path: 'register', element: <RegisterPage /> },
          { path: 'forgot-password', element: <ForgotPasswordPage /> },
          { path: 'reset-password', element: <ResetPasswordPage /> },
        ],
      },
    ],
  },
  {
    element: <AuthLayout />,
    children: [{ path: 'verify-email', element: <VerifyEmailPage /> }],
  },
  {
    element: <RequireAuth />,
    children: [{ path: 'account', element: <AccountPage /> }],
  },
  {
    element: <RequireRole roles={['librarian', 'admin']} />,
    children: [
      {
        element: <AdminLayout />,
        children: [
          { path: 'admin/books', element: <AdminBooksPage /> },
          { path: 'admin/books/new', element: <AdminBookFormPage /> },
          { path: 'admin/books/:id/edit', element: <AdminBookFormPage /> },
          { path: 'admin/categories', element: <AdminCategoriesPage /> },
          { path: 'admin/authors', element: <AdminAuthorsPage /> },
        ],
      },
    ],
  },
])
