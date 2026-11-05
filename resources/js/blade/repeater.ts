/**
 * Repeating groups in Blade forms (form-builder::stencil.repeater): Add copies
 * the template row with the next row number, Remove takes a row out, and the
 * buttons follow the fewest and most rows. Loaded as a script it enhances the
 * page and repeaters added later (stencil's live updates).
 * With a bundler: `import { init } from "@javaabu/form-builder/blade/repeater"`.
 */

const READY = "formBuilderRepeaterReady"

function number(value: string | undefined): number | null {
  if (value === undefined || value === "") return null
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

/** The rows of a repeater, not those of repeaters inside it */
export function rowsOf(repeater: HTMLElement): HTMLElement[] {
  const container = repeater.querySelector<HTMLElement>(":scope > [data-form-builder-repeater-rows]")
  return container
    ? Array.from(container.querySelectorAll<HTMLElement>(":scope > [data-form-builder-repeater-row]"))
    : []
}

/** Renumbers the row headings and enables Add and Remove for the number of rows */
export function update(repeater: HTMLElement): void {
  const rows = rowsOf(repeater)
  const min = number(repeater.dataset.min) ?? 0
  const max = number(repeater.dataset.max)
  const label = repeater.dataset.rowLabel ?? ":number"

  rows.forEach((row, index) => {
    const heading = row.querySelector<HTMLElement>(":scope > * > [data-form-builder-repeater-row-label]")
    if (heading) heading.textContent = label.replace(":number", String(index + 1))

    const remove = row.querySelector<HTMLButtonElement>(":scope > * > [data-form-builder-repeater-remove]")
    if (remove) remove.disabled = rows.length <= min
  })

  const add = repeater.querySelector<HTMLButtonElement>(":scope > * > [data-form-builder-repeater-add]")
  if (add) add.disabled = max !== null && rows.length >= max
}

/** Adds a row copied from the template and returns it */
export function addRow(repeater: HTMLElement): HTMLElement | null {
  const template = repeater.querySelector<HTMLTemplateElement>(":scope > template[data-form-builder-repeater-template]")
  const container = repeater.querySelector<HTMLElement>(":scope > [data-form-builder-repeater-rows]")
  if (!template || !container) return null

  const max = number(repeater.dataset.max)
  if (max !== null && rowsOf(repeater).length >= max) return null

  const next = number(repeater.dataset.next) ?? rowsOf(repeater).length
  const placeholder = repeater.dataset.placeholder || "__ROW__"

  const holder = document.createElement("template")
  holder.innerHTML = template.innerHTML.split(placeholder).join(String(next))
  const row = holder.content.firstElementChild as HTMLElement | null
  if (!row) return null

  container.appendChild(row)
  repeater.dataset.next = String(next + 1)
  update(repeater)

  row.querySelector<HTMLElement>("input:not([type=hidden]), select, textarea")?.focus()
  repeater.dispatchEvent(new CustomEvent("form-builder:row-added", { detail: { row }, bubbles: true }))

  return row
}

export function removeRow(repeater: HTMLElement, row: HTMLElement): void {
  const rows = rowsOf(repeater)
  const min = number(repeater.dataset.min) ?? 0
  if (rows.length <= min) return

  const index = rows.indexOf(row)
  row.remove()
  update(repeater)

  const remaining = rowsOf(repeater)
  const focus =
    remaining[Math.min(index, remaining.length - 1)]?.querySelector<HTMLElement>(
      "[data-form-builder-repeater-remove]",
    ) ?? repeater.querySelector<HTMLElement>("[data-form-builder-repeater-add]")
  focus?.focus()

  repeater.dispatchEvent(new CustomEvent("form-builder:row-removed", { bubbles: true }))
}

export function initRepeater(repeater: HTMLElement): void {
  if (repeater.dataset[READY]) return
  repeater.dataset[READY] = "true"

  repeater.addEventListener("click", (event) => {
    const target = event.target instanceof Element ? event.target : null
    const add = target?.closest<HTMLElement>("[data-form-builder-repeater-add]")

    if (add && add.closest("[data-form-builder-repeater]") === repeater) {
      event.preventDefault()
      addRow(repeater)
      return
    }

    const remove = target?.closest<HTMLElement>("[data-form-builder-repeater-remove]")
    const row = remove?.closest<HTMLElement>("[data-form-builder-repeater-row]")

    if (remove && row && row.closest("[data-form-builder-repeater]") === repeater) {
      event.preventDefault()
      removeRow(repeater, row)
    }
  })

  update(repeater)
}

export function init(root: ParentNode = document): void {
  if (root instanceof HTMLElement && root.matches("[data-form-builder-repeater]")) initRepeater(root)
  root.querySelectorAll<HTMLElement>("[data-form-builder-repeater]").forEach(initRepeater)
}

function autoInit(): void {
  if (typeof document === "undefined") return

  const start = () => {
    init(document)
    new MutationObserver((records) => {
      for (const record of records) {
        for (const node of record.addedNodes) {
          if (node instanceof HTMLElement) init(node)
        }
      }
    }).observe(document.body, { childList: true, subtree: true })
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start)
  else start()
}

autoInit()
