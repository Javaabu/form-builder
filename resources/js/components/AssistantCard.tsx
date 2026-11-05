import { Card, Form, Submit, Textarea } from "@javaabu/forms"
import type { Strings } from "../types"

/** The AI assistant: a description, sent to the assistant route */
export function AssistantCard({ url, strings }: { url: string; strings: Strings }): React.JSX.Element {
  return (
    <div data-form-builder-assistant="">
      <Card title={strings.assistant_title}>
        <Form action={url} method="POST">
          <div className="flex flex-col gap-3">
            <p className="text-muted-foreground text-sm">{strings.assistant_help}</p>
            <Textarea name="description" label={strings.assistant_prompt} rows={4} required dir="auto" />
            <div>
              <Submit>{strings.assistant_submit}</Submit>
            </div>
          </div>
        </Form>
      </Card>
    </div>
  )
}
