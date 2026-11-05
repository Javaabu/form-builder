"use client"

import * as React from "react"
import { Errors } from "@javaabu/forms"
import type { StencilComponentProps, StencilNode } from "@javaabu/stencil"

interface RepeaterProps {
  label?: string
  help?: React.ReactNode
  required?: boolean
  showLabel?: boolean
  minRows?: number | null
  maxRows?: number | null
  addLabel?: string
  removeLabel?: string
  rowLabel?: string
  rowKeys?: string[]
  nextRow?: number
  rowPlaceholder?: string
  template?: StencilNode
}

interface Row {
  key: string
  node: StencilNode
}

/** The template row with the placeholder (`__ROW__`) replaced by a row's key, in names, ids and selectors */
export function rowFromTemplate(template: StencilNode, placeholder: string, key: string): StencilNode {
  return JSON.parse(JSON.stringify(template).split(placeholder).join(key)) as StencilNode
}

/**
 * `repeater`: a repeating group of fields from javaabu/form-builder's Repeater.
 * Add copies the template row with the next row number; Remove takes a row out.
 * Register it with `formBuilderComponents` on `StencilForm` or `StencilProvider`.
 */
export function FormBuilderRepeater({ node, renderChildren }: StencilComponentProps): React.JSX.Element {
  const props = (node.props ?? {}) as RepeaterProps
  const placeholder = props.rowPlaceholder || "__ROW__"
  const id = (node.name ?? node.key).replace(/\[/g, "-").replace(/\]/g, "")
  const min = props.minRows ?? 0
  const max = props.maxRows ?? null

  const initial = React.useMemo<Row[]>(
    () => (node.children ?? []).map((child, index) => ({ key: props.rowKeys?.[index] ?? String(index), node: child })),
    [node.children, props.rowKeys],
  )

  const [rows, setRows] = React.useState<Row[]>(initial)
  const [previous, setPrevious] = React.useState(initial)
  const next = React.useRef(props.nextRow ?? initial.length)
  const container = React.useRef<HTMLDivElement>(null)

  // A new schema from the server (validation errors, a live update) replaces the rows
  if (previous !== initial) {
    setPrevious(initial)
    setRows(initial)
  }

  const add = () => {
    if (!props.template || (max !== null && rows.length >= max)) return
    const key = String(next.current++)
    setRows((current) => [...current, { key, node: rowFromTemplate(props.template as StencilNode, placeholder, key) }])
    queueMicrotask(() => {
      container.current
        ?.querySelector<HTMLElement>(`[data-row="${CSS.escape(key)}"] :is(input:not([type=hidden]), select, textarea)`)
        ?.focus()
    })
  }

  const remove = (key: string) => {
    if (rows.length <= min) return
    setRows((current) => current.filter((row) => row.key !== key))
  }

  const label = (index: number) => (props.rowLabel ?? ":number").replace(":number", String(index + 1))

  return (
    <div
      className="form-builder-repeater"
      id={id}
      role="group"
      aria-labelledby={`${id}-label`}
      data-form-builder-repeater=""
    >
      <div className="form-builder-repeater-header">
        <span className={props.showLabel === false ? "sr-only" : "form-builder-repeater-label"} id={`${id}-label`}>
          {props.label}
          {props.required ? (
            <span className="form-builder-required" aria-hidden="true">
              *
            </span>
          ) : null}
        </span>
        {props.help ? <p className="form-builder-repeater-help">{props.help}</p> : null}
      </div>

      <div className="form-builder-repeater-rows" ref={container}>
        {rows.map((row, index) => (
          <div className="form-builder-repeater-row" key={row.key} data-row={row.key} data-form-builder-repeater-row="">
            <div className="form-builder-repeater-row-header">
              <span className="form-builder-repeater-row-label">{label(index)}</span>
              <button
                type="button"
                className="form-builder-repeater-remove"
                onClick={() => remove(row.key)}
                disabled={rows.length <= min}
                aria-label={`${props.removeLabel ?? "Remove"}: ${label(index)}`}
              >
                {props.removeLabel ?? "Remove"}
              </button>
            </div>
            {renderChildren(row.node.children ?? [], row.node.props?.columns)}
          </div>
        ))}
      </div>

      <div className="form-builder-repeater-footer">
        <button
          type="button"
          className="form-builder-repeater-add"
          onClick={add}
          disabled={!props.template || (max !== null && rows.length >= max)}
        >
          {props.addLabel ?? "Add"}
        </button>
      </div>

      <Errors name={(node.name ?? node.key).replace(/\[\]$/, "")} />
    </div>
  )
}
