import { Alert, Button, LinkButton } from "@javaabu/forms"
import { AssistantCard } from "../../components/AssistantCard"
import { PageHeader } from "../../components/PageHeader"
import { PaginationNav } from "../../components/PaginationNav"
import { StatusAlert } from "../../components/StatusAlert"
import { StatusBadge } from "../../components/StatusBadge"
import type { FormsIndexProps } from "../../types"

/** form-builder::Forms/Index — every form, with search */
export default function Index({
  forms,
  pagination,
  search,
  can,
  urls,
  status,
  strings,
}: FormsIndexProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-forms="">
      <PageHeader
        title={strings.title ?? "Forms"}
        description={strings.description}
        actions={
          <>
            {urls.import && can.create ? (
              <LinkButton url={urls.import} color="outline-secondary" startIcon="upload">
                {strings.import}
              </LinkButton>
            ) : null}
            {urls.create && can.create ? (
              <LinkButton url={urls.create} startIcon="plus">
                {strings.create}
              </LinkButton>
            ) : null}
          </>
        }
      />

      <StatusAlert status={status} />

      <form method="GET" action={urls.index} className="flex flex-wrap items-end gap-2" role="search">
        <div className="flex min-w-64 flex-col gap-1">
          <label htmlFor="form-builder-search" className="text-sm font-medium">
            {strings.search}
          </label>
          <input
            id="form-builder-search"
            type="search"
            name="search"
            defaultValue={search}
            dir="auto"
            className="bg-background h-9 rounded-md border px-3"
          />
        </div>
        <Button type="submit" color="outline-secondary">
          {strings.search_button}
        </Button>
      </form>

      {urls.assistant && can.create ? <AssistantCard url={urls.assistant} strings={strings} /> : null}

      {forms.length === 0 ? (
        <Alert type="info">{search !== "" ? strings.no_results : strings.empty}</Alert>
      ) : (
        <>
          <div className="overflow-x-auto rounded-md border">
            <table className="w-full text-sm">
              <thead className="bg-muted/50">
                <tr>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.name}
                  </th>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.status}
                  </th>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.version}
                  </th>
                  <th scope="col" className="px-3 py-2 text-start font-medium">
                    {strings.entries}
                  </th>
                  <th scope="col" className="px-3 py-2 text-end font-medium">
                    <span className="sr-only">{strings.actions}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                {forms.map((form) => (
                  <tr key={form.key} className="border-t" data-form-key={form.key}>
                    <td className="px-3 py-2">
                      <a href={form.urls.show} className="font-medium hover:underline" dir="auto">
                        {form.name}
                      </a>
                      <div className="text-muted-foreground text-xs" dir="ltr">
                        {form.key}
                      </div>
                    </td>
                    <td className="px-3 py-2">
                      <StatusBadge form={form} />
                      {form.is_locked ? (
                        <span className="text-muted-foreground ms-1 text-xs">{strings.locked}</span>
                      ) : null}
                    </td>
                    <td className="px-3 py-2">{form.published_version ?? "—"}</td>
                    <td className="px-3 py-2">
                      {form.urls.entries ? (
                        <a href={form.urls.entries} className="hover:underline">
                          {form.entries_count}
                        </a>
                      ) : (
                        form.entries_count
                      )}
                    </td>
                    <td className="px-3 py-2 text-end">
                      <LinkButton url={form.urls.show} color="outline-secondary" size="sm">
                        {strings.open_builder}
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
