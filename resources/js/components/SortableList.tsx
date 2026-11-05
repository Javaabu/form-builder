import { useState, type DragEvent, type HTMLAttributes, type KeyboardEvent, type ReactNode } from "react"
import { announcement, keyboardTarget, moveItem, type SortableEvent, type SortableStrings } from "../sortable"

/** Props to spread on an item's drag handle (a <button>) */
export interface SortableHandleProps {
  "aria-pressed": boolean
  "aria-describedby"?: string
  onKeyDown: (event: KeyboardEvent<HTMLElement>) => void
  onBlur: () => void
  onPointerDown: () => void
  "data-form-builder-handle": true
}

export interface SortableListProps<T> extends Omit<HTMLAttributes<HTMLOListElement>, "children" | "onChange"> {
  items: T[]
  getKey: (item: T) => string | number
  /** What screen readers call an item */
  getTitle: (item: T) => string
  renderItem: (item: T, handle: SortableHandleProps, state: { sorting: boolean; dragging: boolean }) => ReactNode
  /** Called with the items in their new order after a drop that changed it */
  onReorder?: (items: T[]) => void
  strings?: Partial<SortableStrings>
  /** The id of the element that explains the keyboard controls */
  instructionsId?: string
  /** Turn sorting off (users who can't change the order) */
  disabled?: boolean
  itemClassName?: string
}

/**
 * A list sorted by dragging an item's handle or with the keyboard on it:
 * Space or Enter picks an item up, the arrow keys move it, Space or Enter
 * drops it and Escape cancels. Moves are announced in a live region.
 */
export function SortableList<T>({
  items: initial,
  getKey,
  getTitle,
  renderItem,
  onReorder,
  strings,
  instructionsId,
  disabled = false,
  itemClassName,
  ...props
}: SortableListProps<T>) {
  const [items, setItems] = useState(initial)
  const [previous, setPrevious] = useState(initial)
  const [picked, setPicked] = useState<{ key: string | number; from: number } | null>(null)
  const [dragging, setDragging] = useState<{ key: string | number; from: number } | null>(null)
  const [message, setMessage] = useState("")
  // The item whose handle was pressed: only the handle starts a drag
  const [grabbed, setGrabbed] = useState<string | number | null>(null)

  // New items from the server (an Inertia visit) replace the local order
  if (previous !== initial) {
    setPrevious(initial)
    setItems(initial)
  }

  const say = (event: SortableEvent, list: T[], key: string | number) => {
    const index = list.findIndex((item) => getKey(item) === key)
    const item = list[index]
    if (item === undefined) return
    setMessage(announcement(event, getTitle(item), index, list.length, strings))
  }

  // Moving re-renders the list; keep focus on the moved item's handle
  const focusHandle = (list: Element | null, key: string | number) => {
    queueMicrotask(() =>
      list
        ?.querySelector<HTMLElement>(`[data-form-builder-item="${CSS.escape(String(key))}"] [data-form-builder-handle]`)
        ?.focus(),
    )
  }

  const indexOf = (key: string | number) => items.findIndex((item) => getKey(item) === key)

  const drop = (key: string | number, from: number) => {
    setPicked(null)
    say("dropped", items, key)
    if (indexOf(key) !== from) onReorder?.(items)
  }

  const onKeyDown = (key: string | number, event: KeyboardEvent<HTMLElement>) => {
    if (disabled) return

    if (event.key === " " || event.key === "Enter") {
      event.preventDefault()
      if (picked?.key === key) {
        drop(key, picked.from)
        return
      }
      if (picked) drop(picked.key, picked.from)
      setPicked({ key, from: indexOf(key) })
      say("picked_up", items, key)
      return
    }

    if (picked?.key !== key) return

    if (event.key === "Escape") {
      event.preventDefault()
      const restored = moveItem(items, indexOf(key), picked.from)
      setItems(restored)
      setPicked(null)
      say("cancelled", restored, key)
      focusHandle(event.currentTarget.closest("ol"), key)
      return
    }

    const rtl = getComputedStyle(event.currentTarget).direction === "rtl"
    const pressed =
      rtl && event.key === "ArrowLeft" ? "ArrowRight" : rtl && event.key === "ArrowRight" ? "ArrowLeft" : event.key
    const from = indexOf(key)
    const to = keyboardTarget(pressed, from, items.length)
    if (to === null) return

    event.preventDefault()
    if (to === from) return

    const moved = moveItem(items, from, to)
    setItems(moved)
    say("moved", moved, key)
    focusHandle(event.currentTarget.closest("ol"), key)
  }

  const onBlur = (key: string | number) => {
    if (picked?.key === key) drop(key, picked.from)
  }

  const onDragStart = (key: string | number, event: DragEvent<HTMLLIElement>) => {
    // Lists nest: a drag in an inner list is its own
    if (event.target !== event.currentTarget) return
    if (disabled || grabbed !== key) {
      event.preventDefault()
      return
    }
    setDragging({ key, from: indexOf(key) })
    event.dataTransfer?.setData("text/plain", String(key))
    if (event.dataTransfer) event.dataTransfer.effectAllowed = "move"
  }

  const onDragOver = (key: string | number, event: DragEvent<HTMLLIElement>) => {
    if (!dragging) return
    event.preventDefault()
    event.stopPropagation()
    if (key === dragging.key) return

    const rect = event.currentTarget.getBoundingClientRect()
    const after = event.clientY > rect.top + rect.height / 2
    const from = indexOf(dragging.key)
    let to = indexOf(key) + (after ? 1 : 0)
    if (from < to) to -= 1
    if (to !== from) setItems(moveItem(items, from, to))
  }

  const onDragEnd = () => {
    setGrabbed(null)
    if (!dragging) return
    const { key, from } = dragging
    setDragging(null)
    if (indexOf(key) !== from) {
      say("dropped", items, key)
      onReorder?.(items)
    }
  }

  return (
    <>
      <ol
        {...props}
        data-form-builder-sortable={disabled ? undefined : true}
        onDrop={(event) => dragging && event.preventDefault()}
      >
        {items.map((item) => {
          const key = getKey(item)
          const handle: SortableHandleProps = {
            "aria-pressed": picked?.key === key,
            "aria-describedby": instructionsId,
            onKeyDown: (event) => onKeyDown(key, event),
            onBlur: () => onBlur(key),
            onPointerDown: () => setGrabbed(key),
            "data-form-builder-handle": true,
          }

          return (
            <li
              key={key}
              className={itemClassName}
              data-form-builder-item={key}
              data-sorting={picked?.key === key ? "true" : undefined}
              data-dragging={dragging?.key === key ? "true" : undefined}
              draggable={!disabled}
              onDragStart={(event) => onDragStart(key, event)}
              onDragOver={(event) => onDragOver(key, event)}
              onDragEnd={onDragEnd}
            >
              {renderItem(item, handle, { sorting: picked?.key === key, dragging: dragging?.key === key })}
            </li>
          )
        })}
      </ol>
      <div className="sr-only" aria-live="assertive" aria-atomic="true" data-form-builder-announcer>
        {message}
      </div>
    </>
  )
}
