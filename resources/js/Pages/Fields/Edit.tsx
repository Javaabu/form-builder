import { StencilForm } from "@javaabu/stencil"
import { PageHeader } from "../../components/PageHeader"
import { StatusAlert } from "../../components/StatusAlert"
import { formBuilderComponents } from "../../stencil"
import type { FieldsEditProps } from "../../types"

/** form-builder::Fields/Edit — a new field, or a field's label, settings, options and conditions */
export default function Edit({
  form,
  record,
  type,
  field,
  title,
  back_url,
  status,
  strings,
}: FieldsEditProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-field-edit={type.key}>
      <PageHeader title={title} description={record.name} backUrl={back_url} backLabel={strings.back} />
      <StatusAlert status={status} />
      <StencilForm key={String(field?.key ?? `new-${type.key}`)} schema={form} components={formBuilderComponents} />
    </div>
  )
}
