/**
 * Drag and drop ordering with a keyboard alternative, shared by the Blade
 * admin page (createSortable, on the DOM) and the React one (SortableList,
 * on state). Screen reader announcements use the page's strings.
 *
 * Keyboard: Space or Enter on an item's handle picks it up, the arrow keys
 * move it, Space or Enter drops it and Escape puts it back.
 */

export interface SortableStrings {
  /** ":item picked up. Position :position of :total." */
  picked_up: string
  /** ":item moved to position :position of :total." */
  moved: string
  /** ":item dropped at position :position of :total." */
  dropped: string
  /** "Move cancelled. :item is back at position :position of :total." */
  cancelled: string
}

export type SortableEvent = keyof SortableStrings

export const defaultSortableStrings: SortableStrings = {
  picked_up: ":item picked up. Position :position of :total.",
  moved: ":item moved to position :position of :total.",
  dropped: ":item dropped at position :position of :total.",
  cancelled: "Move cancelled. :item is back at position :position of :total.",
}

/** A copy of the list with the item at `from` moved to `to` */
export function moveItem<T>(items: readonly T[], from: number, to: number): T[] {
  const list = [...items]

  if (from < 0 || from >= list.length) return list

  const target = Math.max(0, Math.min(list.length - 1, to))
  const [item] = list.splice(from, 1) as [T]
  list.splice(target, 0, item)

  return list
}

/** Replaces :placeholders, longest first so :item doesn't eat :items */
export function format(template: string, replacements: Record<string, string | number>): string {
  return Object.keys(replacements)
    .sort((a, b) => b.length - a.length)
    .reduce((text, key) => text.split(`:${key}`).join(String(replacements[key])), template)
}

/** What to announce after a keyboard or pointer move */
export function announcement(
  event: SortableEvent,
  title: string,
  index: number,
  total: number,
  strings: Partial<SortableStrings> = {},
): string {
  const template = strings[event] ?? defaultSortableStrings[event]

  return format(template, { item: title, position: index + 1, total })
}

/** The index the arrow key moves an item to, or null for other keys */
export function keyboardTarget(key: string, index: number, total: number): number | null {
  switch (key) {
    case "ArrowUp":
    case "ArrowLeft":
      return Math.max(0, index - 1)
    case "ArrowDown":
    case "ArrowRight":
      return Math.min(total - 1, index + 1)
    case "Home":
      return 0
    case "End":
      return total - 1
    default:
      return null
  }
}

export interface CreateSortableOptions {
  /** The items: children matching this selector. Default `[data-form-builder-item]` */
  itemSelector?: string
  /** The drag handle inside an item. Default `[data-form-builder-handle]` */
  handleSelector?: string
  strings?: Partial<SortableStrings>
  /** Called with a message for the live region */
  announce?: (message: string) => void
  /** Called with the items in their new order after a drop that changed it */
  onChange?: (items: HTMLElement[]) => void
}

export interface Sortable {
  items(): HTMLElement[]
  destroy(): void
}

/**
 * Makes a list's items sortable on the DOM: by dragging their handle, or
 * with the keyboard on it. The Blade builder page uses it.
 */
