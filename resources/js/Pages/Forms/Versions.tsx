import { Status } from "@javaabu/forms"
import { PageHeader } from "../../components/PageHeader"
import { format } from "../../sortable"
import type { FormsVersionsProps } from "../../types"

function formatDate(value: string | null): string {
  if (!value) return "—"
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString(document.documentElement.lang || undefined)
}

/** form-builder::Forms/Versions — every version, with its entries */
export default function Versions({ record, versions, back_url, strings }: FormsVersionsProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-versions="">
      <PageHeader
        title={strings.versions ?? "Versions"}
        description={record.name}
        backUrl={back_url}
        backLabel={strings.back}
      />

      <div className="overflow-x-auto rounded-md border">
        <table className="w-full text-sm">
          <thead className="bg-muted/50">
            <tr>
              <th scope="col" className="px-3 py-2 text-start font-medium">
                {format(strings.version_number ?? ":number", { number: "" }).trim()}
              </th>
              <th scope="col" className="px-3 py-2 text-start font-medium">
                {strings.published_at}
              </th>
              <th scope="col" className="px-3 py-2 text-start font-medium">
                {strings.author}
              </th>
              <th scope="col" className="px-3 py-2 text-start font-medium">
                {strings.fields_count}
              </th>
              <th scope="col" className="px-3 py-2 text-start font-medium">
                {strings.entries_count}
              </th>
            </tr>
          </thead>
          <tbody>
            {versions.map((version) => (
              <tr key={version.id} className="border-t" data-version={version.number}>
                <td className="px-3 py-2">
                  {version.number}{" "}
                  {version.is_draft ? (
                    <Status label={strings.draft} color="secondary" />
                  ) : version.is_current ? (
                    <Status label={strings.current} color="success" />
                  ) : null}
                </td>
                <td className="px-3 py-2">{formatDate(version.published_at)}</td>
                <td className="px-3 py-2" dir="auto">
                  {version.author ?? "—"}
                </td>
                <td className="px-3 py-2">{version.fields_count}</td>
                <td className="px-3 py-2">{version.entries_count}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
