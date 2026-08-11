import { forwardRef } from 'react'
import type { SelectHTMLAttributes } from 'react'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label: string
  error?: string
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  function Select({ label, error, id, className = '', children, ...props }, ref) {
    return (
      <div className="w-full">
        <label htmlFor={id} className="field-label">
          {label}
        </label>
        <select
          ref={ref}
          id={id}
          aria-invalid={error ? true : undefined}
          className={`input ${error ? 'border-red-500' : ''} ${className}`}
          {...props}
        >
          {children}
        </select>
        {error ? <p className="field-error">{error}</p> : null}
      </div>
    )
  },
)
