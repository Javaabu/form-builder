import { StencilForm } from "@javaabu/stencil"
import { PageHeader } from "../../components/PageHeader"
import { StatusAlert } from "../../components/StatusAlert"
import { formBuilderComponents } from "../../stencil"
import type { FormsEditProps } from "../../types"

/** form-builder::Forms/Edit — a new form, or a form's settings */
export default function Edit({ form, record, title, back_url, status, strings }: FormsEditProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-settings="">
      <PageHeader title={title} backUrl={back_url} backLabel={strings.back} />
      <StatusAlert status={status} />
      <StencilForm key={record?.key ?? "new"} schema={form} components={formBuilderComponents} />
    </div>
  )
}
