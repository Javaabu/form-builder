import { Card, File, Form, Submit, Text, Textarea } from "@javaabu/forms"
import { PageHeader } from "../../components/PageHeader"
import { StatusAlert } from "../../components/StatusAlert"
import type { FormsImportProps } from "../../types"

/** form-builder::Forms/Import — upload or paste an export */
export default function Import({ action, back_url, status, strings }: FormsImportProps): React.JSX.Element {
  return (
    <div className="flex flex-col gap-6" data-form-builder-import="">
      <PageHeader
        title={strings.import_title ?? "Import a form"}
        description={strings.import_description}
        backUrl={back_url}
        backLabel={strings.back}
      />
      <StatusAlert status={status} />

      <Form action={action} method="POST" files>
        <Card>
          <div className="flex flex-col gap-4">
            <File name="file" label={strings.import_file} mimetypes="application/json" extensions="json" />
            <Textarea name="json" label={strings.import_json} rows={8} dir="ltr" />
            <Text name="key" label={strings.import_key} dir="ltr" help={strings.import_key_help} />
          </div>
        </Card>
        <Submit>{strings.import_submit}</Submit>
      </Form>
    </div>
  )
}