export function createSortable(list: HTMLElement, options: CreateSortableOptions = {}): Sortable {
  const itemSelector = options.itemSelector ?? "[data-form-builder-item]"
  const handleSelector = options.handleSelector ?? "[data-form-builder-handle]"
  const announce = options.announce ?? (() => {})

  let picked: HTMLElement | null = null
  let pickedFrom = -1
  let dragging: HTMLElement | null = null
  let dragFrom: string[] = []

  const items = () => Array.from(list.querySelectorAll<HTMLElement>(`:scope > ${itemSelector}`))
  const ids = () => items().map((item) => item.dataset.formBuilderItem ?? "")
  const titleOf = (item: HTMLElement) => item.dataset.formBuilderItemLabel ?? item.textContent?.trim() ?? ""
  // Lists nest (fields in sections, fields in repeating groups): only items
  // that are children of this list count, and only their own handles
  const itemOf = (target: EventTarget | null) => {
    let item = target instanceof Element ? target.closest<HTMLElement>(itemSelector) : null
    while (item && item.parentElement !== list) {
      item = item.parentElement?.closest<HTMLElement>(itemSelector) ?? null
    }
    return item
  }
  const ownItemOf = (handle: Element | null) => {
    const item = handle?.closest<HTMLElement>(itemSelector) ?? null
    return item && item.parentElement === list ? item : null
  }
  const handleOf = (item: HTMLElement) => item.querySelector<HTMLElement>(handleSelector)

  const say = (event: SortableEvent, item: HTMLElement) => {
    const all = items()
    announce(announcement(event, titleOf(item), all.indexOf(item), all.length, options.strings))
  }

  const place = (item: HTMLElement, index: number) => {
    const others = items().filter((other) => other !== item)
    const before = others[index] ?? null
    list.insertBefore(item, before)
  }

  const changed = (before: string[]) => before.join(",") !== ids().join(",")

  const pick = (item: HTMLElement) => {
    picked = item
    pickedFrom = items().indexOf(item)
    item.dataset.sorting = "true"
    handleOf(item)?.setAttribute("aria-pressed", "true")
    say("picked_up", item)
  }

  const release = (item: HTMLElement) => {
    delete item.dataset.sorting
    handleOf(item)?.setAttribute("aria-pressed", "false")
    picked = null
  }

  const drop = (item: HTMLElement) => {
    const moved = items().indexOf(item) !== pickedFrom
    release(item)
    say("dropped", item)
    if (moved) options.onChange?.(items())
  }

  const cancel = (item: HTMLElement) => {
    place(item, pickedFrom)
    release(item)
    handleOf(item)?.focus()
    say("cancelled", item)
  }

  const onKeyDown = (event: KeyboardEvent) => {
    const handle = event.target instanceof Element ? event.target.closest(handleSelector) : null
    const item = ownItemOf(handle)
    if (!handle || !item) return

    if (event.key === " " || event.key === "Enter") {
      event.preventDefault()
      if (picked === item) drop(item)
      else {
        if (picked) drop(picked)
        pick(item)
      }
      return
    }

    if (picked !== item) return

    if (event.key === "Escape") {
      event.preventDefault()
      cancel(item)
      return
    }

    const all = items()
    const rtl = getComputedStyle(list).direction === "rtl"
    const key =
      rtl && event.key === "ArrowLeft" ? "ArrowRight" : rtl && event.key === "ArrowRight" ? "ArrowLeft" : event.key
    const target = keyboardTarget(key, all.indexOf(item), all.length)
    if (target === null) return

    event.preventDefault()
    if (target === all.indexOf(item)) return

    place(item, target)
    ;(handle as HTMLElement).focus()
    say("moved", item)
  }

  const onFocusOut = (event: FocusEvent) => {
    if (!picked) return
    const next = event.relatedTarget instanceof Node ? event.relatedTarget : null
    if (next && picked.contains(next)) return
    drop(picked)
  }

  // Only the handle starts a drag, so text in the item can still be selected
  const onPointerDown = (event: PointerEvent) => {
    const handle = event.target instanceof Element ? event.target.closest(handleSelector) : null
    const item = ownItemOf(handle)
    if (item) item.draggable = true
  }

  const onDragStart = (event: DragEvent) => {
    const item = event.target instanceof HTMLElement && event.target.parentElement === list ? event.target : null
    if (!item || !item.draggable) return

    dragging = item
    dragFrom = ids()
    item.dataset.dragging = "true"
    event.dataTransfer?.setData("text/plain", item.dataset.formBuilderItem ?? "")
    if (event.dataTransfer) event.dataTransfer.effectAllowed = "move"
  }

  const onDragOver = (event: DragEvent) => {
    if (!dragging) return
    event.preventDefault()

    const over = itemOf(event.target)
    if (!over || over === dragging) return

    const rect = over.getBoundingClientRect()
    const after = event.clientY > rect.top + rect.height / 2
    list.insertBefore(dragging, after ? over.nextSibling : over)
  }

  const onDragEnd = () => {
    if (!dragging) return

    const item = dragging
    dragging = null
    item.draggable = false
    delete item.dataset.dragging

    if (changed(dragFrom)) {
      say("dropped", item)
      options.onChange?.(items())
    }
  }

  const onDrop = (event: DragEvent) => {
    if (dragging) event.preventDefault()
  }

  list.addEventListener("keydown", onKeyDown)
  list.addEventListener("focusout", onFocusOut)
  list.addEventListener("pointerdown", onPointerDown)
  list.addEventListener("dragstart", onDragStart)
  list.addEventListener("dragover", onDragOver)
  list.addEventListener("drop", onDrop)
  list.addEventListener("dragend", onDragEnd)

  for (const handle of list.querySelectorAll(handleSelector)) handle.setAttribute("aria-pressed", "false")

  return {
    items,
    destroy() {
      list.removeEventListener("keydown", onKeyDown)
      list.removeEventListener("focusout", onFocusOut)
      list.removeEventListener("pointerdown", onPointerDown)
      list.removeEventListener("dragstart", onDragStart)
      list.removeEventListener("dragover", onDragOver)
      list.removeEventListener("drop", onDrop)
      list.removeEventListener("dragend", onDragEnd)
    },
  }
}
