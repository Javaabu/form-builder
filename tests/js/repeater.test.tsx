import { fireEvent, render, screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import { StencilForm, type StencilNode, type StencilSchema } from "@javaabu/stencil"
import { formBuilderComponents, rowFromTemplate } from "@javaabu/form-builder"
import fill from "./fixtures/fill-show.json"

const schema = fill.form as unknown as StencilSchema

function names(): string[] {
  return Array.from(document.querySelectorAll<HTMLInputElement>("[name^='references[']")).map((input) => input.name)
}

describe("FormBuilderRepeater", () => {
  it("renders the rows with their fields", () => {
    render(<StencilForm schema={schema} components={formBuilderComponents} />)

    expect(screen.getByText("Reference 1")).toBeInTheDocument()
    expect(names()).toContain("references[0][name]")
    expect(names().some((name) => name.includes("__ROW__"))).toBe(false)
  })

  it("adds and removes rows", () => {
    render(<StencilForm schema={schema} components={formBuilderComponents} />)

    fireEvent.click(screen.getByRole("button", { name: "Add a reference" }))

    expect(screen.getByText("Reference 2")).toBeInTheDocument()
    expect(names()).toContain("references[1][name]")

    fireEvent.click(screen.getByRole("button", { name: "Remove: Reference 1" }))

    expect(screen.queryByText("Reference 2")).not.toBeInTheDocument()
    expect(names()).toContain("references[1][name]")
    expect(names()).not.toContain("references[0][name]")
  })

  it("stops at the most rows", () => {
    render(<StencilForm schema={schema} components={formBuilderComponents} />)

    const add = screen.getByRole("button", { name: "Add a reference" })
    fireEvent.click(add)
    fireEvent.click(add)

    expect(screen.getByText("Reference 3")).toBeInTheDocument()
    expect(add).toBeDisabled()
  })

  it("submits the rows' values", () => {
    const { container } = render(<StencilForm schema={schema} components={formBuilderComponents} />)

    const input = container.querySelector<HTMLInputElement>("[name='references[0][name]']")!
    fireEvent.change(input, { target: { value: "މަރިޔަމް" } })

    const data = new FormData(container.querySelector("form")!)
    expect(data.get("references[0][name]")).toBe("މަރިޔަމް")
  })

  it("replaces the row placeholder everywhere", () => {
    const template: StencilNode = {
      type: "group",
      key: "references-__ROW__",
      children: [
        {
          type: "text",
          key: "references.__ROW__.other",
          name: "references[__ROW__][other]",
          conditions: [
            {
              effect: "visible",
              field: "references.__ROW__.relationship",
              selector: "#references-__ROW__-relationship",
              values: ["other"],
              checkbox: false,
            },
          ],
        },
      ],
    }

    const row = rowFromTemplate(template, "__ROW__", "7")

    expect(row.key).toBe("references-7")
    expect(row.children![0]!.name).toBe("references[7][other]")
    expect(row.children![0]!.conditions![0]!.selector).toBe("#references-7-relationship")
  })
})

describe("FormBuilderContent", () => {
  it("renders paragraphs and headings", () => {
    render(<StencilForm schema={schema} components={formBuilderComponents} />)

    expect(screen.getByText("Give up to three people we can call.").tagName).toBe("P")
  })
})
