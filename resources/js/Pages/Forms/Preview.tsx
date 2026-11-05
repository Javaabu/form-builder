import { Alert } from "@javaabu/forms"
import { StencilForm } from "@javaabu/stencil"
import { PageHeader } from "../../components/PageHeader"
import { StatusAlert } from "../../components/StatusAlert"
import { format } from "../../sortable"
import { formBuilderComponents } from "../../stencil"
import type { FormsPreviewProps } from "../../types"

/** form-builder::Forms/Preview — the draft as people will see it; submitting only checks it */
export default function Preview({
  form,
  version,
  title,
  description,
  back_url,
  status,
  strings,
}: FormsPreviewProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-preview="">
      <PageHeader
        title={format(strings.preview_title ?? ":form", { form: title })}
        backUrl={back_url}
        backLabel={strings.back}
      />

      <Alert type="info">
        {strings.preview_notice}{" "}
        {version.is_draft ? strings.draft : format(strings.version_number ?? ":number", { number: version.number })}
      </Alert>

      <StatusAlert status={status} />

      {description ? (
        <p className="text-muted-foreground" dir="auto">
          {description}
        </p>
      ) : null}

      <StencilForm schema={form} components={formBuilderComponents} />
    </div>
  )
}
