import { Alert } from "@javaabu/forms"
import { StencilForm } from "@javaabu/stencil"
import { PageHeader } from "../../components/PageHeader"
import { formBuilderComponents } from "../../stencil"
import type { FillShowProps } from "../../types"

/** form-builder::Fill/Show — the form people fill */
export default function Show({ form, open, title, description, strings }: FillShowProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-fill="">
      <PageHeader title={title} description={description} />
      {open && form ? (
        <StencilForm schema={form} components={formBuilderComponents} />
      ) : (
        <Alert type="info">{strings.closed}</Alert>
      )}
    </div>
  )
}
