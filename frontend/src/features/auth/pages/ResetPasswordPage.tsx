import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { TextInput } from '../../../components/ui/TextInput'
import { Alert } from '../../../components/ui/Alert'
import { Spinner } from '../../../components/ui/Spinner'
import { useResetPassword } from '../hooks'
import { getApiErrorMessage, getApiFieldErrors } from '../../../lib/apiError'

const resetPasswordSchema = z
  .object({
    password: z
      .string()
      .min(8, 'Password must be at least 8 characters')
      .regex(/[a-zA-Z]/, 'Password must contain at least one letter')
      .regex(/[0-9]/, 'Password must contain at least one number'),
    password_confirmation: z.string(),
  })
  .refine((values) => values.password === values.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>

export function ResetPasswordPage() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const resetPassword = useResetPassword()

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<ResetPasswordFormValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: { password: '', password_confirmation: '' },
  })

  if (!token || !email) {
    return (
      <div>
        <h1 className="text-xl font-semibold text-slate-900">Invalid reset link</h1>
        <Alert variant="error" className="mt-4">
          This password reset link is missing required information. Please request a new one.
        </Alert>
        <p className="mt-6 text-center text-sm">
          <Link to="/forgot-password" className="text-brand-600 hover:underline">
            Request a new link
          </Link>
        </p>
      </div>
    )
  }

  const onSubmit = (values: ResetPasswordFormValues) => {
    resetPassword.mutate(
      {
        token,
        email,
        password: values.password,
        password_confirmation: values.password_confirmation,
      },
      {
        onSuccess: (message) =>
          navigate('/login', { replace: true, state: { message } }),
        onError: (error) => {
          const fieldErrors = getApiFieldErrors(error)
          const passwordErrors = fieldErrors.password
          if (passwordErrors?.length) {
            setError('password', { type: 'server', message: passwordErrors[0] })
          }
        },
      },
    )
  }

  return (
    <div>
      <h1 className="text-xl font-semibold text-slate-900">Choose a new password</h1>
      <p className="mt-1 text-sm text-slate-500">
        Enter a new password for <span className="font-medium">{email}</span>.
      </p>

      {resetPassword.isError && !Object.keys(getApiFieldErrors(resetPassword.error)).length ? (
        <Alert variant="error" className="mt-4">
          {getApiErrorMessage(resetPassword.error)}
        </Alert>
      ) : null}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <TextInput
          id="password"
          label="New password"
          type="password"
          autoComplete="new-password"
          placeholder="At least 8 characters, letters and numbers"
          error={errors.password?.message}
          {...register('password')}
        />
        <TextInput
          id="password_confirmation"
          label="Confirm new password"
          type="password"
          autoComplete="new-password"
          placeholder="Repeat your new password"
          error={errors.password_confirmation?.message}
          {...register('password_confirmation')}
        />

        <button type="submit" className="btn-primary w-full" disabled={resetPassword.isPending}>
          {resetPassword.isPending ? <Spinner /> : null}
          Reset password
        </button>
      </form>
    </div>
  )
}
