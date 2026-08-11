import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { TextInput } from '../../../components/ui/TextInput'
import { Alert } from '../../../components/ui/Alert'
import { Spinner } from '../../../components/ui/Spinner'
import { useLogin } from '../hooks'
import { getApiErrorMessage } from '../../../lib/apiError'

const loginSchema = z.object({
  email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
  password: z.string().min(1, 'Password is required'),
})

type LoginFormValues = z.infer<typeof loginSchema>

export function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const login = useLogin()

  const from = (location.state as { from?: string } | null)?.from ?? '/'
  const notice = (location.state as { message?: string } | null)?.message

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '' },
  })

  const onSubmit = (values: LoginFormValues) => {
    login.mutate(
      { email: values.email, password: values.password },
      {
        onSuccess: () => navigate(from, { replace: true }),
      },
    )
  }

  return (
    <div>
      <h1 className="text-xl font-semibold text-slate-900">Sign in</h1>
      <p className="mt-1 text-sm text-slate-500">
        Welcome back. Access your bookshelf and continue reading.
      </p>

      {notice ? <Alert variant="success" className="mt-4">{notice}</Alert> : null}
      {login.isError ? <Alert variant="error" className="mt-4">{getApiErrorMessage(login.error)}</Alert> : null}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <TextInput
          id="email"
          label="Email address"
          type="email"
          autoComplete="email"
          placeholder="you@example.com"
          error={errors.email?.message}
          {...register('email')}
        />
        <div>
          <TextInput
            id="password"
            label="Password"
            type="password"
            autoComplete="current-password"
            placeholder="Your password"
            error={errors.password?.message}
            {...register('password')}
          />
          <div className="mt-1 text-right">
            <Link to="/forgot-password" className="text-sm text-brand-600 hover:underline">
              Forgot password?
            </Link>
          </div>
        </div>

        <button type="submit" className="btn-primary w-full" disabled={login.isPending}>
          {login.isPending ? <Spinner /> : null}
          Sign in
        </button>
      </form>

      <p className="mt-6 text-center text-sm text-slate-500">
        New to the library?{' '}
        <Link to="/register" className="text-brand-600 hover:underline">
          Create an account
        </Link>
      </p>
    </div>
  )
}
