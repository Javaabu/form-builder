import { Alert, Button, LinkButton } from "@javaabu/forms"
import { PageHeader } from "../../components/PageHeader"
import { PaginationNav } from "../../components/PaginationNav"
import { StatusAlert } from "../../components/StatusAlert"
import { format } from "../../sortable"
import type { EntriesIndexProps } from "../../types"

/** form-builder::Entries/Index — a form's entries, with their first answers */
export default function Index({
  record,
  entries,
  columns,
  versions,
  version,
  pagination,
  can,
  urls,
  status,
  strings,
}: EntriesIndexProps): React.JSX.Element {
  const paths = Object.keys(columns)

  return (
    <div className="flex flex-col gap-6" data-form-builder-entries="">
      <PageHeader
        title={format(strings.title ?? ":form", { form: record.name })}
        backUrl={urls.form}
        backLabel={strings.back}
        actions={
          can.export ? (
            <a href={urls.export} className="text-sm hover:underline">
              {strings.export}
            </a>
          ) : null
        }
      />

      <StatusAlert status={status} />

      {versions.length > 1 ? (
        <form method="GET" action={urls.index} className="flex flex-wrap items-end gap-2">
          <div className="flex min-w-48 flex-col gap-1">
            <label htmlFor="form-builder-version" className="text-sm font-medium">
              {strings.version}
            </label>
            <select
              id="form-builder-version"
              name="version"
              defaultValue={version ?? ""}
              className="bg-background h-9 rounded-md border px-3"
            >
              <option value="">{strings.all_versions}</option>
              {versions.map((number) => (
                <option key={number} value={number}>
                  {format(strings.version_number ?? ":number", { number })}
                </option>
              ))}
            </select>
          </div>
          <Button type="submit" color="outline-secondary">
            {strings.filter}
          </Button>
        </form>
      ) : null}

      {entries.length === 0 ? (
        <Alert type="info">{strings.empty}</Alert>
      ) : (
        <>
          <div className="overflow-x-auto rounded-md border">
            <table className="w-full text-sm">
              <thead className="bg-muted/50">
                <tr>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.number}
                  </th>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.submitted_at}
                  </th>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.submitter}
                  </th>
                  {paths.map((path) => (
                    <th key={path} scope="col" className="px-3 py-2 text-start font-medium" dir="auto">
                      {columns[path]}
                    </th>
                  ))}
                  <th scope="col" className="px-3 py-2 text-end font-medium">
                    <span className="sr-only">{strings.actions}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                {entries.map((entry) => (
                  <tr key={entry.id} className="border-t" data-entry={entry.id}>
                    <td className="px-3 py-2">
                      <a href={entry.urls.show} className="font-medium hover:underline">
                        {entry.id}
                      </a>
                    </td>
                    <td className="px-3 py-2">{entry.submitted_at_label ?? "—"}</td>
                    <td className="px-3 py-2" dir="auto">
                      {entry.submitter ?? strings.guest}
                    </td>
                    {paths.map((path) => (
                      <td key={path} className="px-3 py-2" dir="auto">
                        {entry.values[path] ?? "—"}
                      </td>
                    ))}
                    <td className="px-3 py-2 text-end">
                      <LinkButton url={entry.urls.show} color="outline-secondary" size="sm">
                        {strings.view}
                      </LinkButton>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <PaginationNav pagination={pagination} strings={strings} />
        </>
      )}
    </div>
  )
}
