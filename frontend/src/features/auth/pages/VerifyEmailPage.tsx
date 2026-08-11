import { Link, useSearchParams } from 'react-router-dom'
import { useEffect } from 'react'
import { Alert } from '../../../components/ui/Alert'
import { Spinner } from '../../../components/ui/Spinner'
import { useVerifyEmail } from '../hooks'
import { getApiErrorMessage } from '../../../lib/apiError'

export function VerifyEmailPage() {
  const [searchParams] = useSearchParams()
  const verify = useVerifyEmail()

  const params = {
    id: searchParams.get('id') ?? '',
    hash: searchParams.get('hash') ?? '',
    expires: searchParams.get('expires') ?? '',
    signature: searchParams.get('signature') ?? '',
  }

  const hasRequiredParams = Boolean(params.id && params.hash && params.expires && params.signature)

  useEffect(() => {
    if (hasRequiredParams && verify.isIdle) {
      verify.mutate(params)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hasRequiredParams])

  if (!hasRequiredParams) {
    return (
      <div>
        <h1 className="text-xl font-semibold text-slate-900">Invalid verification link</h1>
        <Alert variant="error" className="mt-4">
          This email verification link is invalid or incomplete. Please request a new one.
        </Alert>
        <p className="mt-6 text-center text-sm">
          <Link to="/login" className="text-brand-600 hover:underline">
            Back to sign in
          </Link>
        </p>
      </div>
    )
  }

  if (verify.isPending) {
    return (
      <div className="flex flex-col items-center justify-center gap-4 py-8">
        <Spinner className="h-6 w-6 text-brand-600" />
        <p className="text-sm text-slate-500">Verifying your email address…</p>
      </div>
    )
  }

  if (verify.isError) {
    return (
      <div>
        <h1 className="text-xl font-semibold text-slate-900">Verification failed</h1>
        <Alert variant="error" className="mt-4">
          {getApiErrorMessage(verify.error)}
        </Alert>
        <p className="mt-6 text-center text-sm">
          <Link to="/login" className="text-brand-600 hover:underline">
            Back to sign in
          </Link>
        </p>
      </div>
    )
  }

  return (
    <div>
      <h1 className="text-xl font-semibold text-slate-900">Email verified</h1>
      <Alert variant="success" className="mt-4">
        {verify.data}
      </Alert>
      <p className="mt-6 text-center text-sm">
        <Link to="/login" className="text-brand-600 hover:underline">
          Continue to sign in
        </Link>
      </p>
    </div>
  )
}
