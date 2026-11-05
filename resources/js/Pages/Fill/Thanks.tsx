import { Alert, LinkButton } from "@javaabu/forms"
import { PageHeader } from "../../components/PageHeader"
import { format } from "../../sortable"
import type { FillThanksProps } from "../../types"

/** form-builder::Fill/Thanks — after submitting */
export default function Thanks({ title, message, entry, form_url, strings }: FillThanksProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-thanks="">
      <PageHeader title={title} />
      <Alert type="success">
        <p className="font-medium">{strings.thanks}</p>
        <p dir="auto">{message}</p>
        {entry ? <p>{format(strings.reference ?? ":number", { number: entry })}</p> : null}
      </Alert>
      {form_url ? (
        <div>
          <LinkButton url={form_url} color="outline-secondary">
            {strings.again}
          </LinkButton>
        </div>
      ) : null}
    </div>
  )
}
