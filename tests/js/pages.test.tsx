import { fireEvent, render, screen, within } from "@testing-library/react"
import { beforeEach, describe, expect, it, vi } from "vitest"
import {
  EntriesIndex,
  EntriesShow,
  FieldsEdit,
  FillShow,
  FormsIndex,
  FormsShow,
  type EntriesIndexProps,
  type EntriesShowProps,
  type FieldsEditProps,
  type FillShowProps,
  type FormsIndexProps,
  type FormsShowProps,
} from "@javaabu/form-builder"
import entriesIndex from "./fixtures/entries-index.json"
import entriesShow from "./fixtures/entries-show.json"
import fieldsEdit from "./fixtures/fields-edit.json"
import fillShow from "./fixtures/fill-show.json"
import formsIndex from "./fixtures/forms-index.json"
import formsShow from "./fixtures/forms-show.json"

const router = vi.hoisted(() => ({ put: vi.fn(), post: vi.fn(), delete: vi.fn(), get: vi.fn() }))

vi.mock("@inertiajs/react", () => ({ router }))

beforeEach(() => {
  for (const method of Object.values(router)) method.mockReset()
  vi.unstubAllGlobals()
})

describe("form-builder::Forms/Index", () => {
  it("lists forms", () => {
    render(<FormsIndex {...(formsIndex as unknown as FormsIndexProps)} />)

    expect(screen.getByRole("heading", { name: "Forms" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "Job application" })).toHaveAttribute(
      "href",
      "http://localhost/forms/job-application",
    )
    expect(screen.getByText("Published")).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "New form" })).toBeInTheDocument()
  })
})

describe("form-builder::Forms/Show", () => {
  const props = formsShow as unknown as FormsShowProps

  it("lists sections and fields with their actions", () => {
    render(<FormsShow {...props} />)

    expect(screen.getByRole("heading", { name: "Personal details" })).toBeInTheDocument()
    expect(screen.getByText("Full name")).toBeInTheDocument()
    expect(screen.getByText("references.name")).toBeInTheDocument()
    expect(screen.getByText(/Applicant type is one of Company/)).toBeInTheDocument()
    expect(screen.getByRole("button", { name: "Publish" })).toBeInTheDocument()
    expect(screen.getByText(/version 1 until you publish/)).toBeInTheDocument()
  })

  it("saves a field order made with the keyboard", () => {
    render(<FormsShow {...props} />)

    const handle = screen.getByRole("button", { name: "Move Full name" })
    fireEvent.keyDown(handle, { key: " " })
    fireEvent.keyDown(handle, { key: "ArrowDown" })
    fireEvent.keyDown(screen.getByRole("button", { name: "Move Full name" }), { key: " " })

    const section = props.sections[0]!
    expect(router.put).toHaveBeenCalledWith(
      section.urls.order_fields,
      { fields: [section.fields[1]!.key, "full_name", ...section.fields.slice(2).map((field) => field.key)] },
      expect.anything(),
    )
  })

  it("moves, deletes and publishes", () => {
    vi.stubGlobal("confirm", vi.fn().mockReturnValue(true))
    render(<FormsShow {...props} />)

    fireEvent.click(screen.getByRole("button", { name: "Move down: Full name" }))
    expect(router.post).toHaveBeenCalledWith(
      props.sections[0]!.fields[0]!.urls.move,
      { direction: "down" },
      expect.anything(),
    )

    const field = document.querySelector<HTMLElement>('[data-form-builder-field="age"]')!
    fireEvent.click(within(field).getByRole("button", { name: "Delete" }))
    expect(router.delete).toHaveBeenCalledWith(props.sections[0]!.fields[1]!.urls.destroy, expect.anything())

    fireEvent.click(screen.getByRole("button", { name: "Publish" }))
    expect(router.post).toHaveBeenCalledWith(props.urls.publish, {}, expect.anything())
  })

  it("offers the assistant when laravel/ai is installed", () => {
    render(<FormsShow {...props} />)

    const assistant = document.querySelector<HTMLElement>("[data-form-builder-assistant]")!
    expect(within(assistant).getByLabelText(/What should the form ask/)).toHaveAttribute("name", "description")
    expect(assistant.querySelector("form")).toHaveAttribute("action", props.urls.assistant)
  })

  it("opens a new field of the chosen type", () => {
    render(<FormsShow {...props} />)

    fireEvent.change(screen.getByLabelText("Add a field to Personal details"), { target: { value: "email" } })
    fireEvent.click(
      within(document.querySelector<HTMLElement>('[data-form-builder-section="personal"]')!)
        .getAllByRole("button", { name: "Add field" })
        .at(-1)!,
    )

    expect(router.get).toHaveBeenCalledWith("/forms/job-application/sections/personal/fields/create?type=email")
  })
})

describe("form-builder::Fields/Edit", () => {
  it("renders the field's settings form", () => {
    render(<FieldsEdit {...(fieldsEdit as unknown as FieldsEditProps)} />)

    expect(screen.getByRole("heading", { name: "Edit References" })).toBeInTheDocument()
    expect(document.querySelector('[name="key"]')).toHaveValue("references")
    expect(document.querySelector('[name="settings[max_rows]"]')).toHaveValue(3)
  })
})

describe("form-builder::Entries", () => {
  it("lists entries", () => {
    render(<EntriesIndex {...(entriesIndex as unknown as EntriesIndexProps)} />)

    expect(screen.getByText("އަޙްމަދު ނާޒިމް")).toBeInTheDocument()
    expect(screen.getByText("Export CSV")).toBeInTheDocument()
  })

  it("shows an entry read-only", () => {
    render(<EntriesShow {...(entriesShow as unknown as EntriesShowProps)} />)

    expect(screen.getByText("ޖާވާބު")).toBeInTheDocument()
    expect(screen.getByText("Reference 1")).toBeInTheDocument()
    expect(document.querySelector("input:not([type=hidden])")).toBeNull()
  })
})

describe("form-builder::Fill/Show", () => {
  it("renders the form", () => {
    render(<FillShow {...(fillShow as unknown as FillShowProps)} />)

    expect(screen.getByRole("heading", { name: "Job application" })).toBeInTheDocument()
    expect(document.querySelector('[name="full_name"]')).not.toBeNull()
    expect(screen.getByRole("button", { name: "Apply" })).toBeInTheDocument()
  })

  it("says when a form is closed", () => {
    render(<FillShow {...(fillShow as unknown as FillShowProps)} open={false} form={null} />)

    expect(screen.getByText("This form isn't accepting entries.")).toBeInTheDocument()
  })
})
