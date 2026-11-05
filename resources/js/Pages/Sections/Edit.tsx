import { StencilForm } from "@javaabu/stencil"
import { PageHeader } from "../../components/PageHeader"
import { StatusAlert } from "../../components/StatusAlert"
import { formBuilderComponents } from "../../stencil"
import type { SectionsEditProps } from "../../types"

/** form-builder::Sections/Edit — a new section, or a section's settings and conditions */
export default function Edit({
  form,
  record,
  section,
  title,
  back_url,
  status,
  strings,
}: SectionsEditProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-section-edit="">
      <PageHeader title={title} description={record.name} backUrl={back_url} backLabel={strings.back} />
      <StatusAlert status={status} />
      <StencilForm key={String(section?.key ?? "new")} schema={form} components={formBuilderComponents} />
    </div>
  )
}
