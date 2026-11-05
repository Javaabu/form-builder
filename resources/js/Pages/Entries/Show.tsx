import { router } from "@inertiajs/react"
import { Button, Card, LinkButton } from "@javaabu/forms"
import { StencilFields } from "@javaabu/stencil"
import { EntryDetails } from "../../components/EntryDetails"
import { PageHeader } from "../../components/PageHeader"
import { StatusAlert } from "../../components/StatusAlert"
import { format } from "../../sortable"
import { formBuilderComponents } from "../../stencil"
import type { EntriesShowProps } from "../../types"

/** form-builder::Entries/Show — one entry's answers, read-only */
export default function Show({
  record,
  entry,
  display,
  files,
  can,
  urls,
  status,
  strings,
}: EntriesShowProps): React.JSX.Element {
  const remove = () => {
    if (window.confirm(strings.confirm_delete ?? "")) router.delete(urls.destroy)
  }

  return (
    <div className="flex flex-col gap-6" data-form-builder-entry={entry.id}>
      <PageHeader
        title={format(strings.entry ?? ":number", { number: entry.id })}
        description={record.name}
        backUrl={urls.index}
        backLabel={strings.back}
        actions={
          <>
            <LinkButton url={urls.print} color="outline-secondary" startIcon="file-text" target="_blank">
              {strings.print}
            </LinkButton>
            {can.delete ? (
              <Button type="button" color="danger" onClick={remove}>
                {strings.delete}
              </Button>
            ) : null}
          </>
        }
      />

      <StatusAlert status={status} />

      <EntryDetails entry={entry} strings={strings} />

      <div className="form-builder-entry">
        <StencilFields schema={display} components={formBuilderComponents} />
      </div>

      {files.length > 0 ? (
        <Card title={strings.files}>
          <ul className="flex flex-col gap-1">
            {files.map((file) => (
              <li key={file.url}>
                <span className="text-muted-foreground" dir="auto">
                  {file.label}:
                </span>{" "}
                <a href={file.url} className="hover:underline" dir="auto">
                  {file.name}
                </a>
              </li>
            ))}
          </ul>
        </Card>
      ) : null}
    </div>
  )
}
