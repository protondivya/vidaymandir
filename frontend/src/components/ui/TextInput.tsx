import { forwardRef } from 'react'
import type { InputHTMLAttributes } from 'react'

interface TextInputProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string
  error?: string
}

export const TextInput = forwardRef<HTMLInputElement, TextInputProps>(
  function TextInput({ label, error, id, className = '', ...props }, ref) {
    return (
      <div className="w-full">
        <label htmlFor={id} className="field-label">
          {label}
        </label>
        <input
          ref={ref}
          id={id}
          aria-invalid={error ? true : undefined}
          className={`input ${error ? 'border-red-500' : ''} ${className}`}
          {...props}
        />
        {error ? <p className="field-error">{error}</p> : null}
      </div>
    )
  },
)
