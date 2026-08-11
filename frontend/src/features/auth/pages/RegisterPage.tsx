import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { TextInput } from '../../../components/ui/TextInput'
import { Alert } from '../../../components/ui/Alert'
import { Spinner } from '../../../components/ui/Spinner'
import { useRegister } from '../hooks'
import { getApiErrorMessage, getApiFieldErrors } from '../../../lib/apiError'

const registerSchema = z
  .object({
    display_name: z.string().min(2, 'Name must be at least 2 characters').max(120),
    email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
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

type RegisterFormValues = z.infer<typeof registerSchema>

export function RegisterPage() {
  const navigate = useNavigate()
  const register = useRegister()

  const {
    register: registerField,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<RegisterFormValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      display_name: '',
      email: '',
      password: '',
      password_confirmation: '',
    },
  })

  const onSubmit = (values: RegisterFormValues) => {
    register.mutate(
      {
        display_name: values.display_name,
        email: values.email,
        password: values.password,
      },
      {
        onSuccess: (result) =>
          navigate('/login', {
            replace: true,
            state: { message: result.message },
          }),
        onError: (error) => {
          const fieldErrors = getApiFieldErrors(error)
          const map: Record<string, keyof RegisterFormValues> = {
            display_name: 'display_name',
            email: 'email',
            password: 'password',
          }

          for (const [field, path] of Object.entries(map)) {
            const messages = fieldErrors[field]
            if (messages?.length) setError(path, { type: 'server', message: messages[0] })
          }
        },
      },
    )
  }

  return (
    <div>
      <h1 className="text-xl font-semibold text-slate-900">Create your account</h1>
      <p className="mt-1 text-sm text-slate-500">
        Free and open for everyone. Verify your email to unlock favorites.
      </p>

      {register.isError && !Object.keys(getApiFieldErrors(register.error)).length ? (
        <Alert variant="error" className="mt-4">{getApiErrorMessage(register.error)}</Alert>
      ) : null}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <TextInput
          id="display_name"
          label="Display name"
          type="text"
          autoComplete="name"
          placeholder="Jane Reader"
          error={errors.display_name?.message}
          {...registerField('display_name')}
        />
        <TextInput
          id="email"
          label="Email address"
          type="email"
          autoComplete="email"
          placeholder="you@example.com"
          error={errors.email?.message}
          {...registerField('email')}
        />
        <TextInput
          id="password"
          label="Password"
          type="password"
          autoComplete="new-password"
          placeholder="At least 8 characters, letters and numbers"
          error={errors.password?.message}
          {...registerField('password')}
        />
        <TextInput
          id="password_confirmation"
          label="Confirm password"
          type="password"
          autoComplete="new-password"
          placeholder="Repeat your password"
          error={errors.password_confirmation?.message}
          {...registerField('password_confirmation')}
        />

        <button type="submit" className="btn-primary w-full" disabled={register.isPending}>
          {register.isPending ? <Spinner /> : null}
          Create account
        </button>
      </form>

      <p className="mt-6 text-center text-sm text-slate-500">
        Already have an account?{' '}
        <Link to="/login" className="text-brand-600 hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  )
}
