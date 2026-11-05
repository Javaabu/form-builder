import { describe, expect, it } from "vitest"
import { addRow, init, rowsOf } from "../../resources/js/blade/repeater"
import html from "./fixtures/repeater.html?raw"

function page() {
  document.body.innerHTML = html
  init(document)
  return document.querySelector<HTMLElement>("[data-form-builder-repeater]")!
}

describe("the Blade repeater script", () => {
  it("adds rows from the template with the next row number", () => {
    const repeater = page()

    expect(rowsOf(repeater)).toHaveLength(1)

    const add = repeater.querySelector<HTMLButtonElement>("[data-form-builder-repeater-add]")!
    add.click()

    const rows = rowsOf(repeater)
    expect(rows).toHaveLength(2)
    expect(rows[1]!.querySelector('[name="references[1][name]"]')).not.toBeNull()
    expect(rows[1]!.querySelector("[data-form-builder-repeater-row-label]")!.textContent).toBe("Reference 2")
    expect(rows[1]!.innerHTML).not.toContain("__ROW__")
    expect(document.activeElement?.getAttribute("name")).toBe("references[1][name]")
  })

  it("stops at the most rows and keeps the fewest", () => {
    const repeater = page()
    const add = repeater.querySelector<HTMLButtonElement>("[data-form-builder-repeater-add]")!

    addRow(repeater)
    addRow(repeater)
    expect(addRow(repeater)).toBeNull()
    expect(rowsOf(repeater)).toHaveLength(3)
    expect(add.disabled).toBe(true)

    rowsOf(repeater)[0]!.querySelector<HTMLButtonElement>("[data-form-builder-repeater-remove]")!.click()

    const rows = rowsOf(repeater)
    expect(rows).toHaveLength(2)
    expect(add.disabled).toBe(false)
    expect(rows[0]!.querySelector("[data-form-builder-repeater-row-label]")!.textContent).toBe("Reference 1")
    // Row numbers in names don't change: the server keeps the order and renumbers
    expect(rows[0]!.querySelector('[name="references[1][name]"]')).not.toBeNull()
  })
})
