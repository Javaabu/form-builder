import { Status } from "@javaabu/forms"
import type { FormData } from "../types"

/** A form's status: published, published with changes, or draft */
export function StatusBadge({ form }: { form: FormData }): React.JSX.Element {
  const color = form.status === "published" ? "success" : form.status === "changed" ? "warning" : "secondary"

  return <Status label={form.status_label} color={color} />
}
