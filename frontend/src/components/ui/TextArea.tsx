import { forwardRef } from 'react'
import type { TextareaHTMLAttributes } from 'react'

interface TextAreaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label: string
  error?: string
}

export const TextArea = forwardRef<HTMLTextAreaElement, TextAreaProps>(
  function TextArea({ label, error, id, className = '', ...props }, ref) {
    return (
      <div className="w-full">
        <label htmlFor={id} className="field-label">
          {label}
        </label>
        <textarea
          ref={ref}
          id={id}
          aria-invalid={error ? true : undefined}
          className={`input min-h-24 ${error ? 'border-red-500' : ''} ${className}`}
          {...props}
        />
        {error ? <p className="field-error">{error}</p> : null}
      </div>
    )
  },
)
