import { Link } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useState } from 'react'
import { TextInput } from '../../../components/ui/TextInput'
import { Alert } from '../../../components/ui/Alert'
import { Spinner } from '../../../components/ui/Spinner'
import { useSendPasswordResetLink } from '../hooks'
import { getApiErrorMessage } from '../../../lib/apiError'

const forgotPasswordSchema = z.object({
  email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
})

type ForgotPasswordFormValues = z.infer<typeof forgotPasswordSchema>

export function ForgotPasswordPage() {
  const sendResetLink = useSendPasswordResetLink()
  const [submittedEmail, setSubmittedEmail] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ForgotPasswordFormValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  })

  const onSubmit = (values: ForgotPasswordFormValues) => {
    sendResetLink.mutate(values.email, {
      onSuccess: () => setSubmittedEmail(values.email),
    })
  }

  if (submittedEmail) {
    return (
      <div>
        <h1 className="text-xl font-semibold text-slate-900">Check your email</h1>
        <Alert variant="success" className="mt-4">
          If an account exists for <span className="font-medium">{submittedEmail}</span>, a
          password reset link has been sent. The link expires in 60 minutes.
        </Alert>
        <p className="mt-6 text-center text-sm text-slate-500">
          <Link to="/login" className="text-brand-600 hover:underline">
            Back to sign in
          </Link>
        </p>
      </div>
    )
  }

  return (
    <div>
      <h1 className="text-xl font-semibold text-slate-900">Reset your password</h1>
      <p className="mt-1 text-sm text-slate-500">
        Enter your email address and we will send you a reset link.
      </p>

      {sendResetLink.isError ? (
        <Alert variant="error" className="mt-4">{getApiErrorMessage(sendResetLink.error)}</Alert>
      ) : null}

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

        <button type="submit" className="btn-primary w-full" disabled={sendResetLink.isPending}>
          {sendResetLink.isPending ? <Spinner /> : null}
          Send reset link
        </button>
      </form>

      <p className="mt-6 text-center text-sm text-slate-500">
        <Link to="/login" className="text-brand-600 hover:underline">
          Back to sign in
        </Link>
      </p>
    </div>
  )
}
