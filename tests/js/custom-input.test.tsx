import { fireEvent, render, screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import {
  ConditionalWrapper,
  splitFieldProps,
  useField,
  useFieldState,
  useFieldValue,
  usePublishField,
  type FieldProps,
} from "@javaabu/forms"
import { StencilForm, type StencilComponentProps, type StencilSchema } from "@javaabu/stencil"
import { formBuilderComponents } from "@javaabu/form-builder"
import starsForm from "./fixtures/stars-form.json"

/** The React side of an app's own input type, built with @javaabu/forms' custom-field hooks */
function StarsInput(props: FieldProps & { max: number }): React.JSX.Element {
  const { field, rest } = splitFieldProps(props)
  const f = useField(field)
  const initial = String(useFieldValue(field.name, field.model, field.default) ?? "")
  const [value, setValue] = useFieldState(field.name, initial)

  usePublishField({ id: f.id, name: field.name, kind: "value", value, disabled: f.disabled, label: f.labelText })

  return f.wrap(
    <div role="radiogroup" id={f.id} aria-labelledby={`${f.baseId}-label`} aria-describedby={f.describedBy}>
      {Array.from({ length: rest.max }, (_, index) => String(index + 1)).map((star) => (
        <button key={star} type="button" role="radio" aria-checked={value === star} onClick={() => setValue(star)}>
          {"★".repeat(Number(star))}
        </button>
      ))}
      <input type="hidden" name={field.name} value={value} />
    </div>,
  )
}

/** Registered for the stencil type the PHP field renders as */
function Stars({ node }: StencilComponentProps) {
  return <StarsInput name={node.name ?? node.key} {...(node.props as { label: string; max: number })} />
}

describe("an input type of the app's own", () => {
  it("renders with its React component and submits its value", () => {
    const schema = starsForm as unknown as StencilSchema
    const { container } = render(
      <StencilForm schema={schema} components={{ ...formBuilderComponents, stars: Stars }} />,
    )

    expect(screen.getAllByRole("radio")).toHaveLength(4)

    fireEvent.click(screen.getByRole("radio", { name: "★★★" }))

    expect(new FormData(container.querySelector("form")!).get("stars")).toBe("3")
  })

  it("works with conditional wrappers like built-in fields", () => {
    render(
      <StencilForm
        schema={starsForm as unknown as StencilSchema}
        components={{ ...formBuilderComponents, stars: Stars }}
      >
        <ConditionalWrapper enableElem="#stars" enableValue="4" hideFields>
          <p>Thanks for the four stars</p>
        </ConditionalWrapper>
      </StencilForm>,
    )

    expect(screen.queryByText("Thanks for the four stars")).not.toBeVisible()

    fireEvent.click(screen.getByRole("radio", { name: "★★★★" }))

    expect(screen.getByText("Thanks for the four stars")).toBeVisible()
  })
})
