/**
 * The Blade builder page (form-builder::forms.show): drag and drop and keyboard
 * ordering of sections and fields that saves on drop, and confirmation before
 * deleting or publishing. Required by the page with @manifestRequire('form-builder::builder').
 */
import { createSortable, type SortableStrings } from "../sortable"

type BuilderStrings = Partial<SortableStrings> & { order_saved?: string; order_failed?: string }

function readStrings(root: HTMLElement): BuilderStrings {
  try {
    return JSON.parse(root.dataset.strings ?? "{}") as BuilderStrings
  } catch {
    return {}
  }
}

/** PUT the keys of a list's items, in order, as `{ [name]: keys }` */
export async function saveOrder(url: string, name: string, keys: string[], csrf?: string): Promise<boolean> {
  try {
    const response = await fetch(url, {
      method: "PUT",
      body: JSON.stringify({ [name]: keys }),
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
      },
      credentials: "same-origin",
    })

    return response.ok
  } catch {
    return false
  }
}

export function initBuilder(root: HTMLElement): () => void {
  const strings = readStrings(root)
  const csrf = root.dataset.csrf
  const announcer = root.querySelector<HTMLElement>("[data-form-builder-announcer]")
  const announce = (message: string) => {
    if (!announcer) return
    // Clearing first makes screen readers repeat the same message
    announcer.textContent = ""
    window.setTimeout(() => (announcer.textContent = message), 50)
  }

  const cleanups: Array<() => void> = []

  for (const list of root.querySelectorAll<HTMLElement>("[data-form-builder-sortable]")) {
    const url = list.dataset.orderUrl
    const name = list.dataset.orderName ?? "items"

    const sortable = createSortable(list, {
      strings,
      announce,
      onChange: async (items) => {
        if (!url) return
        const keys = items.map((item) => item.dataset.formBuilderItem ?? "")
        const saved = await saveOrder(url, name, keys, csrf)
        const message = saved ? strings.order_saved : strings.order_failed
        if (message) window.setTimeout(() => announce(message), 400)
        root.dispatchEvent(new CustomEvent("form-builder:order", { detail: { saved, name, keys } }))
      },
    })

    cleanups.push(() => sortable.destroy())
  }

  const onSubmit = (event: SubmitEvent) => {
    const form = event.target instanceof HTMLFormElement ? event.target : null
    const message = form?.dataset.formBuilderConfirm
    if (message && !window.confirm(message)) event.preventDefault()
  }

  // Delete buttons submit hidden forms outside their item (form="…"), so listen on the document
  document.addEventListener("submit", onSubmit, true)
  cleanups.push(() => document.removeEventListener("submit", onSubmit, true))

  return () => cleanups.forEach((cleanup) => cleanup())
}

function boot() {
  for (const root of document.querySelectorAll<HTMLElement>("[data-form-builder-builder]")) {
    if (root.dataset.formBuilderReady) continue
    root.dataset.formBuilderReady = "true"
    initBuilder(root)
  }
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot)
  else boot()
}
