import { describe, expect, it, vi } from "vitest"
import { announcement, createSortable, format, keyboardTarget, moveItem } from "@javaabu/form-builder"

describe("moveItem", () => {
  it("moves an item and clamps the target", () => {
    expect(moveItem(["a", "b", "c"], 0, 2)).toEqual(["b", "c", "a"])
    expect(moveItem(["a", "b", "c"], 2, 0)).toEqual(["c", "a", "b"])
    expect(moveItem(["a", "b", "c"], 1, 10)).toEqual(["a", "c", "b"])
    expect(moveItem(["a", "b"], 5, 0)).toEqual(["a", "b"])
  })
})

describe("announcements", () => {
  it("formats the strings, longest placeholder first", () => {
    expect(format(":item :items", { item: "A", items: "B" })).toBe("A B")
    expect(announcement("moved", "ޓެކްސްޓް", 1, 3)).toBe("ޓެކްސްޓް moved to position 2 of 3.")
    expect(announcement("dropped", "Text", 0, 2, { dropped: ":item ބެހެއްޓީ :position/:total" })).toBe(
      "Text ބެހެއްޓީ 1/2",
    )
  })

  it("maps keys to targets", () => {
    expect(keyboardTarget("ArrowUp", 1, 3)).toBe(0)
    expect(keyboardTarget("ArrowDown", 2, 3)).toBe(2)
    expect(keyboardTarget("ArrowRight", 0, 3)).toBe(1)
    expect(keyboardTarget("Home", 2, 3)).toBe(0)
    expect(keyboardTarget("End", 0, 3)).toBe(2)
    expect(keyboardTarget("a", 0, 3)).toBeNull()
  })
})

function list(titles: string[]) {
  document.body.innerHTML = `<ol id="list">${titles
    .map(
      (title, index) =>
        `<li data-form-builder-item="${index + 1}" data-form-builder-item-label="${title}"><button type="button" data-form-builder-handle>≡</button> ${title}</li>`,
    )
    .join("")}</ol><button id="outside">x</button>`

  return document.getElementById("list") as HTMLOListElement
}

const order = (element: HTMLElement) =>
  Array.from(element.querySelectorAll("li")).map((item) => item.dataset.formBuilderItem)
const handle = (element: HTMLElement, index: number) =>
  element.querySelectorAll<HTMLButtonElement>("[data-form-builder-handle]")[index]!
const key = (target: HTMLElement, key: string) =>
  target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }))

describe("createSortable (Blade)", () => {
  it("moves an item with the keyboard and announces each step", () => {
    const element = list(["ފުރަތަމަ", "Second", "Third"])
    const announce = vi.fn()
    const onChange = vi.fn()
    createSortable(element, { announce, onChange })

    const first = handle(element, 0)
    expect(first).toHaveAttribute("aria-pressed", "false")

    key(first, " ")
    expect(first).toHaveAttribute("aria-pressed", "true")
    expect(announce).toHaveBeenLastCalledWith("ފުރަތަމަ picked up. Position 1 of 3.")

    key(first, "ArrowDown")
    key(first, "ArrowDown")
    expect(order(element)).toEqual(["2", "3", "1"])
    expect(announce).toHaveBeenLastCalledWith("ފުރަތަމަ moved to position 3 of 3.")
    expect(onChange).not.toHaveBeenCalled()

    key(first, "Enter")
    expect(first).toHaveAttribute("aria-pressed", "false")
    expect(announce).toHaveBeenLastCalledWith("ފުރަތަމަ dropped at position 3 of 3.")
    expect(onChange).toHaveBeenCalledTimes(1)
    expect(onChange.mock.calls[0]![0].map((item: HTMLElement) => item.dataset.formBuilderItem)).toEqual(["2", "3", "1"])
  })

  it("puts the item back on Escape", () => {
    const element = list(["A", "B", "C"])
    const onChange = vi.fn()
    const announce = vi.fn()
    createSortable(element, { onChange, announce })

    const b = handle(element, 1)
    key(b, " ")
    key(b, "ArrowUp")
    expect(order(element)).toEqual(["2", "1", "3"])

    key(b, "Escape")
    expect(order(element)).toEqual(["1", "2", "3"])
    expect(announce).toHaveBeenLastCalledWith("Move cancelled. B is back at position 2 of 3.")
    expect(onChange).not.toHaveBeenCalled()
  })

  it("doesn't save a drop in the same place, and drops when focus leaves", () => {
    const element = list(["A", "B"])
    const onChange = vi.fn()
    createSortable(element, { onChange })

    const a = handle(element, 0)
    key(a, " ")
    key(a, " ")
    expect(onChange).not.toHaveBeenCalled()

    key(a, " ")
    key(a, "End")
    a.dispatchEvent(new FocusEvent("focusout", { bubbles: true, relatedTarget: document.getElementById("outside") }))
    expect(onChange).toHaveBeenCalledTimes(1)
    expect(a).toHaveAttribute("aria-pressed", "false")
  })

  it("ignores arrow keys until an item is picked up, and stops listening when destroyed", () => {
    const element = list(["A", "B"])
    const sortable = createSortable(element)

    key(handle(element, 0), "ArrowDown")
    expect(order(element)).toEqual(["1", "2"])

    sortable.destroy()
    key(handle(element, 0), " ")
    expect(handle(element, 0)).toHaveAttribute("aria-pressed", "false")
    expect(sortable.items()).toHaveLength(2)
  })

  it("reorders by dragging the handle", () => {
    const element = list(["A", "B", "C"])
    const onChange = vi.fn()
    createSortable(element, { onChange })

    const [a, , c] = Array.from(element.querySelectorAll("li"))
    c!.getBoundingClientRect = () => ({ top: 100, height: 20 }) as DOMRect

    // Dragging the item itself (not its handle) does nothing
    a!.dispatchEvent(new Event("dragstart", { bubbles: true }))
    expect(a!.dataset.dragging).toBeUndefined()

    handle(element, 0).dispatchEvent(new Event("pointerdown", { bubbles: true }))
    a!.dispatchEvent(new Event("dragstart", { bubbles: true }))
    expect(a!.dataset.dragging).toBe("true")

    const over = new Event("dragover", { bubbles: true, cancelable: true }) as DragEvent
    Object.defineProperty(over, "clientY", { value: 115 })
    c!.dispatchEvent(over)
    expect(over.defaultPrevented).toBe(true)
    expect(order(element)).toEqual(["2", "3", "1"])

    element.dispatchEvent(new Event("drop", { bubbles: true, cancelable: true }))
    a!.dispatchEvent(new Event("dragend", { bubbles: true }))
    expect(onChange).toHaveBeenCalledTimes(1)
    expect(a!.draggable).toBe(false)
  })
})
