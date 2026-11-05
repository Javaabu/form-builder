"use client"

import * as React from "react"
import type { StencilComponentProps } from "@javaabu/stencil"

/** `form-builder-content`: a heading or a paragraph between fields */
export function FormBuilderContent({ node }: StencilComponentProps): React.JSX.Element | null {
  const text = node.props?.text as string | undefined
  if (!text) return null

  if (node.props?.variant === "heading") {
    return (
      <h3 className="form-builder-heading" dir="auto">
        {text}
      </h3>
    )
  }

  return (
    <p className="form-builder-paragraph" dir="auto" style={{ whiteSpace: "pre-line" }}>
      {text}
    </p>
  )
}
